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
 * Report of media bank items that nothing links to. Read-only.
 *
 * @package    local_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_mediabank_unlinked');

$days = optional_param('days', 0, PARAM_INT);
$days = max(0, min($days, 36500));
$limit = 500;

$PAGE->set_heading(get_string('unlinkedreport', 'local_mediabank'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('unlinkedreport', 'local_mediabank'));
echo html_writer::tag('p', get_string('unlinkedreport_intro', 'local_mediabank'));

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(), 'class' => 'mb-3']);
echo html_writer::label(get_string('unlinkedolderthan', 'local_mediabank'), 'id_days', true, ['class' => 'me-2']);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'days', 'id' => 'id_days', 'value' => $days,
    'min' => 0, 'max' => 36500, 'class' => 'form-control d-inline-block w-auto me-2']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('show', 'local_mediabank'),
    'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

$items = \contenttype_mediabank\usage::find_unlinked($days * DAYSECS);
if (!$items) {
    echo $OUTPUT->notification(get_string('unlinkednone', 'local_mediabank'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->caption = get_string('unlinkedreport', 'local_mediabank');
$table->captionhide = true;
$table->head = [
    get_string('unlinkedname', 'local_mediabank'),
    get_string('unlinkedsize', 'local_mediabank'),
    get_string('unlinkeduploaded', 'local_mediabank'),
];
$total = 0;
$shown = 0;
foreach ($items as $item) {
    $size = $item->get_file()->get_filesize();
    $total += $size;
    if ($shown++ >= $limit) {
        continue;
    }
    $url = new moodle_url('/contentbank/view.php', ['id' => $item->get_id()]);
    $table->data[] = [
        html_writer::link($url, s($item->get_name())),
        display_size($size),
        userdate($item->get_content()->timecreated, get_string('strftimedatefullshort', 'langconfig')),
    ];
}
echo html_writer::tag('p', get_string(
    'unlinkedsummary',
    'local_mediabank',
    ['count' => count($items), 'size' => display_size($total)]
));
if (count($items) > $limit) {
    echo $OUTPUT->notification(
        get_string('unlinkedlimit', 'local_mediabank', $limit),
        \core\output\notification::NOTIFY_WARNING
    );
}
echo html_writer::table($table);
echo $OUTPUT->footer();
