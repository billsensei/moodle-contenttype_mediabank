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

    /**
     * Change one <img> tag if it points at a media bank link and has no real text alternative.
     *
     * @param string $tag The whole tag, from < to >.
     * @return string The tag, changed or not.
     */
    public static function apply_to_tag(string $tag): string {
        if (!preg_match('/\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $tag, $src)) {
            return $tag;
        }
        $url = html_entity_decode($src[1] !== '' ? $src[1] : ($src[2] ?? ''), ENT_QUOTES | ENT_HTML5);
        if (preg_match('/\brole\s*=\s*(?:"(?:presentation|none)"|\'(?:presentation|none)\')/i', $tag)) {
            return $tag;
        }
        $hasalt = preg_match('/\balt\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $tag, $alt, PREG_OFFSET_CAPTURE);
        $current = '';
        if ($hasalt) {
            $current = html_entity_decode($alt[1][0] !== '' ? $alt[1][0] : ($alt[2][0] ?? ''), ENT_QUOTES | ENT_HTML5);
        }
        $filename = self::filename_of($url);
        if (!self::is_missing($current, $filename)) {
            return $tag;
        }
        $bankalt = self::find_alt($url);
        if ($bankalt === '') {
            return $tag;
        }
        $attribute = 'alt="' . s($bankalt) . '"';
        if ($hasalt) {
            return substr_replace($tag, $attribute, $alt[0][1], strlen($alt[0][0]));
        }
        return preg_replace('/\s*\/?>$/', ' ' . $attribute . '$0', $tag, 1);
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
