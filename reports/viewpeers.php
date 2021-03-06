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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG,$OUTPUT, $PAGE, $DB;//To suppress eclipse warnings

require_once($CFG->dirroot.'/mod/emarking/locallib.php');

$cmid = required_param('id', PARAM_INT);

if(!$cm = get_coursemodule_from_id('emarking', $cmid)) {
	error('Invalid cm id');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
	error('You must specify a valid course ID');
}

if(!$emarking = $DB->get_record('emarking', array('id'=>$cm->instance))) {
	print_error('Invalid activity');
}

if(!$submission = $DB->get_record('emarking_submission', array('emarking'=>$emarking->id, 'student'=>$USER->id))) {
	print_error('Invalid submission');
}

require_login($course, true);

$url = new moodle_url("/mod/emarking/reports/viewpeers.php", array('id'=>$cmid));
$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_title(get_string('justice.peercheck','mod_emarking'));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(get_string('justice.my.evaluations','mod_emarking'));
$PAGE->set_url($url);

if(!has_capability('mod/emarking:viewpeerstatistics', $context)) {
	redirect(new moodle_url("/mod/emarking/view.php?id=$cmid"));
}

if(!has_capability('mod/assign:grade', $context) && !$emarking->peervisibility) {
	redirect(new moodle_url("/mod/emarking/view.php?id=$cmid"));
}


$query = "SELECT * FROM (
		SELECT
		s.student as userid,
		s.id as submission,
		s.grade,
		u.firstname,
		u.lastname
		FROM {emarking_submission} as s
		INNER JOIN {user} as u ON (s.emarking = :emarking AND s.student = u.id)
		union
		SELECT
		uu.userid,
		CASE WHEN s.id is null THEN 0 ELSE s.id END as submission,
		IFNULL (s.grade, 0) as finalgrade,
		u.firstname,
		u.lastname
		FROM (SELECT :userid2 as userid) as uu
		INNER JOIN {user} as u ON (uu.userid = u.id)
		LEFT JOIN {emarking_submission} as s ON (s.emarking = :emarking2 AND uu.userid = s.student AND s.status >= 20)
		WHERE uu.userid <> :userid3) as GRADES
		ORDER BY grade DESC";

$exams = $DB->get_records_sql($query,
		array('emarking'=>$submission->emarking, 'userid'=>$USER->id, 'userid2'=>$USER->id, 'emarking2'=>$submission->emarking, 'userid3'=>$USER->id));

// Calculates the relative position of the student within the list we display a limited
// amount of information (10% within her grade)
$studentposition = 0;
$current = 0;
foreach($exams as $exam) {
	$current++;
	if($exam->userid == $USER->id) {
		$studentposition = $current;
	}
}

$delta = max(2, round((count($exams) * 0.1),0));
$minstudent = max($studentposition - $delta, 1);
$maxstudent = min($studentposition + $delta, count($exams));

$table = new html_table();
$table->head = array(
		get_string('justice.similars.name','mod_emarking'),
		get_string('justice.similars.grade','mod_emarking'),
		get_string('justice.similars.actions','mod_emarking')
);
$data = array();
$pixicon = new pix_icon('i/preview', get_string('viewsubmission','mod_emarking'));

$current = 0;
foreach($exams as $exam){
	
	$current++;
	if($current < $minstudent || $current > $maxstudent) {
		continue;
	}
	
	$examarray = array();
	$grade = " - ";
	if(isset($exam->hidden)&&$exam->hidden==true){
		$grade = " ? ";
	}else if(isset($exam->grade)){
		$grade = round($exam->grade,2);
	}
	$downloadurl = new moodle_url('/mod/emarking/ajax/a.php',array('action'=>'emarking','ids'=>$exam->submission));
	$examarray[] = $exam->userid == $USER->id ? $exam->firstname." ".$exam->lastname : "NN";
	$examarray[] = $grade;
	$examarray[] = $OUTPUT->action_link($downloadurl, null,
			new popup_action ( 'click', $downloadurl, 'emarking' . $exam->submission, array (
								'menubar' => 'no',
								'titlebar' => 'no',
								'status' => 'no',
								'toolbar' => 'no' 
						)), null, $pixicon);
	$data[] = $examarray;
}
$table->data = $data;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('justice.peercheck','mod_emarking'));
echo $OUTPUT->tabtree(emarking_tabs($context, $cm, $emarking),'viewpeers');

echo html_writer::table($table);

echo $OUTPUT->footer();
