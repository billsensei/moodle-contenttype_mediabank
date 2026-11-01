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
    /** @var string[] Found alt text by file, for the length of the request. An empty string means none. */
    protected static array $cache = [];

    /**
     * Forget what was found. For tests.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

    /** @var string The characters HTML treats as white space inside a tag. Not PCRE's \s, which also has a vertical tab. */
    protected const SPACE = "\t\n\f\r ";

    /**
     * Give every media bank picture in a piece of HTML its text alternative.
     *
     * The text is walked once, and each <img> tag is read the way a browser's tokenizer reads it. A tag that does not
     * end is left as it is, together with everything after it.
     *
     * @param string $text HTML.
     * @return string The HTML, changed where a picture needed it.
     */
    public static function apply_to_text(string $text): string {
        $out = '';
        $pos = 0;
        $length = strlen($text);
        while (($found = stripos($text, '<img', $pos)) !== false) {
            $after = $text[$found + 4] ?? '';
            if ($after === '' || !str_contains(self::SPACE . '/>', $after)) {
                // Something like <image or <imgx, not an img tag.
                $out .= substr($text, $pos, $found + 4 - $pos);
                $pos = $found + 4;
                continue;
            }
            $parsed = self::parse_tag($text, $found + 4);
            if ($parsed === null) {
                break;
            }
            $out .= substr($text, $pos, $found - $pos) . self::apply_to_tag(substr($text, $found, $parsed['end'] - $found));
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
     * @return string The tag, changed or not.
     */
    public static function apply_to_tag(string $tag): string {
        $parsed = self::parse_tag($tag, 4);
        if ($parsed === null || $parsed['end'] !== strlen($tag)) {
            return $tag;
        }
        $attributes = $parsed['attributes'];
        if (!isset($attributes['src']) || $attributes['src']['value'] === null) {
            return $tag;
        }
        $role = strtolower(trim((string) ($attributes['role']['value'] ?? ''), self::SPACE));
        if ($role === 'presentation' || $role === 'none') {
            return $tag;
        }
        $url = html_entity_decode($attributes['src']['value'], ENT_QUOTES | ENT_HTML5);
        $current = html_entity_decode((string) ($attributes['alt']['value'] ?? ''), ENT_QUOTES | ENT_HTML5);
        if (!self::is_missing($current, self::filename_of($url))) {
            return $tag;
        }
        $bankalt = self::find_alt($url);
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
     * Read the attributes of a start tag the way the HTML tokenizer does, from just after "<img" to the closing ">".
     *
     * A quote only starts a value straight after "=". Anywhere else it is an ordinary character of a name. Names are
     * lower-cased and a repeated name keeps its first use, as in a browser. Values are raw, with their entities.
     *
     * @param string $text The text holding the tag.
     * @param int $pos Where to start, just after "<img".
     * @return array|null ['attributes' => name => ['value' => string|null, 'start' => int, 'end' => int], 'end' => the
     *      position after ">"], or null if the text ends before the tag does.
     */
    protected static function parse_tag(string $text, int $pos): ?array {
        $length = strlen($text);
        $attributes = [];
        while (true) {
            // Before an attribute name.
            while ($pos < $length && ($text[$pos] === '/' || str_contains(self::SPACE, $text[$pos]))) {
                $pos++;
            }
            if ($pos >= $length) {
                return null;
            }
            if ($text[$pos] === '>') {
                return ['attributes' => $attributes, 'end' => $pos + 1];
            }
            // The attribute name. Its first character counts whatever it is, even "=".
            $start = $pos++;
            while ($pos < $length && !str_contains(self::SPACE . '/>=', $text[$pos])) {
                $pos++;
            }
            $name = strtolower(substr($text, $start, $pos - $start));
            $value = null;
            $end = $pos;
            $look = $pos;
            while ($look < $length && str_contains(self::SPACE, $text[$look])) {
                $look++;
            }
            if ($look < $length && $text[$look] === '=') {
                $look++;
                while ($look < $length && str_contains(self::SPACE, $text[$look])) {
                    $look++;
                }
                if ($look >= $length) {
                    return null;
                }
                $quote = $text[$look];
                if ($quote === '"' || $quote === "'") {
                    $close = strpos($text, $quote, $look + 1);
                    if ($close === false) {
                        return null;
                    }
                    $value = substr($text, $look + 1, $close - $look - 1);
                    $pos = $close + 1;
                } else {
                    $close = $look;
                    while ($close < $length && $text[$close] !== '>' && !str_contains(self::SPACE, $text[$close])) {
                        $close++;
                    }
                    $value = substr($text, $look, $close - $look);
                    $pos = $close;
                }
                $end = $pos;
            }
            if (!isset($attributes[$name])) {
                $attributes[$name] = ['value' => $value, 'start' => $start, 'end' => $end];
            }
        }
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
     * @return string The text alternative, or an empty string when the URL is not a link to a media bank item.
     */
    public static function find_alt(string $url): string {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $position = strpos($path, '/pluginfile.php/');
        if ($position === false) {
            return '';
        }
        $parts = array_map('rawurldecode', explode('/', trim(substr($path, $position + strlen('/pluginfile.php/')), '/')));
        if (count($parts) < 4 || !ctype_digit($parts[0])) {
            return '';
        }
        $key = implode('/', $parts);
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::lookup($parts);
        }
        return self::$cache[$key];
    }

    /**
     * Look the file up and follow its link to the bank item.
     *
     * @param string[] $parts Context id, component, file area, then the rest of the path.
     * @return string
     */
    protected static function lookup(array $parts): string {
        global $DB;
        $fs = get_file_storage();
        $contextid = (int) array_shift($parts);
        $component = array_shift($parts);
        $filearea = array_shift($parts);
        $filename = array_pop($parts);
        if ($filename === null || $filename === '') {
            return '';
        }
        // The next part is the item id when it is a number, and the file path is what lies between it and the name.
        $candidates = [[0, $parts]];
        if ($parts && ctype_digit($parts[0])) {
            array_unshift($candidates, [(int) $parts[0], array_slice($parts, 1)]);
        }
        foreach ($candidates as [$itemid, $dirs]) {
            $filepath = $dirs ? '/' . implode('/', $dirs) . '/' : '/';
            $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
            if (!$file || $file->is_directory() || !$file->is_external_file()) {
                continue;
            }
            try {
                $source = \file_storage::unpack_reference($file->get_reference());
            } catch (\Exception $e) {
                return '';
            }
            if ($source['component'] !== 'contentbank' || $source['filearea'] !== 'public') {
                return '';
            }
            $configdata = $DB->get_field('contentbank_content', 'configdata', [
                'id' => $source['itemid'],
                'contenttype' => 'contenttype_mediabank',
            ]);
            $metadata = json_decode((string) $configdata, true);
            return is_array($metadata) ? trim((string) ($metadata['alt'] ?? '')) : '';
        }
        return '';
    }
}
