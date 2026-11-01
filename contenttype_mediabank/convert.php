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

/**
 * Turn one course's links to a media bank item into independent copies. Asks first, then needs a POST with a session key.
 *
 * @package    contenttype_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);
require_capability('moodle/course:manageactivities', context_course::instance($course->id));

// A missing item and one that is not a media bank item look the same as an item this course does not link to, so the
// page cannot be used to find out which ids exist.
$content = null;
try {
    $found = (new \core_contentbank\contentbank())->get_content_from_id($id);
    $content = $found->get_content_type() === 'contenttype_mediabank' ? $found : null;
} catch (\dml_missing_record_exception $e) {
    $content = null;
}

// Only a course that really links to the item is sent on to the item. Anyone else goes back to the course, whatever the id.
$links = $content ? count(\contenttype_mediabank\usage::get_links_in_course($content, $courseid)) : 0;
$returnurl = $links
    ? new moodle_url('/contentbank/view.php', ['id' => $id, 'contextid' => $content->get_contextid()])
    : new moodle_url('/course/view.php', ['id' => $courseid]);
$PAGE->set_url(new moodle_url('/contentbank/contenttype/mediabank/convert.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_title(get_string('convertcourse', 'contenttype_mediabank'));
$PAGE->set_heading(format_string($course->fullname));

if ($links && $confirm && confirm_sesskey()) {
    $count = \contenttype_mediabank\usage::detach_in_course($content, $courseid);
    redirect($returnurl, get_string('convertdone', 'contenttype_mediabank', $count), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
if (!$links) {
    echo $OUTPUT->notification(get_string('convertnolinks', 'contenttype_mediabank'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->continue_button($returnurl);
} else {
    echo $OUTPUT->confirm(
        get_string('convertconfirm', 'contenttype_mediabank',
            ['name' => s($content->get_name()), 'course' => format_string($course->shortname), 'links' => $links]),
        new single_button(new moodle_url($PAGE->url, ['confirm' => 1]), get_string('convertbutton', 'contenttype_mediabank'),
            'post', single_button::BUTTON_PRIMARY),
        $returnurl
    );
}
echo $OUTPUT->footer();
