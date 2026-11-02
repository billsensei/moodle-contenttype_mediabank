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

/**
 * Finds the media bank item behind a picture in course text and puts its text alternative on the picture.
 *
 * @package    filter_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class alt_text {
    /**
     * Forget what was found. For tests.
     */
    public static function reset_cache(): void {
        link_lookup::reset_cache();
    }

    /**
     * Give every media bank picture in a piece of HTML its text alternative.
     *
     * The text is walked once, and each <img> tag is read the way a browser's tokenizer reads it. A tag that does not
     * end is left as it is, together with everything after it.
     *
     * @param string $text HTML.
     * @param int $contextid Context of the text. Only links in this context are used.
     * @return string The HTML, changed where a picture needed it.
     */
    public static function apply_to_text(string $text, int $contextid): string {
        $out = '';
        $pos = 0;
        $length = strlen($text);
        while (($found = stripos($text, '<img', $pos)) !== false) {
            $after = $text[$found + 4] ?? '';
            if ($after === '' || !str_contains(html_tag::SPACE . '/>', $after)) {
                // Something like <image or <imgx, not an img tag.
                $out .= substr($text, $pos, $found + 4 - $pos);
                $pos = $found + 4;
                continue;
            }
            $parsed = html_tag::parse($text, $found + 4);
            if ($parsed === null) {
                break;
            }
            $tag = substr($text, $found, $parsed['end'] - $found);
            $out .= substr($text, $pos, $found - $pos) . self::apply_to_tag($tag, $contextid);
            $pos = $parsed['end'];
        }
        return $out . ($pos < $length ? substr($text, $pos) : '');
    }

    /**
     * Change one <img> tag if it points at a media bank link and has no real text alternative.
     *
     * Only an attribute that is really called src, alt or role is looked at. Text that merely looks like `alt='...'` inside
     * another attribute's value is never touched.
     *
     * @param string $tag The whole tag, from < to >.
     * @param int $contextid Context of the text.
     * @return string The tag, changed or not.
     */
    public static function apply_to_tag(string $tag, int $contextid): string {
        $parsed = html_tag::parse($tag, 4);
        if ($parsed === null || $parsed['end'] !== strlen($tag)) {
            return $tag;
        }
        $attributes = $parsed['attributes'];
        if (!isset($attributes['src']) || $attributes['src']['value'] === null) {
            return $tag;
        }
        $role = strtolower(trim((string) ($attributes['role']['value'] ?? ''), html_tag::SPACE));
        if ($role === 'presentation' || $role === 'none') {
            return $tag;
        }
        $url = html_entity_decode($attributes['src']['value'], ENT_QUOTES | ENT_HTML5);
        $current = html_entity_decode((string) ($attributes['alt']['value'] ?? ''), ENT_QUOTES | ENT_HTML5);
        if (!self::is_missing($current, self::filename_of($url))) {
            return $tag;
        }
        $bankalt = self::find_alt($url, $contextid);
        if ($bankalt === '') {
            return $tag;
        }
        $attribute = 'alt="' . s($bankalt) . '"';
        if (isset($attributes['alt'])) {
            $alt = $attributes['alt'];
            return substr_replace($tag, $attribute, $alt['start'], $alt['end'] - $alt['start']);
        }
        // Add it after the last attribute, or after the tag name when there is none.
        $last = array_reduce($attributes, fn($carry, $attr) => max($carry, $attr['end']), 4);
        return substr_replace($tag, ' ' . $attribute, $last, 0);
    }

    /**
     * Whether a text alternative is absent, empty or only the file name.
     *
     * @param string $alt The current text alternative.
     * @param string $filename The file name of the picture.
     * @return bool
     */
    public static function is_missing(string $alt, string $filename): bool {
        $alt = trim($alt);
        if ($alt === '') {
            return true;
        }
        $name = \core_text::strtolower($filename);
        $base = \core_text::strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $alt = \core_text::strtolower($alt);
        return $alt === $name || $alt === $base;
    }

    /**
     * The file name at the end of a URL.
     *
     * @param string $url The picture's URL.
     * @return string
     */
    protected static function filename_of(string $url): string {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        return rawurldecode(basename($path));
    }

    /**
     * The text alternative of the media bank item that a picture URL is a link to.
     *
     * @param string $url A pluginfile URL.
     * @param int $contextid Context of the text. A link in another context gives no text.
     * @return string The text alternative, or an empty string when the URL is not a link to a media bank item.
     */
    public static function find_alt(string $url, int $contextid): string {
        global $DB;
        $alias = link_lookup::find_alias($url);
        if (!$alias || (int) $alias->get_contextid() !== $contextid) {
            return '';
        }
        $source = \file_storage::unpack_reference($alias->get_reference());
        $configdata = $DB->get_field('contentbank_content', 'configdata', [
            'id' => $source['itemid'],
            'contenttype' => 'contenttype_mediabank',
        ]);
        $metadata = json_decode((string) $configdata, true);
        return is_array($metadata) ? trim((string) ($metadata['alt'] ?? '')) : '';
    }
}
