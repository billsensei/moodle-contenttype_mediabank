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

namespace contenttype_mediabank;

/**
 * Captions and transcripts for a course that links to a media bank item.
 *
 * A viewer in a course cannot read the item's own context, so the caption is served through the link (the file alias) in
 * the course's context instead. The route names the alias, never the item; this class decides whether the alias is in a
 * place where being able to see the page means being able to read the caption, and whether this viewer can see it.
 * See docs/CAPTIONS-DESIGN.md.
 *
 * @package    contenttype_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_captions {
    /** @var string File area name in the URL. No file is stored under it. */
    public const FILEAREA = 'linkcaptions';

    /** @var string[] Activity file areas where being able to see the activity means being able to see the file. */
    protected const MODULE_AREAS = [
        'mod_resource' => 'content',
        'mod_page' => 'content',
        'mod_book' => 'chapter',
        'mod_folder' => 'content',
    ];

    /**
     * The URL a browser uses to fetch the captions of a link.
     *
     * @param \stored_file $alias The link.
     * @return \moodle_url
     */
    public static function url_for(\stored_file $alias): \moodle_url {
        return \moodle_url::make_pluginfile_url(
            $alias->get_contextid(),
            'contenttype_mediabank',
            self::FILEAREA,
            $alias->get_id(),
            '/',
            captions::FILENAME
        );
    }

    /**
     * The media bank item a file is a link to.
     *
     * @param \stored_file $alias A file in a course.
     * @return content|null Null if the file is not a link to a media bank item.
     */
    public static function item_of(\stored_file $alias): ?content {
        global $DB;
        if ($alias->is_directory() || !$alias->is_external_file()) {
            return null;
        }
        try {
            $source = \file_storage::unpack_reference($alias->get_reference());
        } catch (\Exception $e) {
            return null;
        }
        if ($source['component'] !== 'contentbank' || $source['filearea'] !== 'public') {
            return null;
        }
        $record = $DB->get_record('contentbank_content', ['id' => $source['itemid'], 'contenttype' => 'contenttype_mediabank']);
        return $record ? new content($record) : null;
    }

    /**
     * Whether the link is in a place where seeing the page means being allowed to read the file.
     *
     * Anything not listed (forum posts, submissions, block content, user files) is refused: there, who may see a file
     * depends on the record it belongs to, which this class cannot know. This does not depend on who is asking.
     *
     * @param \stored_file $alias The link.
     * @return bool
     */
    public static function is_place_allowed(\stored_file $alias): bool {
        $context = \context::instance_by_id($alias->get_contextid(), IGNORE_MISSING);
        if (!$context) {
            return false;
        }
        $component = $alias->get_component();
        $filearea = $alias->get_filearea();
        if ($context->contextlevel == CONTEXT_MODULE) {
            if (!str_starts_with($component, 'mod_')) {
                return false;
            }
            $cm = get_coursemodule_from_id(null, $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm || 'mod_' . $cm->modname !== $component) {
                return false;
            }
            return $filearea === 'intro' || (self::MODULE_AREAS[$component] ?? null) === $filearea;
        }
        if ($context->contextlevel == CONTEXT_COURSE) {
            return $component === 'course' && in_array($filearea, ['summary', 'section'], true);
        }
        return false;
    }

    /**
     * Whether the current user may read the captions of a link.
     *
     * The rule is no weaker than "may open the page the link is on".
     *
     * @param \stored_file $alias The link.
     * @return bool
     */
    public static function viewer_may_read(\stored_file $alias): bool {
        if (!self::is_place_allowed($alias)) {
            return false;
        }
        $context = \context::instance_by_id($alias->get_contextid(), IGNORE_MISSING);
        $coursecontext = $context ? $context->get_course_context(false) : false;
        if (!$coursecontext) {
            return false;
        }
        $course = get_course($coursecontext->instanceid);
        if (!can_access_course($course)) {
            return false;
        }
        $modinfo = get_fast_modinfo($course);
        if ($context->contextlevel == CONTEXT_MODULE) {
            $cm = $modinfo->cms[$context->instanceid] ?? null;
            if (!$cm || !$cm->uservisible || $cm->deletioninprogress) {
                return false;
            }
            if (isset(self::MODULE_AREAS[$alias->get_component()]) && $alias->get_filearea() !== 'intro') {
                return has_capability('mod/' . $cm->modname . ':view', $context);
            }
            return true;
        }
        if ($alias->get_filearea() === 'section') {
            $section = $modinfo->get_section_info_by_id($alias->get_itemid());
            return $section && $section->uservisible;
        }
        return true;
    }

    /**
     * The caption file to send for a request to the route, or null for any reason at all.
     *
     * Every refusal looks the same, so the route cannot be used to find out which files, items or captions exist.
     *
     * @param \context $context The context in the URL.
     * @param array $args What follows the file area in the URL: the link's file id, then the file name.
     * @return \stored_file|null
     */
    public static function find_file(\context $context, array $args): ?\stored_file {
        if (count($args) !== 2 || !ctype_digit((string) $args[0]) || $args[1] !== captions::FILENAME) {
            return null;
        }
        $alias = get_file_storage()->get_file_by_id((int) $args[0]);
        if (!$alias || (int) $alias->get_contextid() !== (int) $context->id || !self::viewer_may_read($alias)) {
            return null;
        }
        $item = self::item_of($alias);
        return $item ? captions::get($item) : null;
    }
}
