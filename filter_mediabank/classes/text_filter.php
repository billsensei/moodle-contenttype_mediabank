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
 * Gives a picture that was added to a course from the media bank as a link the text alternative kept with the bank item, and a
 * video or audio player the captions and transcript.
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
        // Everything is done before Moodle cleans the HTML, in filter_stage_pre_clean(), so that for text that is not trusted
        // the cleaner checks what this filter produced. Doing nothing here keeps the filter out of the stage after cleaning.
        return $text;
    }

    #[\Override]
    public function filter_stage_pre_clean(string $text, array $options): string {
        if (strpos($text, 'pluginfile.php') === false) {
            return $text;
        }
        if (stripos($text, '<img') !== false) {
            $text = alt_text::apply_to_text($text);
        }
        if (stripos($text, '<video') !== false || stripos($text, '<audio') !== false) {
            $text = media_tracks::apply_to_text($text, (int) $this->context->id);
        }
        return $text;
    }

    #[\Override]
    public function filter_stage_post_clean(string $text, array $options): string {
        return $text;
    }

    #[\Override]
    public function filter_stage_string(string $text, array $options): string {
        return $text;
    }
}
