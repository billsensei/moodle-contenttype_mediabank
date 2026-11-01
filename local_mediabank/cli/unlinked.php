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
 * List the media bank items that nothing links to, largest first, to help clear space.
 *
 * Usage: php local/mediabank/cli/unlinked.php [--days=N] [--csv]
 *
 * Only reads. Nothing is changed or deleted. A copy made from an item is an independent file and does not count as a link.
 *
 * @package    local_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(['days' => 0, 'csv' => false, 'help' => false], ['h' => 'help']);

if ($unrecognised || $options['help'] || (int) $options['days'] < 0) {
    cli_writeln('Usage: php local/mediabank/cli/unlinked.php [--days=N] [--csv]');
    cli_writeln('  --days=N  only items uploaded at least N days ago');
    cli_writeln('  --csv     print id, name, size in bytes and upload date as CSV');
    exit($options['help'] ? 0 : 1);
}

/**
 * Make an item name safe to print: no control characters, and in CSV no leading character a spreadsheet reads as a formula.
 *
 * @param string $name The item name.
 * @param bool $csv True when the name goes into a CSV file.
 * @return string
 */
function local_mediabank_safe_name(string $name, bool $csv): string {
    $name = preg_replace('/[\x00-\x08\x0a-\x1f\x7f]/', ' ', $name);
    if ($csv && preg_match('/^[=+\-@\t\r]/', $name)) {
        $name = "'" . $name;
    }
    return $name;
}

$items = \contenttype_mediabank\usage::find_unlinked((int) $options['days'] * DAYSECS);
$total = 0;
if ($options['csv']) {
    cli_writeln('id,name,bytes,uploaded');
}
foreach ($items as $item) {
    $size = $item->get_file()->get_filesize();
    $total += $size;
    if ($options['csv']) {
        $name = str_replace('"', '""', local_mediabank_safe_name($item->get_name(), true));
        cli_writeln(implode(',', [$item->get_id(), '"' . $name . '"', $size,
            date('Y-m-d', $item->get_content()->timecreated)]));
    } else {
        cli_writeln(sprintf(
            '%6d  %10s  %s  %s',
            $item->get_id(),
            display_size($size),
            userdate($item->get_content()->timecreated, '%Y-%m-%d'),
            local_mediabank_safe_name($item->get_name(), false)
        ));
    }
}
if (!$options['csv']) {
    cli_writeln(count($items) . ' item(s) with no links, ' . display_size($total) . ' in all.');
}
