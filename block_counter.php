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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block Visitor counter.
 *
 * @since     2.0
 * @package   block_counter
 * @copyright 2017 David Herney Bernal - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main class to manage the counter block.
 *
 * @package   block_counter
 * @copyright 2017 David Herney - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_counter extends block_base {

    /**
     * Initial method.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_counter');
    }

    /**
     * If the block has general configurations.
     * @return bool True. Has configuration
     */
    public function has_config() {
      return true;
    }

    /**
     * The block can be used in all pages.
     * @return array Formats
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * The block html content.
     * @return stdClass text and footer html
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $CFG, $USER, $DB, $SESSION;

        $course = $this->page->course;

        if ($course == null || !is_object($course) || $course->id == 0) {
            $course->id = (-1) * $USER->id;
        }

        if (!isset($SESSION->block_counter)) {
            $SESSION->block_counter = array();
        }

        if (!isset($SESSION->block_counter[$course->id])) {
            $SESSION->block_counter[$course->id] = array();
        }

        $ip = getremoteaddr();
        $blockconfig = get_config('block_counter');

        // Seconds between a same IP (86400 = 1 day).
        if (isset($blockconfig->delay)) {
            $difference = $blockconfig->delay;
        } else {
            $difference = 14400;
        }

        if (!isset($SESSION->block_counter[$course->id]['time'])) {
            $sql = "SELECT MAX(time) AS mintime FROM {block_counter} " .
                " WHERE course = {$course->id} AND ip = '$ip'";

            $time = $DB->get_record_sql($sql);

            $SESSION->block_counter[$course->id]['time'] = $time && $time->mintime ? $time->mintime : 0;
        }

        $increase = false;
        if ($SESSION->block_counter[$course->id]['time'] < (time() - $difference)) {
            $dataobject = new stdClass();
            $dataobject->ip = $ip;
            $dataobject->course = $course->id;
            $dataobject->time = time();
            $DB->insert_record('block_counter', $dataobject, false);
            $SESSION->block_counter[$course->id]['time'] = time();
            $increase = true;
        }

        $stats = $DB->get_record('block_counter_stats', array('course' => $course->id));

        if (!$stats) {
            $stats = new stdClass();
            $stats->course = $course->id;
            $stats->time = time();
            $stats->currenttime = 0;
            $stats->sum = 0;
            $stats->id = $DB->insert_record('block_counter_stats', $stats, true);
        }

        // Count this visit.
        if ($increase) {
            $stats->sum++;
        }

        $count = $stats->sum;

        if (!empty($blockconfig->sizepad)) {
            $count = str_pad($count, $blockconfig->sizepad, '0', STR_PAD_LEFT);
        }

        $syscontext = context_system::instance();

        $text = '<div class="block_counter_numbers" >';
        for ($i = 0; $i < strlen($count); $i++) {
            $tok = substr ($count, $i, 1);

            $configvar = 'number' . $tok;
            $filepath = $blockconfig->$configvar;

            if (empty($filepath)) {
                $text .= '<span class="block_counter_number">' . $tok . '</span>';
            } else {
                $filearea = 'number';
                $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                    "/$syscontext->id/block_counter/$filearea/$tok" . $filepath);

                $text .= "<img src='$url' alt='$tok' />";
            }

        }
        $text .= "</div>";

        $this->content = new stdClass;
        $this->content->text = $text;

        if (!empty($blockconfig->displaydate)) {
            $a = strftime(get_string('strftimedate'), $stats->time);
            $this->content->footer = get_string('timecounter', 'block_counter', $a);
        }

        if ($increase) {
            // Update counter stats with this visit.
            $stats->currenttime = time();
            $DB->update_record('block_counter_stats', $stats);
        }

        return $this->content;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        global $CFG;

        $blockconfig = get_config('block_counter');

        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = (object) [
            'delay' => $blockconfig->delay,
            'sizepad' => $blockconfig->sizepad,
            'displaydate' => $blockconfig->displaydate
        ];

        return (object) [
            'instance' => new stdClass(),
            'plugin' => $configs,
        ];
    }

}
