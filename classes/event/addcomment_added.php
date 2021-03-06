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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * The add Comment	Event
 *
 * @package Emarking
 * @copyright 2015 Xiu-Fong Lin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_emarking\event;

defined ( 'MOODLE_INTERNAL' ) || die ();
/**
 * The add Comment	Event
 *
 * @property-read array $other {
 *                adds a comment
 *                }
 *               
 * @since Moodle 2.8.2
 * @copyright 2015 Xiu-Fong Lin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *         
 */
class addcomment_added extends \core\event\base {
	protected function init() {
		$this->data ['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
		$this->data ['edulevel'] = self::LEVEL_PARTICIPATING;
		$this->data ['objecttable'] = 'emarking_submission';
	}
	public static function get_name() {
		return get_string ( 'eventaddcommentadded', 'mod_emarking' );
	}
	public function get_description() {
		return "The user with id {$this->userid} added a comment to this item: {$this->objectid}.";
	}
	public function get_legacy_logdata() {
		// Override if you are migrating an add_to_log() call.
		return array (
				$this->courseid,
				'emarking',
				'added',
				$this->objectid,
				$this->contextinstanceid 
		);
	}
}