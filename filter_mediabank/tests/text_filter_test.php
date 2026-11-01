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
     * Make a bank item with a text alternative, link it into a course and return the filter, the file URL and the course.
     *
     * @param string $alt The text alternative of the item.
     * @return array [text_filter, url, course]
     */
    protected function setup_link(string $alt): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        alt_text::reset_cache();
        $course = $this->getDataGenerator()->create_course();
        $item = link_helper::create_item('pic.png', media_samples::get('png'));
        $item->merge_metadata(['alt' => $alt]);
        $context = \context_course::instance($course->id);
        $link = link_helper::create_link($item, $context, 'mod_label', 'intro');
        $url = \moodle_url::make_pluginfile_url($context->id, 'mod_label', 'intro', 0, '/', $link->get_filename())->out(false);
        return [new text_filter($context, []), $url, $course];
    }

    /**
     * A picture with no alt gets the item's text alternative.
     */
    public function test_missing_alt_is_filled(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $out = $filter->filter('<p><img src="' . $url . '" class="x"></p>', ['originalformat' => FORMAT_HTML]);
        $this->assertStringContainsString('alt="A red dot"', $out);
        $this->assertStringContainsString('class="x"', $out);
    }

    /**
     * An empty alt and an alt that is only the file name are replaced.
     */
    public function test_empty_and_filename_alt_are_replaced(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        foreach (['alt=""', "alt='pic.png'", 'alt="PIC"'] as $attribute) {
            $out = $filter->filter('<img ' . $attribute . ' src="' . $url . '" />', ['originalformat' => FORMAT_HTML]);
            $this->assertStringContainsString('alt="A red dot"', $out, $attribute);
            $this->assertStringNotContainsString($attribute === 'alt=""' ? 'alt="" ' : $attribute, $out);
        }
    }

    /**
     * What a teacher wrote, and a decorative picture, are left as they are.
     */
    public function test_teacher_alt_and_decorative_are_kept(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $mine = '<img alt="My own words" src="' . $url . '">';
        $this->assertSame($mine, $filter->filter($mine, ['originalformat' => FORMAT_HTML]));
        $decorative = '<img alt="" role="presentation" src="' . $url . '">';
        $this->assertSame($decorative, $filter->filter($decorative, ['originalformat' => FORMAT_HTML]));
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
            $this->assertSame($html, $filter->filter($html, ['originalformat' => FORMAT_HTML]));
        }
        $this->assertSame('plain text', $filter->filter('plain text', ['originalformat' => FORMAT_HTML]));
    }

    /**
     * Text that only looks like an alt attribute inside another attribute's value is never rewritten.
     */
    public function test_attribute_lookalikes_inside_other_values_are_ignored(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $trap = '<img title="alt=\'\' onerror=x" src="' . $url . '">';
        $out = $filter->filter($trap, ['originalformat' => FORMAT_HTML]);
        // The real alt is added as a new attribute and the title is untouched, so onerror stays inside the title value.
        $this->assertStringContainsString('title="alt=\'\' onerror=x"', $out);
        $this->assertStringContainsString(' alt="A red dot"', $out);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $out);
        $image = $dom->getElementsByTagName('img')->item(0);
        $this->assertFalse($image->hasAttribute('onerror'));
        $this->assertSame("alt='' onerror=x", $image->getAttribute('title'));

        // The same with the lookalike in a data attribute that comes after the real, empty alt.
        $out = $filter->filter('<img alt="" data-x="alt=\'z\'" src="' . $url . '">', ['originalformat' => FORMAT_HTML]);
        $this->assertStringContainsString('data-x="alt=\'z\'"', $out);
        $this->assertStringContainsString('alt="A red dot"', $out);
    }

    /**
     * Quoting styles, a ">" inside a value, and a repeated alt.
     */
    public function test_attribute_parsing(): void {
        [$filter, $url] = $this->setup_link('A red dot');
        $options = ['originalformat' => FORMAT_HTML];
        $this->assertStringContainsString('alt="A red dot"', $filter->filter("<img src='$url' alt=''>", $options));
        $this->assertStringContainsString('alt="A red dot"', $filter->filter("<img src=$url alt=>", $options));
        $out = $filter->filter('<img title="a > b" src="' . $url . '">', $options);
        $this->assertStringContainsString('title="a > b"', $out);
        $this->assertStringContainsString('alt="A red dot"', $out);
        // The first alt is the one a browser uses, and it is the teacher's.
        $kept = '<img alt="Mine" alt="" src="' . $url . '">';
        $this->assertSame($kept, $filter->filter($kept, $options));
        // An uppercase attribute name is still an attribute name.
        $this->assertStringContainsString('alt="A red dot"', $filter->filter('<IMG SRC="' . $url . '" ALT="">', $options));
        // A tag that never ends, or has an unfinished quote, is left alone.
        $broken = '<img src="' . $url . '" title="open';
        $this->assertSame($broken, $filter->filter($broken, $options));
    }

    /**
     * The text alternative is escaped.
     */
    public function test_alt_is_escaped(): void {
        [$filter, $url] = $this->setup_link('"><script>alert(1)</script>');
        $out = $filter->filter('<img src="' . $url . '">', ['originalformat' => FORMAT_HTML]);
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    /**
     * Through format_text, when the filter is switched on, with an HTML-escaped URL as the editor saves it.
     */
    public function test_works_through_format_text(): void {
        [, $url, $course] = $this->setup_link('A red dot');
        filter_set_global_state('mediabank', TEXTFILTER_ON);
        $context = \context_course::instance($course->id);
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
        $this->assertSame($html, $filter->filter($html, ['originalformat' => FORMAT_HTML]));
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
