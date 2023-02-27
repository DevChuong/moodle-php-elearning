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
 * A form for cohort upload.
 *
 * @package    core_cohort
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
if (!is_readable($CFG->dirroot.'/phlcohort/training_schedule/lib.php')) {
    throw new coding_exception('/phlcohort/training_schedule/lib.php not found');
}   else    {
    if (!is_readable($CFG->dirroot.'/phlcohort/phl_libraries/phllib.php')) {
        throw new coding_exception('/phlcohort/phl_libraries/phllib.php not found');
    }   else {
        if (!is_readable($CFG->dirroot.'/phlcohort/check_user_role/check_user_role.php')) {
            throw new coding_exception('/phlcohort/check_user_role/check_user_role.php not found');
        }   else    {
            require_once($CFG->dirroot.'/phlcohort/phl_libraries/phllib.php'); // general functions
            require_once($CFG->dirroot.'/phlcohort/check_user_role/check_user_role.php'); // role assign
            require_once($CFG->dirroot.'/phlcohort/training_schedule/upload/lib.php');
            require_once($CFG->dirroot.'/phlcohort/training_schedule/lib.php');
        }
    }
}
require_login();
admin_manager_only($USER->id);
require_once($CFG->dirroot.'/phlcohort/training_schedule/upload/upload_form_pss.php');
require_once($CFG->libdir . '/csvlib.class.php');
$contextid = optional_param('contextid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_URL);

require_login();
if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}
if ($context->contextlevel != CONTEXT_COURSECAT && $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}
//require_capability('moodle/cohort:manage', $context);

$PAGE->set_context($context);
$baseurl = new moodle_url('/phlcohort/upload.php', array('contextid' => $context->id));
$PAGE->set_url($baseurl);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_pagelayout('admin');

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $PAGE->set_category_by_id($context->instanceid);
    navigation_node::override_active_url(new moodle_url('/phlcohort/manager.php', array('contextid' => $context->id)));
} else {
    navigation_node::override_active_url(new moodle_url('/phlcohort/manager.php', array()));
}

$uploadform = new cohort_upload_form(null, array('contextid' => $context->id, 'returnurl' => $returnurl));

$returnurl = new moodle_url('/phlcohort/upload.php');
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/phlcohort/manager.php', array('contextid' => $context->id));
}

if ($uploadform->is_cancelled()) {
    redirect($returnurl);
}
echo $OUTPUT->header();
$search = html_writer::start_div('row');
$search .= html_writer::start_div('col-md-10');
$search .= html_writer::end_div();
$search .= html_writer::start_div('col-md-2');
$search .= html_writer::end_div();
$search .= html_writer::end_div();
echo $search;


if ($data = $uploadform->get_data()) {
    
    $cohortsdata = $uploadform->get_cohorts_data();
    foreach ($cohortsdata as $cohort) {
        
        cohort_upload_add_cohort_debut($cohort);
    }
    echo $OUTPUT->notification(get_string('uploadedcohorts', 'cohort', count($cohortsdata)), 'notifysuccess');
    echo $OUTPUT->continue_button($returnurl);
} else {
    
    $uploadform->display();
}





echo $OUTPUT->footer();

/*04082022
- Sửa header
04082022*/
