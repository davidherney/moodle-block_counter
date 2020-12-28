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
 * Counter block settings.
 *
 * @package   block_counter
 * @copyright 2017 David Herney Bernal - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $newsetting = new admin_setting_configtext('delay', get_string('counter_delay', 'block_counter'),
                       get_string('counter_delay_key', 'block_counter'), '14400', PARAM_TEXT);
    $newsetting->plugin = 'block_counter';
    $settings->add($newsetting);

    $newsetting = new admin_setting_configtext('sizepad', get_string('counter_sizepad', 'block_counter'),
                       get_string('counter_sizepad_key', 'block_counter'), '', PARAM_TEXT);
    $newsetting->plugin = 'block_counter';
    $settings->add($newsetting);

    $newsetting = new admin_setting_configcheckbox('displaydate', get_string('counter_displaydate', 'block_counter'),
                       get_string('counter_displaydate_key', 'block_counter'), 1, 1, 0);
    $newsetting->plugin = 'block_counter';
    $settings->add($newsetting);

    for ($i = 0; $i < 10; $i++) {
        $newsetting = new admin_setting_configstoredfile('number' . $i,
            get_string('number', 'block_counter', $i), get_string('number_key', 'block_counter', $i), 'number', $i);
        $newsetting->plugin = 'block_counter';
        $settings->add($newsetting);
    }
}
