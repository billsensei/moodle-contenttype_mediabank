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

// NOTE: Behat requires this file name and the behat_ class name, so the usual lowercase test naming does not apply.

/**
 * Steps for the media bank content type.
 *
 * @package    contenttype_mediabank
 * @category   test
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Steps for the media bank content type.
 *
 * @package    contenttype_mediabank
 * @category   test
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_contenttype_mediabank extends behat_base {
    /**
     * Open the convert page for an item, looking up the ids.
     *
     * @Given /^I visit the media bank convert page for "(?P<name>[^"]*)" in course "(?P<shortname>[^"]*)"$/
     * @param string $name The item name.
     * @param string $shortname The course short name.
     */
    public function i_visit_the_media_bank_convert_page_for(string $name, string $shortname): void {
        global $DB;
        $id = $DB->get_field('contentbank_content', 'id', ['name' => $name], MUST_EXIST);
        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $this->getSession()->visit($this->locate_path("/contentbank/contenttype/mediabank/convert.php?id=$id&courseid=$courseid"));
    }

    /**
     * Open the convert page for an item id that does not exist.
     *
     * @Given /^I visit the media bank convert page for a missing item in course "(?P<shortname>[^"]*)"$/
     * @param string $shortname The course short name.
     */
    public function i_visit_the_media_bank_convert_page_for_a_missing_item(string $shortname): void {
        global $DB;
        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $this->getSession()->visit($this->locate_path("/contentbank/contenttype/mediabank/convert.php?id=99999&courseid=$courseid"));
    }
}
