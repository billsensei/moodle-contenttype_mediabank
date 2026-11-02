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

use contenttype_mediabank\tests\link_helper;
use contenttype_mediabank\tests\media_samples;

/**
 * Tests for the media bank text alternative filter.
 *
 * @package    filter_mediabank
 * @category   test
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(text_filter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(alt_text::class)]
final class text_filter_test extends \advanced_testcase {
    /**
     * Make a bank item with a text alternative, link it into a label activity and return the filter, the file URL and the course.
     *
     * @param string $alt The text alternative of the item.
     * @param string $component Component the link belongs to.
     * @param string $filearea File area the link is in.
     * @return array [text_filter, url, course, module context]
     */
    protected function setup_link(string $alt, string $component = 'mod_label', string $filearea = 'intro'): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        alt_text::reset_cache();
        $course = $this->getDataGenerator()->create_course();
        $item = link_helper::create_item('pic.png', media_samples::get('png'));
        $item->merge_metadata(['alt' => $alt]);
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id]);
        $context = \context_module::instance($label->cmid);
        $link = link_helper::create_link($item, $context, $component, $filearea);
        $url = \moodle_url::make_pluginfile_url($context->id, $component, $filearea, 0, '/', $link->get_filename())->out(false);
        return [new text_filter($context, []), $url, $course, $context];
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
     * A picture with no alt gets the item's text alternative.
     */
    public function test_missing_alt_is_filled(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $out = $this->apply($filter, '<p><img src="' . $url . '" class="x"></p>');
        $this->assertStringContainsString('alt="A red dot"', $out);
        $this->assertStringContainsString('class="x"', $out);
    }

    /**
     * An empty alt and an alt that is only the file name are replaced.
     */
    public function test_empty_and_filename_alt_are_replaced(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        foreach (['alt=""', "alt='pic.png'", 'alt="PIC"'] as $attribute) {
            $out = $this->apply($filter, '<img ' . $attribute . ' src="' . $url . '" />');
            $this->assertStringContainsString('alt="A red dot"', $out, $attribute);
            $this->assertStringNotContainsString($attribute === 'alt=""' ? 'alt="" ' : $attribute, $out);
        }
    }

    /**
     * Text in another context cannot borrow the text alternative of an item linked somewhere else.
     */
    public function test_alt_is_not_taken_from_a_link_in_another_context(): void {
        [, $url] = $this->setup_link('A red dot');
        $other = $this->getDataGenerator()->create_course();
        $filter = new text_filter(\context_course::instance($other->id), []);
        $html = '<img src="' . $url . '">';
        $this->assertSame($html, $this->apply($filter, $html));
    }

    /**
     * A link in a place that is not on the list gives no text alternative, even in the text's own context.
     */
    public function test_alt_is_not_taken_from_a_link_in_a_place_that_is_not_allowed(): void {
        [$filter, $url] = $this->setup_link('A red dot', 'mod_label', 'content');
        $html = '<img src="' . $url . '">';
        $this->assertSame($html, $this->apply($filter, $html));
    }

    /**
     * What a teacher wrote, and a decorative picture, are left as they are.
     */
    public function test_teacher_alt_and_decorative_are_kept(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $mine = '<img alt="My own words" src="' . $url . '">';
        $this->assertSame($mine, $this->apply($filter, $mine));
        $decorative = '<img alt="" role="presentation" src="' . $url . '">';
        $this->assertSame($decorative, $this->apply($filter, $decorative));
    }

    /**
     * Pictures that are not links to a bank item are not touched, and an item with no alt changes nothing.
     */
    public function test_other_pictures_are_not_touched(): void {
        [$filter, $url, $course] = $this->setup_link('');
        $context = \context_course::instance($course->id);
        $plain = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_label', 'filearea' => 'intro', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'plain.png',
        ], media_samples::get('png'));
        $plainurl = \moodle_url::make_pluginfile_url($context->id, 'mod_label', 'intro', 0, '/', $plain->get_filename());
        $plainurl = $plainurl->out(false);
        foreach (['<img src="' . $plainurl . '">', '<img src="https://example.com/a.png">', '<img src="' . $url . '">'] as $html) {
            $this->assertSame($html, $this->apply($filter, $html));
        }
        $this->assertSame('plain text', $this->apply($filter, 'plain text'));
    }

    /**
     * Text that only looks like an alt attribute inside another attribute's value is never rewritten.
     */
    public function test_attribute_lookalikes_inside_other_values_are_ignored(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $trap = '<img title="alt=\'\' onerror=x" src="' . $url . '">';
        $out = $this->apply($filter, $trap);
        // The real alt is added as a new attribute and the title is untouched, so onerror stays inside the title value.
        $this->assertStringContainsString('title="alt=\'\' onerror=x"', $out);
        $this->assertStringContainsString(' alt="A red dot"', $out);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $out);
        $image = $dom->getElementsByTagName('img')->item(0);
        $this->assertFalse($image->hasAttribute('onerror'));
        $this->assertSame("alt='' onerror=x", $image->getAttribute('title'));

        // The same with the lookalike in a data attribute that comes after the real, empty alt.
        $out = $this->apply($filter, '<img alt="" data-x="alt=\'z\'" src="' . $url . '">');
        $this->assertStringContainsString('data-x="alt=\'z\'"', $out);
        $this->assertStringContainsString('alt="A red dot"', $out);
    }

    /**
     * Quoting styles, a ">" inside a value, and a repeated alt.
     */
    public function test_attribute_parsing(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $options = ['originalformat' => FORMAT_HTML];
        $this->assertStringContainsString('alt="A red dot"', $this->apply($filter, "<img src='$url' alt=''>"));
        $this->assertStringContainsString('alt="A red dot"', $this->apply($filter, "<img src=$url alt=>"));
        $out = $this->apply($filter, '<img title="a > b" src="' . $url . '">');
        $this->assertStringContainsString('title="a > b"', $out);
        $this->assertStringContainsString('alt="A red dot"', $out);
        // The first alt is the one a browser uses, and it is the teacher's.
        $kept = '<img alt="Mine" alt="" src="' . $url . '">';
        $this->assertSame($kept, $this->apply($filter, $kept));
        // An uppercase attribute name is still an attribute name.
        $this->assertStringContainsString('alt="A red dot"', $this->apply($filter, '<IMG SRC="' . $url . '" ALT="">'));
        // A tag that never ends, or has an unfinished quote, is left alone.
        $broken = '<img src="' . $url . '" title="open';
        $this->assertSame($broken, $this->apply($filter, $broken));
    }

    /**
     * White space is the HTML set, not PCRE's: a vertical tab does not separate an attribute from its value.
     *
     * By the HTML rules the input here already has a real onerror attribute. The filter must read it that way, change
     * nothing but add the one alt, and the cleaner must then remove the attribute.
     */
    public function test_vertical_tab_is_not_white_space(): void {
        [$filter, $url, $course] = $this->setup_link('A red dot');
        $html = "<img title=\x0b\"alt='' onerror=x\" src=\"$url\">";
        $out = $this->apply($filter, $html);
        $this->assertSame($html, str_replace(' alt="A red dot"', '', $out));
        $this->assertSame(1, substr_count($out, 'alt="A red dot"'));
        filter_set_global_state('mediabank', TEXTFILTER_ON);
        $cleaned = format_text($html, FORMAT_HTML, ['context' => \context_course::instance($course->id), 'noclean' => false]);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $cleaned);
        $this->assertFalse($dom->getElementsByTagName('img')->item(0)->hasAttribute('onerror'));
    }

    /**
     * A quote that does not follow "=" is part of an attribute name, as in a browser, and does not hide the real alt.
     */
    public function test_stray_quote_does_not_swallow_attributes(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $out = $this->apply($filter, '<img a"b src="' . $url . '" alt=""> <b title="x">');
        $this->assertStringContainsString('alt="A red dot"', $out);
        $this->assertStringContainsString('<b title="x">', $out);
    }

    /**
     * Only the pre-clean stage changes anything, so for untrusted text the cleaner sees what the filter produced.
     */
    public function test_only_the_pre_clean_stage_changes_text(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $html = '<img src="' . $url . '">';
        $this->assertSame($html, $filter->filter($html, []));
        $this->assertSame($html, $filter->filter_stage_post_clean($html, []));
        $this->assertSame($html, $filter->filter_stage_string($html, []));
        $this->assertNotSame($html, $filter->filter_stage_pre_clean($html, []));
    }

    /**
     * Whatever the filter does to a hostile tag, untrusted text is cleaned afterwards, so no script attribute survives.
     */
    public function test_untrusted_text_is_cleaned_after_the_filter(): void {
        [, $url, , $context] = $this->setup_link('A red dot');
        filter_set_global_state('mediabank', TEXTFILTER_ON);
        $html = '<img title="alt=\'\' onerror=x" src="' . s($url) . '"><img a"b onerror=y src="' . s($url) . '" alt="">';
        $out = format_text($html, FORMAT_HTML, ['context' => $context, 'noclean' => false, 'trusted' => false]);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $out);
        foreach ($dom->getElementsByTagName('img') as $image) {
            $this->assertFalse($image->hasAttribute('onerror'));
            $this->assertSame('A red dot', $image->getAttribute('alt'));
        }
        $this->assertSame(2, $dom->getElementsByTagName('img')->length);
    }

    /**
     * The text alternative is escaped.
     */
    public function test_alt_is_escaped(): void {
        [$filter, $url] = $this->setup_link('"><script>alert(1)</script>');
        $out = $this->apply($filter, '<img src="' . $url . '">');
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    /**
     * Through format_text, when the filter is switched on, with an HTML-escaped URL as the editor saves it.
     */
    public function test_works_through_format_text(): void {
        [, $url, , $context] = $this->setup_link('A red dot');
        filter_set_global_state('mediabank', TEXTFILTER_ON);
        $html = '<p><img src="' . s($url) . '" alt=""></p>';
        $this->assertStringContainsString('alt="A red dot"', format_text($html, FORMAT_HTML, ['context' => $context]));
    }

    /**
     * A converted link is an ordinary file again and the filter leaves it alone.
     */
    public function test_converted_copy_is_not_touched(): void {
        [$filter, $url, $course] = $this->setup_link('A red dot');
        $items = \contenttype_mediabank\usage::detach_in_course(
            (new \core_contentbank\contentbank())->get_content_from_id(
                (int) $this->get_item_id()
            ),
            (int) $course->id
        );
        $this->assertSame(1, $items);
        alt_text::reset_cache();
        $html = '<img src="' . $url . '">';
        $this->assertSame($html, $this->apply($filter, $html));
    }

    /**
     * The id of the only media bank item.
     *
     * @return int
     */
    protected function get_item_id(): int {
        global $DB;
        return (int) $DB->get_field('contentbank_content', 'id', ['contenttype' => 'contenttype_mediabank'], MUST_EXIST);
    }
}
