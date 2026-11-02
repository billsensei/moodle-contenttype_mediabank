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

use contenttype_mediabank\captions;
use contenttype_mediabank\tests\link_helper;
use contenttype_mediabank\tests\media_samples;

/**
 * Tests for the captions and transcript step of the media bank filter.
 *
 * @package    filter_mediabank
 * @category   test
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(media_tracks::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(link_lookup::class)]
final class media_tracks_test extends \advanced_testcase {
    /**
     * Make an item and link it into a label in a new course.
     *
     * @param bool $captions Whether the item has a caption file.
     * @param string $transcript The item's transcript.
     * @param string $component Component of the link.
     * @param string $filearea File area of the link.
     * @return array [filter, url, alias, context, course]
     */
    protected function setup_player(
        bool $captions = true,
        string $transcript = '',
        string $component = 'mod_label',
        string $filearea = 'intro'
    ): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        link_lookup::reset_cache();
        $course = $this->getDataGenerator()->create_course();
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id]);
        $context = \context_module::instance($label->cmid);
        $item = link_helper::create_item('talk.mp4', media_samples::get('mp4'));
        if ($captions) {
            $adminctx = \context_user::instance(get_admin()->id)->id;
            $draftid = file_get_unused_draft_itemid();
            $fs = get_file_storage();
            $fs->create_file_from_string([
                'contextid' => $adminctx, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
                'filepath' => '/', 'filename' => 'c.vtt',
            ], "WEBVTT\n\n00:00.000 --> 00:01.000\nHello\n");
            captions::save($item, $fs->get_file($adminctx, 'user', 'draft', $draftid, '/', 'c.vtt'));
        }
        if ($transcript !== '') {
            $item->merge_metadata(['transcript' => $transcript]);
        }
        $alias = link_helper::create_link($item, $context, $component, $filearea);
        $url = \moodle_url::make_pluginfile_url($context->id, $component, $filearea, 0, '/', $alias->get_filename())->out(false);
        return [new text_filter($context, []), $url, $alias, $context, $course];
    }

    /**
     * Run the filter's pre-clean stage on some HTML.
     *
     * @param text_filter $filter The filter.
     * @param string $html The HTML.
     * @return string
     */
    protected function apply(text_filter $filter, string $html): string {
        return $filter->filter_stage_pre_clean($html, ['originalformat' => FORMAT_HTML]);
    }

    /**
     * A player gets a captions track that is not on by default, and a transcript after it.
     */
    public function test_track_and_transcript_are_added(): void {
        [$filter, $url, $alias] = $this->setup_player(true, "Hello & welcome\n<b>Line two</b>");
        $out = $this->apply($filter, '<p><video controls><source src="' . s($url) . '" type="video/mp4"></video></p>');
        $track = '<track kind="captions" label="Captions" src="'
            . s(\contenttype_mediabank\link_captions::url_for($alias)->out(false)) . '">';
        $this->assertStringContainsString($track, $out);
        $this->assertStringNotContainsString('default', $out);
        // The track is inside the element, the transcript right after it.
        $this->assertLessThan(strpos($out, '</video>'), strpos($out, '<track'));
        $this->assertGreaterThan(strpos($out, '</video>'), strpos($out, '<details'));
        $this->assertStringContainsString('<summary>Transcript</summary>', $out);
        $this->assertStringContainsString('Hello &amp; welcome<br>' . "\n" . '&lt;b&gt;Line two&lt;/b&gt;', $out);
    }

    /**
     * Audio with the source on the element itself works the same way.
     */
    public function test_audio_with_a_src_attribute(): void {
        [$filter, $url] = $this->setup_player();
        $out = $this->apply($filter, '<audio controls src="' . s($url) . '"></audio>');
        $this->assertStringContainsString('<track kind="captions"', $out);
    }

    /**
     * Nothing is added to a player that already has a track, has no captions, or is not a bank link.
     */
    public function test_players_that_are_left_alone(): void {
        [$filter, $url, , $context] = $this->setup_player();
        $own = '<video><source src="' . s($url) . '"><track kind="captions" src="https://example.com/x.vtt"></video>';
        $this->assertSame($own, $this->apply($filter, $own));
        $outside = '<video><source src="https://example.com/a.mp4"></video>';
        $this->assertSame($outside, $this->apply($filter, $outside));
        $plain = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_label', 'filearea' => 'intro', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'plain.mp4',
        ], media_samples::get('mp4'));
        $plainurl = \moodle_url::make_pluginfile_url($context->id, 'mod_label', 'intro', 0, '/', 'plain.mp4')->out(false);
        $html = '<video><source src="' . s($plainurl) . '"></video>';
        $this->assertSame($html, $this->apply($filter, $html));
        $this->assertNotNull($plain);
        $this->assertSame('', $this->apply($filter, ''));
    }

    /**
     * An item with no captions gets no track, and one with only a transcript gets only the transcript.
     */
    public function test_no_captions_means_no_track(): void {
        [$filter, $url] = $this->setup_player(false);
        $html = '<video><source src="' . s($url) . '"></video>';
        $this->assertSame($html, $this->apply($filter, $html));
        [$filter, $url] = $this->setup_player(false, 'Only words');
        $out = $this->apply($filter, '<video><source src="' . s($url) . '"></video>');
        $this->assertStringNotContainsString('<track', $out);
        $this->assertStringContainsString('Only words', $out);
    }

    /**
     * A link from another context, a place not on the list and a converted copy are all left alone.
     */
    public function test_places_and_contexts_that_are_refused(): void {
        [$filter, $url, , , $course] = $this->setup_player();
        $html = '<video><source src="' . s($url) . '"></video>';
        // Text that belongs to another context cannot borrow the link.
        $other = new text_filter(\context_course::instance($course->id), []);
        $this->assertSame($html, $this->apply($other, $html));
        [$filter, $url] = $this->setup_player(true, '', 'mod_label', 'content');
        $html = '<video><source src="' . s($url) . '"></video>';
        $this->assertSame($html, $this->apply($filter, $html), 'Not a listed file area.');

        [$filter, $url, $alias, , $course] = $this->setup_player();
        $html = '<video><source src="' . s($url) . '"></video>';
        $this->assertNotSame($html, $this->apply($filter, $html));
        $item = \contenttype_mediabank\link_captions::item_of($alias);
        \contenttype_mediabank\usage::detach_in_course($item, (int) $course->id);
        link_lookup::reset_cache();
        $this->assertSame($html, $this->apply($filter, $html), 'A converted copy has no captions.');
    }

    /**
     * A player that never ends is left as it is, and hostile text cannot get a script attribute past the cleaner.
     */
    public function test_hostile_text(): void {
        [$filter, $url, , $context] = $this->setup_player(true, '<script>alert(1)</script>');
        $broken = '<video><source src="' . s($url) . '"> and more text';
        $this->assertSame($broken, $this->apply($filter, $broken));
        $this->assertSame('<video', $this->apply($filter, '<video'));

        filter_set_global_state('mediabank', TEXTFILTER_ON);
        $html = '<video title="x" data-a="</video>" src="' . s($url) . '" onloadstart="alert(2)"></video>'
            . '<audio><source src="' . s($url) . '"/></audio>';
        $cleaned = format_text($html, FORMAT_HTML, ['context' => $context, 'noclean' => false]);
        $this->assertStringNotContainsString('<script', $cleaned);
        $this->assertStringNotContainsString('onloadstart', $cleaned);
        $this->assertStringContainsString('&lt;script&gt;', $cleaned);
        $this->assertStringContainsString('<track', $cleaned, 'The cleaner keeps a caption track.');
        $this->assertStringContainsString('<details', $cleaned, 'The cleaner keeps the transcript.');
    }

    /**
     * Only the pre-clean stage does anything, and text without a bank link is not touched.
     */
    public function test_other_stages_do_nothing(): void {
        [$filter, $url] = $this->setup_player();
        $html = '<video><source src="' . s($url) . '"></video>';
        $this->assertSame($html, $filter->filter($html, []));
        $this->assertSame($html, $filter->filter_stage_post_clean($html, []));
        $this->assertSame($html, $filter->filter_stage_string($html, []));
        $this->assertSame('<p>No players here</p>', $this->apply($filter, '<p>No players here</p>'));
    }
}
