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
 * Turns a pluginfile URL in course text into the link (file alias) to a media bank item that it names.
 *
 * @package    filter_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_lookup {
    /** @var array Found links by URL path, for the length of the request. False means none. */
    protected static array $cache = [];

    /**
     * Forget what was found. For tests.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

    /**
     * The link that a pluginfile URL points at, if it is a link to a media bank item.
     *
     * @param string $url A pluginfile URL, with its entities already decoded.
     * @return \stored_file|null
     */
    public static function find_alias(string $url): ?\stored_file {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $position = strpos($path, '/pluginfile.php/');
        if ($position === false) {
            return null;
        }
        $parts = array_map('rawurldecode', explode('/', trim(substr($path, $position + strlen('/pluginfile.php/')), '/')));
        if (count($parts) < 4 || !ctype_digit($parts[0])) {
            return null;
        }
        $key = implode('/', $parts);
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::lookup($parts) ?? false;
        }
        return self::$cache[$key] ?: null;
    }

    /**
     * Look the file up and check that it is a link to a media bank item.
     *
     * @param string[] $parts Context id, component, file area, then the rest of the path.
     * @return \stored_file|null
     */
    protected static function lookup(array $parts): ?\stored_file {
        global $DB;
        $fs = get_file_storage();
        $contextid = (int) array_shift($parts);
        $component = array_shift($parts);
        $filearea = array_shift($parts);
        $filename = array_pop($parts);
        if ($filename === null || $filename === '') {
            return null;
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
                return null;
            }
            if ($source['component'] !== 'contentbank' || $source['filearea'] !== 'public') {
                return null;
            }
            $isbank = $DB->record_exists('contentbank_content', [
                'id' => $source['itemid'],
                'contenttype' => 'contenttype_mediabank',
            ]);
            return $isbank ? $file : null;
        }
        return null;
    }
}
