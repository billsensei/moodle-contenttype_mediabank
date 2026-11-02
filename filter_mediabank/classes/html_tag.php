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
 * Reads a start tag the way a browser's HTML tokenizer does.
 *
 * @package    filter_mediabank
 * @copyright  2026 SenseiMoodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_tag {
    /** @var string The characters HTML treats as white space inside a tag. Not PCRE's \s, which also has a vertical tab. */
    public const SPACE = "\t\n\f\r ";

    /**
     * Read the attributes of a start tag the way the HTML tokenizer does, from just after "<img" to the closing ">".
     *
     * A quote only starts a value straight after "=". Anywhere else it is an ordinary character of a name. Names are
     * lower-cased and a repeated name keeps its first use, as in a browser. Values are raw, with their entities.
     *
     * @param string $text The text holding the tag.
     * @param int $pos Where to start, just after "<img".
     * @return array|null ['attributes' => name => ['value' => string|null, 'start' => int, 'end' => int], 'end' => the
     *      position after ">"], or null if the text ends before the tag does.
     */
    public static function parse(string $text, int $pos): ?array {
        $length = strlen($text);
        $attributes = [];
        while (true) {
            // Before an attribute name.
            while ($pos < $length && ($text[$pos] === '/' || str_contains(self::SPACE, $text[$pos]))) {
                $pos++;
            }
            if ($pos >= $length) {
                return null;
            }
            if ($text[$pos] === '>') {
                return ['attributes' => $attributes, 'end' => $pos + 1];
            }
            // The attribute name. Its first character counts whatever it is, even "=".
            $start = $pos++;
            while ($pos < $length && !str_contains(self::SPACE . '/>=', $text[$pos])) {
                $pos++;
            }
            $name = strtolower(substr($text, $start, $pos - $start));
            $value = null;
            $end = $pos;
            $look = $pos;
            while ($look < $length && str_contains(self::SPACE, $text[$look])) {
                $look++;
            }
            if ($look < $length && $text[$look] === '=') {
                $look++;
                while ($look < $length && str_contains(self::SPACE, $text[$look])) {
                    $look++;
                }
                if ($look >= $length) {
                    return null;
                }
                $quote = $text[$look];
                if ($quote === '"' || $quote === "'") {
                    $close = strpos($text, $quote, $look + 1);
                    if ($close === false) {
                        return null;
                    }
                    $value = substr($text, $look + 1, $close - $look - 1);
                    $pos = $close + 1;
                } else {
                    $close = $look;
                    while ($close < $length && $text[$close] !== '>' && !str_contains(self::SPACE, $text[$close])) {
                        $close++;
                    }
                    $value = substr($text, $look, $close - $look);
                    $pos = $close;
                }
                $end = $pos;
            }
            if (!isset($attributes[$name])) {
                $attributes[$name] = ['value' => $value, 'start' => $start, 'end' => $end];
            }
        }
    }
}
