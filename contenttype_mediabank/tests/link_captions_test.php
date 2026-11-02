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

use contenttype_mediabank\tests\link_helper;
use contenttype_mediabank\tests\media_samples;

/**
 * Tests for reading an item's captions through a link in a course.
 *
 * @package    contenttype_mediabank
 * @category   test
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(link_captions::class)]
final class link_captions_test extends \advanced_testcase {
    /** @var string What the test caption says. */
    protected const VTT = "WEBVTT\n\n00:00.000 --> 00:01.000\nHello\n";

    /**
     * Make a video item with captions in the system context, as an administrator.
     *
     * @param string $vtt The caption text.
     * @return content
     */
    protected function make_item(string $vtt = self::VTT): content {
        $this->setAdminUser();
        $item = link_helper::create_item('talk.mp4', media_samples::get('mp4'));
        $this->set_captions($item, $vtt);
        return $item;
    }

    /**
     * Give an item a caption file.
     *
     * @param content $item The item.
     * @param string $vtt The caption text.
     */
    protected function set_captions(content $item, string $vtt): void {
        $adminctx = \context_user::instance(get_admin()->id)->id;
        $draftid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $adminctx, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => 'c.vtt',
        ], $vtt);
        captions::save($item, $fs->get_file($adminctx, 'user', 'draft', $draftid, '/', 'c.vtt'));
    }

    /**
     * Ask the route for the captions of a link, as the current user.
     *
     * @param \stored_file $alias The link.
     * @return string|null The caption text, or null if refused.
     */
    protected function fetch(\stored_file $alias): ?string {
        $context = \context::instance_by_id($alias->get_contextid());
        $file = link_captions::find_file($context, [(string) $alias->get_id(), captions::FILENAME]);
        return $file ? $file->get_content() : null;
    }

    /**
     * A student in the course reads the captions through a File resource, though the item's own context is closed to them.
     */
    public function test_student_reads_captions_through_a_file_resource(): void {
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $resource = $this->getDataGenerator()->create_module('resource', ['course' => $course->id]);
        $alias = link_helper::create_link($item, \context_module::instance($resource->cmid), 'mod_resource', 'content');

        $this->setUser($student);
        $this->assertFalse(\context_system::instance()->id === $alias->get_contextid());
        $this->assertSame(self::VTT, $this->fetch($alias));
        // The URL names the link and not the item.
        $url = link_captions::url_for($alias)->out(false);
        $this->assertStringContainsString('/linkcaptions/' . $alias->get_id() . '/captions.vtt', $url);
        $this->assertStringNotContainsString('/' . $item->get_id() . '/', $url);
    }

    /**
     * Someone outside the course, a hidden activity and a wrong context all get nothing.
     */
    public function test_people_and_places_that_get_nothing(): void {
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $outsider = $this->getDataGenerator()->create_user();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $hidden = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $alias = link_helper::create_link($item, \context_module::instance($page->cmid), 'mod_page', 'content');
        $hiddenalias = link_helper::create_link($item, \context_module::instance($hidden->cmid), 'mod_page', 'content');

        $this->setUser($outsider);
        $this->assertNull($this->fetch($alias), 'Not enrolled.');
        $this->setUser($student);
        $this->assertSame(self::VTT, $this->fetch($alias));
        $this->assertNull($this->fetch($hiddenalias), 'Hidden activity.');
        // The context in the URL must be the link's own.
        $wrongcontext = \context_module::instance($hidden->cmid);
        $this->assertNull(link_captions::find_file($wrongcontext, [(string) $alias->get_id(), captions::FILENAME]));
        // Bad arguments.
        $context = \context_module::instance($page->cmid);
        $id = (string) $alias->get_id();
        $badargs = [
            [], [$id], ['x', captions::FILENAME], [$id, 'other.vtt'], ['999999', captions::FILENAME],
            [$id, captions::FILENAME, 'x'],
        ];
        foreach ($badargs as $args) {
            $this->assertNull(link_captions::find_file($context, $args));
        }
    }

    /**
     * Only the places on the list give captions: an activity intro does, a forum post and a submission do not.
     */
    public function test_only_listed_places(): void {
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id]);
        $forumctx = \context_module::instance($forum->cmid);
        $coursectx = \context_course::instance($course->id);
        $labelctx = \context_module::instance($label->cmid);
        $setup = [
            'label intro' => [$labelctx, 'mod_label', 'intro', true],
            'forum intro' => [$forumctx, 'mod_forum', 'intro', true],
            'forum post' => [$forumctx, 'mod_forum', 'post', false],
            'forum attachment' => [$forumctx, 'mod_forum', 'attachment', false],
            'a different module name' => [$forumctx, 'mod_page', 'content', false],
            'course summary' => [$coursectx, 'course', 'summary', true],
            'course overview files' => [$coursectx, 'course', 'overviewfiles', false],
            'block' => [$coursectx, 'block_html', 'content', false],
        ];
        $aliases = [];
        foreach ($setup as $name => [$where, $component, $filearea]) {
            $aliases[$name] = link_helper::create_link($item, $where, $component, $filearea);
        }
        $this->setUser($student);
        foreach ($setup as $name => [, , , $allowed]) {
            $alias = $aliases[$name];
            $this->assertSame($allowed, link_captions::is_place_allowed($alias), $name);
            $this->assertSame($allowed ? self::VTT : null, $this->fetch($alias), $name);
        }
    }

    /**
     * A hidden book chapter keeps its files, and so its captions, from people who may not see it.
     */
    public function test_hidden_book_chapter(): void {
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $shown = $generator->create_chapter(['bookid' => $book->id]);
        $hidden = $generator->create_chapter(['bookid' => $book->id, 'hidden' => 1]);
        $other = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $foreign = $generator->create_chapter(['bookid' => $other->id]);
        $context = \context_module::instance($book->cmid);
        $shownalias = link_helper::create_link($item, $context, 'mod_book', 'chapter', (int) $shown->id);
        $hiddenalias = link_helper::create_link($item, $context, 'mod_book', 'chapter', (int) $hidden->id);
        // A chapter id that belongs to a different book, and one that does not exist.
        $foreignalias = link_helper::create_link($item, $context, 'mod_book', 'chapter', (int) $foreign->id);
        $missingalias = link_helper::create_link($item, $context, 'mod_book', 'chapter', 999999);

        $this->setUser($student);
        $this->assertSame(self::VTT, $this->fetch($shownalias));
        $this->assertNull($this->fetch($hiddenalias), 'Hidden chapter.');
        $this->assertNull($this->fetch($foreignalias), 'Chapter of another book.');
        $this->assertNull($this->fetch($missingalias), 'No such chapter.');
        $this->setUser($teacher);
        $this->assertSame(self::VTT, $this->fetch($hiddenalias));
    }

    /**
     * Each listed module is asked for the capability its own file code asks for, and a student without it gets nothing.
     */
    public function test_module_capability_is_required(): void {
        global $DB;
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $modules = [
            'resource' => ['mod_resource', 'content', 'mod/resource:view'],
            'page' => ['mod_page', 'content', 'mod/page:view'],
            'folder' => ['mod_folder', 'content', 'mod/folder:view'],
        ];
        foreach ($modules as $name => [$component, $filearea, $capability]) {
            $module = $this->getDataGenerator()->create_module($name, ['course' => $course->id]);
            $context = \context_module::instance($module->cmid);
            $alias = link_helper::create_link($item, $context, $component, $filearea);
            $this->setUser($student);
            $this->assertSame(self::VTT, $this->fetch($alias), $name);
            assign_capability($capability, CAP_PROHIBIT, $studentrole, $context->id, true);
            \accesslib_clear_all_caches_for_unit_testing();
            $this->assertNull($this->fetch($alias), $name . ' without ' . $capability);
        }
    }

    /**
     * A replaced caption is served at once, and a converted copy, a plain file and an item without captions give nothing.
     */
    public function test_changes_to_the_bank_show_at_once(): void {
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $page = \context_module::instance($this->getDataGenerator()->create_module('page', ['course' => $course->id])->cmid);
        $alias = link_helper::create_link($item, $page, 'mod_page', 'content');
        $plain = get_file_storage()->create_file_from_string([
            'contextid' => $page->id, 'component' => 'mod_page', 'filearea' => 'content', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'plain.mp4',
        ], media_samples::get('mp4'));
        $this->setUser($student);
        $this->assertSame(self::VTT, $this->fetch($alias));
        $this->assertNull($this->fetch($plain));

        $this->setAdminUser();
        $this->set_captions($item, "WEBVTT\n\n00:00.000 --> 00:01.000\nChanged\n");
        $this->setUser($student);
        $this->assertStringContainsString('Changed', $this->fetch($alias));

        $this->setAdminUser();
        captions::delete($item);
        $this->setUser($student);
        $this->assertNull($this->fetch($alias));

        $this->setAdminUser();
        $this->set_captions($item, self::VTT);
        usage::detach_in_course($item, (int) $course->id);
        $copy = get_file_storage()->get_file_by_id($alias->get_id());
        $this->setUser($student);
        $this->assertNull($this->fetch($copy), 'A converted copy has no captions.');
    }

    /**
     * Guests follow the course's guest access.
     */
    public function test_guest_follows_course_guest_access(): void {
        global $DB;
        $this->resetAfterTest();
        $item = $this->make_item();
        $course = $this->getDataGenerator()->create_course();
        $page = \context_module::instance($this->getDataGenerator()->create_module('page', ['course' => $course->id])->cmid);
        $alias = link_helper::create_link($item, $page, 'mod_page', 'content');
        $this->setGuestUser();
        $this->assertNull($this->fetch($alias));
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['courseid' => $course->id, 'enrol' => 'guest']);
        $this->assertSame(self::VTT, $this->fetch($alias));
    }
}
