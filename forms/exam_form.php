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
 *
 * @package mod
 * @subpackage emarking
 * @copyright 2012 Marcelo Epuyao, Jorge Villalón {@link http://www.uai.cl}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once ($CFG->libdir . '/formslib.php'); // putting this is as a safety as i got a class not found error.
require_once ($CFG->libdir . '/enrollib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->dirroot . '/mod/emarking/locallib.php');
class emarking_exam_form extends moodleform {
	
	// Extra HTML to be added at the end of the form, used for javascript functions.
	private $extraScript = "";
	function definition() {
		global $DB, $CFG;
		
		// Verifies that the global configurated logo exists, and if it's new it copies it
		// to a normal space within Moodle's filesystem
		emarking_verify_logo ();
		
		$mform = $this->_form;
		$instance = $this->_customdata;
		
		$cmid = $instance ['cmid'];
		$courseid = $instance ['courseid'];
		$examid = $instance ['examid'];
		
		// Multicourse
		// Get the course record to get the shortname
		$course = $DB->get_record ( 'course', array (
				'id' => $courseid 
		) );
		$exam = $DB->get_record ( 'emarking_exams', array (
				'id' => $examid 
		) );
		
		// Exam id goes hidden
		$mform->addElement ( 'hidden', 'id', $examid );
		$mform->setType ( 'id', PARAM_INT );
		
		// Course module id goes hidden as well
		$mform->addElement ( 'hidden', 'cm', $cmid );
		$mform->setType ( 'cm', PARAM_INT );
		
		// Course id goes hidden as well
		$mform->addElement ( 'hidden', 'course', $courseid );
		$mform->setType ( 'course', PARAM_INT );
		
		// Exam totalpages goes hidden as well
		$mform->addElement ( 'hidden', 'totalpages' );
		$mform->setType ( 'totalpages', PARAM_INT );
		
		$mform->addElement ( 'header', 'exam_title', get_string ( 'examinfo', 'mod_emarking' ) );
		
		$mform->addElement ( 'static', 'coursename', get_string ( 'course' ), $course->fullname );
		
		// Exam name
		$mform->addElement ( 'text', 'name', get_string ( 'examname', 'mod_emarking' ) );
		$mform->addRule ( 'name', get_string ( 'required' ), 'required', null, 'client' );
		$mform->addRule ( 'name', get_string ( 'maximumchars', '', 50 ), 'maxlength', 50, 'client' );
		$mform->setType ( 'name', PARAM_TEXT );
		$mform->addHelpButton ( 'name', 'examname', 'mod_emarking' );
		
		$date = new DateTime ();
		$date->setTimestamp ( usertime ( time () ) );
		$date->modify ( '+2 days' );
		$date->modify ( '+10 minutes' );
		
		// Exam date
		
		$mform->addElement ( 'date_time_selector', 'examdate', get_string ( 'examdate', 'mod_emarking' ), array (
				'startyear' => date ( 'Y' ),
				'stopyear' => date ( 'Y' ) + 1,
				'step' => 5,
				'defaulttime' => $date->getTimestamp (),
				'optional' => false 
		), $instance ['options'] );
		$mform->addRule ( 'examdate', get_string ( 'filerequiredpdf', 'mod_emarking' ), 'required', null, 'client' );
		$mform->addHelpButton ( 'examdate', 'examdate', 'mod_emarking' );
		
		// Exam PDF file
		$mform->addElement ( 'filemanager', 'exam_files', get_string ( 'pdffile', 'mod_emarking' ), null, 
							array('subdirs'=>0, 'maxbytes'=>0, 'maxfiles'=>10, 'accepted_types' => array('.pdf'), 
							 'return_types'=> FILE_INTERNAL));
		
		//if ($examid == 0)
	    //se omite el if, ya que al editar el examen emite error
		$mform->addRule ( 'exam_files', get_string ( 'filerequiredtosendnewprintorder', 'mod_emarking' ), 'required', null, 'client' );
		
		$mform->setType ( 'exam_files', PARAM_FILE );
		$mform->addHelpButton ( 'exam_files', 'pdffile', 'mod_emarking' );
		
		// eMarking configuration
		$mform->addElement ( 'header', 'emarking_title', get_string ( 'emarking', 'mod_emarking' ) );
		
		// Personalized header (using QR)
		$mform->addElement ( 'checkbox', 'headerqr', get_string ( 'headerqr', 'mod_emarking' ) );
		// $mform->addElement('hidden','headerqr');
		$mform->setType ( 'headerqr', PARAM_BOOL );
		$mform->addHelpButton ( 'headerqr', 'headerqr', 'mod_emarking' );
		$mform->setDefault ( 'headerqr', true );
		
		// Enrolment methods to include in printing
		$enrolcheckboxes = array ();
		$enrolavailables = array ();
		$enrolments = enrol_get_instances ( $courseid, true );
		$flag = 0;
		foreach ( $enrolments as $enrolment ) {
			if ($enrolment->enrol == "meta") {
				if ($flag == 0) {
					$flag = 1;
					$enrolavailables [] = $enrolment->enrol;
					$enrolcheckboxes [] = $mform->createElement ( 'checkbox', $enrolment->enrol, null, get_string ( 'enrol' . $enrolment->enrol, 'mod_emarking' ), 'checked' );
				}
			} else {
				$enrolavailables [] = $enrolment->enrol;
				$enrolcheckboxes [] = $mform->createElement ( 'checkbox', $enrolment->enrol, null, get_string ( 'enrol' . $enrolment->enrol, 'mod_emarking' ), 'checked' );
			}
		}
		
		$mform->addGroup ( $enrolcheckboxes, 'enrolments', get_string ( 'includestudentsinexam', 'mod_emarking' ), array (
				'<br/>' 
		), true );
		
		if ($CFG->emarking_enrolincludes && strlen ( $CFG->emarking_enrolincludes ) > 1) {
			$enrolincludes = explode ( ",", $CFG->emarking_enrolincludes );
			foreach ( $enrolincludes as $enroldefault ) {
				if (in_array ( $enroldefault, $enrolavailables )) {
					$mform->setDefault ( "enrolments[$enroldefault]", true );
				}
			}
		}
		
		if ($CFG->emarking_enableprintingrandom) {
			// Print Random
			$mform->addElement ( 'checkbox', 'printrandom', get_string ( 'printrandom', 'mod_emarking' ) );
			$mform->setType ( 'printrandom', PARAM_BOOL );
			$mform->addHelpButton ( 'printrandom', 'printrandom', 'mod_emarking' );
			$mform->setDefault ( 'printrandom', false );
		}
		
		if ($CFG->emarking_enableprintinglist) {
			// Print Random
			$mform->addElement ( 'checkbox', 'printlist', get_string ( 'printlist', 'mod_emarking' ) );
			$mform->setType ( 'printlist', PARAM_BOOL );
			$mform->addHelpButton ( 'printlist', 'printlist', 'mod_emarking' );
			$mform->setDefault ( 'printlist', false );
		}
		
		// Copy center instructions
		$mform->addElement ( 'header', 'exam_title', get_string ( 'copycenterinstructions', 'mod_emarking' ) );
		
		// Numbers from 0 to 14 for extra exams and sheets
		$numberarray = array ();
		for($j = 0; $j < 3; $j ++) {
			$numberarray [$j] = $j;
		}
		
		// Extra sheets per student
		$mform->addElement ( 'select', 'extrasheets', get_string ( 'extrasheets', 'mod_emarking' ), $numberarray, null );
		$mform->addHelpButton ( 'extrasheets', 'extrasheets', 'mod_emarking' );
		
		// Extra students
		$mform->addElement ( 'select', 'extraexams', get_string ( 'extraexams', 'mod_emarking' ), $numberarray, null );
		$mform->addHelpButton ( 'extraexams', 'extraexams', 'mod_emarking' );
		
		// print double sided
		// $mform->addElement('checkbox','printdoublesided',get_string('printdoublesided', 'mod_emarking')));
		$mform->addElement ( 'hidden', 'printdoublesided' );
		$mform->setType ( 'printdoublesided', PARAM_BOOL );
		// $mform->addHelpButton('printdoublesided', 'printdoublesided', 'mod_emarking');
		$mform->setDefault ( 'printdoublesided', false );
		
		// Obtain parallel courses
		if ($seccionesparalelas = emarking_get_parallel_courses ( $course, $CFG->emarking_parallelregex )) {
			// Add a checkbox for each parallel course
			$checkboxes = array ();
			foreach ( $seccionesparalelas as $cid => $course ) {
				$checkboxes [] = $mform->createElement ( 'checkbox', $course->shortname, null, $course->fullname, 'checked' );
			}
			
			// If there's any parallel course we add the multicourse option
			if (count ( $checkboxes ) > 0) {
				$mform->addGroup ( $checkboxes, 'multisecciones', get_string ( 'multicourse', 'mod_emarking' ), array (
						'<br/>' 
				), true );
				$mform->addHelpButton ( 'multisecciones', 'multicourse', 'mod_emarking' );
				if ($examid == 0) {
					$mform->addElement ( 'button', 'selectall', get_string ( 'selectall', 'mod_emarking' ), array (
							
							'onClick' => 'selectAllCheckboxes(this.form,true);' 
					) );
					
					$mform->addElement ( 'button', 'deselectall', get_string ( 'selectnone', 'mod_emarking' ), array (
							
							'onClick' => 'selectAllCheckboxes(this.form,false);' 
					) );
				} else {
					foreach ( $seccionesparalelas as $cid => $course ) {
						
						$selected = false;
						if ($examid > 0 && $parallel = $DB->get_record ( 'emarking_exams', array (
								'file' => $exam->file,
								'course' => $cid 
						) )) {
							$selected = true;
						}
						$mform->setType ( "multisecciones[$course->shortname]", PARAM_BOOL );
						if ($selected) {
							$mform->setDefault ( "multisecciones[$course->shortname]", true );
						}
					}
				}
			}
			
			$this->extraScript .= "<script>function selectAllCheckboxes(form,checked) { " . "for (var i = 0; i < form.elements.length; i++ ) { " . "    if (form.elements[i].type == 'checkbox' && form.elements[i].id.indexOf('multiseccion') > 0) { " . "        form.elements[i].checked = checked; " . "    } " . "} " . "}</script>";
		}
		
		$mform->addElement ( 'hidden', 'action', 'uploadfile' );
		$mform->setType ( 'action', PARAM_ALPHA );
		
		// buttons
		$this->add_action_buttons ( true, get_string ( 'submit' ) );
	}
	function validation($data, $files) {
		global $CFG;
		
		// echo date("M-d-Y - h:m:s",time());
		$errors = array ();
		/*
		$date = usertime ( time () );
		
		$today = new DateTime ();
		$today->setTimestamp ( $date );
		
		$examdate = new DateTime ();
		$examdate->setTimestamp ( $data ['examdate'] );
		
		$mindiff = 2;
		if (isset ( $CFG->emarking_minimumdaysbeforeprinting )) {
			$mindiff = intval ( $CFG->emarking_minimumdaysbeforeprinting );
		}
		
		$dw = date ( "w", $examdate->getTimestamp () );
		$dh = date ( "G", $examdate->getTimestamp () );
		$dwt = date ( "w", $today->getTimestamp () );
		
		$diasRestantes = $dw - $dwt;
		
		if ($diasRestantes < 2) {
			$a = new stdClass ();
			$a->mindays = $mindiff;
			$errors ['examdate'] = get_string ( 'examdateinvalid', 'mod_emarking', $a );
			return $errors;
		}
		
		// Sundays are forbidden, saturdays from 6am to 9pm
		if ($dw == 0 || ($dw == 6 && ($dh < 6 || $dh > 15))) {
			$a = new stdClass ();
			$a->mindays = $mindiff;
			$errors ['examdate'] = get_string ( 'examdateinvalid', 'mod_emarking', $a );
			return $errors;
		}*/
		
		/*
		 * $printdate = clone $examdate; $diff = 0; while ( $diff < $mindiff ) { $printdate->modify ( '-24 hour' ); $dw = date ( "w", $printdate->getTimestamp () ); if ($dw == 0) $dw = 7; if ($dw < 6) $diff ++; } $diff = $printdate->diff ( $today ); if ($diff->invert == 1) { } else { $a = new stdClass(); $a->mindays = $mindiff; $errors ['examdate'] = get_string('examdateinvalid','mod_emarking', $a ); }
		 */
		
		if ($CFG->emarking_enableprintingrandom) {
			if ($data ["printrandom"] == 1) {
				$rs = emarking_get_groups_for_printing ( $data ["course"] );
				$result = array ();
				
				foreach ( $rs as $r ) {
					$result [] = $r;
				}
				
				if (empty ( $result )) {
					$errors ['printrandom'] = get_string ( 'printrandominvalid', 'mod_emarking' );
					return $errors;
				}
			}
		}
		
		return $errors;
	}
	function display() {
		parent::display ();
		echo $this->extraScript;
	}
}
