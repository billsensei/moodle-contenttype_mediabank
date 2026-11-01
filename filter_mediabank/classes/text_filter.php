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
 * Gives a picture that was added to a course from the media bank as a link the text alternative kept with the bank item.
 *
 * Only a picture with no text alternative, an empty one, or just its file name is changed. Anything a teacher wrote, and a
 * picture marked as decorative, is left alone.
 *
 * @package    filter_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    #[\Override]
    public function filter($text, array $options = []) {
        if (!is_string($text) || stripos($text, '<img') === false || strpos($text, 'pluginfile.php') === false) {
            return $text;
        }
        return preg_replace_callback('/<img\b[^>]*>/i', fn(array $match) => alt_text::apply_to_tag($match[0]), $text);
    }
}
