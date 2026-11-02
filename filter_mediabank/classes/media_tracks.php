<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace filter_mediabank;

use contenttype_mediabank\captions;
use contenttype_mediabank\link_captions;

/**
 * Adds the captions and transcript kept with a media bank item to a <video> or <audio> element that links to it.
 *
 * Captions are added as a <track> that is not switched on by default; the viewer turns them on. A player the author has
 * already given a <track> is left alone. See docs/CAPTIONS-DESIGN.md.
 *
 * @package    filter_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_tracks {
    /**
     * Give every media bank player in a piece of HTML its captions and transcript.
     *
     * Only a link that lives in the context the text belongs to is used, so text cannot borrow a link from somewhere else.
     * An element that does not end, or has anything unusual, is left as it is.
     *
     * @param string $text HTML.
     * @param int $contextid The context the text belongs to.
     * @return string
     */
    public static function apply_to_text(string $text, int $contextid): string {
        if (!class_exists(link_captions::class)) {
            return $text;
        }
        $out = '';
        $pos = 0;
        while (true) {
            $video = stripos($text, '<video', $pos);
            $audio = stripos($text, '<audio', $pos);
            $found = min(array_filter([$video, $audio], fn($p) => $p !== false) ?: [false]);
            if ($found === false) {
                break;
            }
            $name = strtolower(substr($text, $found + 1, 5));
            $after = $text[$found + 6] ?? '';
            if ($after === '' || !str_contains(html_tag::SPACE . '/>', $after)) {
                $out .= substr($text, $pos, $found + 6 - $pos);
                $pos = $found + 6;
                continue;
            }
            $start = html_tag::parse($text, $found + 6);
            $close = $start ? stripos($text, '</' . $name, $start['end']) : false;
            $closeend = $close === false ? false : strpos($text, '>', $close);
            if ($start === null || $closeend === false) {
                break;
            }
            $inner = substr($text, $start['end'], $close - $start['end']);
            $extra = self::extras($start['attributes'], $inner, $contextid);
            $out .= substr($text, $pos, $close - $pos) . $extra['track'] . substr($text, $close, $closeend + 1 - $close)
                . $extra['transcript'];
            $pos = $closeend + 1;
        }
        return $out . substr($text, $pos);
    }

    /**
     * What to add to one player.
     *
     * @param array $attributes Attributes of the <video> or <audio> tag.
     * @param string $inner The HTML between its start and end tags.
     * @param int $contextid The context the text belongs to.
     * @return string[] 'track' goes inside the element at its end and 'transcript' goes after it. Both may be empty.
     */
    protected static function extras(array $attributes, string $inner, int $contextid): array {
        $none = ['track' => '', 'transcript' => ''];
        if (stripos($inner, '<track') !== false) {
            return $none;
        }
        $alias = self::find_source_link($attributes, $inner);
        if (!$alias || (int) $alias->get_contextid() !== $contextid || !link_captions::is_place_allowed($alias)) {
            return $none;
        }
        $item = link_captions::item_of($alias);
        if (!$item) {
            return $none;
        }
        $track = '';
        if (captions::get($item)) {
            $track = '<track kind="captions" label="' . s(get_string('captions', 'filter_mediabank')) . '" src="'
                . s(link_captions::url_for($alias)->out(false)) . '">';
        }
        $transcript = '';
        $words = trim($item->get_transcript());
        if ($words !== '') {
            $heading = s(get_string('transcript', 'filter_mediabank'));
            $body = nl2br(s($words), false);
            $transcript = self::cleaner_keeps_details()
                ? '<details class="filter-mediabank-transcript"><summary>' . $heading . '</summary><p>' . $body . '</p></details>'
                : '<div class="filter-mediabank-transcript"><p><strong>' . $heading . '</strong></p><p>' . $body . '</p></div>';
        }
        return ['track' => $track, 'transcript' => $transcript];
    }

    /** @var bool|null Whether Moodle's HTML cleaner keeps <details>, found once per request. Null until asked. */
    protected static ?bool $detailsallowed = null;

    /**
     * Whether the transcript can be a collapsible block on this site.
     *
     * Moodle 5.3 added <details> and <summary> to what its cleaner allows. On earlier versions the cleaner would strip
     * the tags and leave a stray word, so a plain labelled block is used there. The cleaner is asked rather than the
     * version number, so a site that changes the rules gets the right answer too.
     *
     * @return bool
     */
    public static function cleaner_keeps_details(): bool {
        if (self::$detailsallowed === null) {
            $cleaned = clean_text('<details><summary>a</summary><p>b</p></details>', FORMAT_HTML);
            self::$detailsallowed = str_contains($cleaned, '<details') && str_contains($cleaned, '<summary');
        }
        return self::$detailsallowed;
    }

    /**
     * Forget what was found, or fix the answer. For tests.
     *
     * @param bool|null $force True or false to fix the answer, null to ask the cleaner again.
     */
    public static function reset_details_probe(?bool $force = null): void {
        self::$detailsallowed = $force;
    }

    /**
     * The first source of a player that is a link to a media bank item.
     *
     * @param array $attributes Attributes of the <video> or <audio> tag.
     * @param string $inner The HTML inside it.
     * @return \stored_file|null
     */
    protected static function find_source_link(array $attributes, string $inner): ?\stored_file {
        $urls = [];
        if (isset($attributes['src']['value'])) {
            $urls[] = $attributes['src']['value'];
        }
        $pos = 0;
        while (($found = stripos($inner, '<source', $pos)) !== false) {
            $after = $inner[$found + 7] ?? '';
            $pos = $found + 7;
            if ($after === '' || !str_contains(html_tag::SPACE . '/>', $after)) {
                continue;
            }
            $parsed = html_tag::parse($inner, $found + 7);
            if ($parsed === null) {
                break;
            }
            if (isset($parsed['attributes']['src']['value'])) {
                $urls[] = $parsed['attributes']['src']['value'];
            }
            $pos = $parsed['end'];
        }
        foreach ($urls as $url) {
            $alias = link_lookup::find_alias(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
            if ($alias) {
                return $alias;
            }
        }
        return null;
    }
}
