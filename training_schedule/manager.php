<?php

require('../../config.php');
if (!is_readable($CFG->dirroot.'/phlcohort/training_schedule/lib.php')) {
    throw new coding_exception('/phlcohort/training_schedule/lib.php not found');
} else {
    if (!is_readable($CFG->dirroot.'/phlcohort/phl_libraries/phllib.php')) {
        throw new coding_exception('/phlcohort/phl_libraries/phllib.php not found');
    }   else {
        if (!is_readable($CFG->dirroot.'/phlcohort/check_user_role/check_user_role.php')) {
            throw new coding_exception('/phlcohort/check_user_role/check_user_role.php not found');
        }   else    {
            require_once($CFG->dirroot.'/phlcohort/training_schedule/lib.php');
            require_once($CFG->dirroot.'/phlcohort/phl_libraries/phllib.php'); // general functions
            require_once($CFG->dirroot.'/phlcohort/check_user_role/check_user_role.php'); // role assign
        }
    }
}
require_login();
check_user_by_role($USER->id,false);
// FROM/TO DATE 
$q = array();
$q['array_from_d'] = date('d'); $q['array_from_m'] = date('m'); $q['array_from_y'] = date('Y');
$q['array_to_d'] = date('d'); $q['array_to_m'] = date('m'); $q['array_to_y'] = date('Y');
$q['array_cohort_idnumber'] = '';
if(optional_param('sent_from_d', '', PARAM_RAW)&&optional_param('sent_from_m', '', PARAM_RAW)&&optional_param('sent_from_y', '', PARAM_RAW)
&&optional_param('sent_to_d', '', PARAM_RAW)&&optional_param('sent_to_m', '', PARAM_RAW)&&optional_param('sent_to_y', '', PARAM_RAW)) {
    $q['array_from_d'] = optional_param('sent_from_d', '', PARAM_RAW);
    $q['array_from_m'] = optional_param('sent_from_m', '', PARAM_RAW);
    $q['array_from_y'] = optional_param('sent_from_y', '', PARAM_RAW);
    $q['array_to_d'] = optional_param('sent_to_d', '', PARAM_RAW);
    $q['array_to_m'] = optional_param('sent_to_m', '', PARAM_RAW);
    $q['array_to_y'] = optional_param('sent_to_y', '', PARAM_RAW);
    $q['array_cohort_idnumber'] = optional_param('sent_cohort_idnumber', '', PARAM_RAW);
}
// Input in here
$tabs_array = training_schedule_types_array();
$href_arrays = training_schedule_type_link($CFG->wwwroot);
$PAGE->set_heading($tabs_array[0]);
$activeTab = $tabs_array[0];
//

context_tabs($tabs_array,$href_arrays,$activeTab);
echo $OUTPUT->header();
page_tabs($tabs_array,$href_arrays,$activeTab);
echo '<br><br>';
training_schedule_cohorts_info();
echo '<br><br>';
training_schedule_search_field_box($q);
if($q['array_cohort_idnumber']){
    $cohorts_results = get_training_schedule_cohort_info($q);
    if($cohorts_results){
        $table_headers = array('Mã lớp','Tên lớp','Loại lớp','Văn phòng','Khóa học',
        'Mở lớp','Đóng lớp','Thi','Địa điểm','TP huấn luyện','Đã đăng ký','Chỉnh sửa');
        html_format_table_show_cohort_information($cohorts_results,$table_headers);
    }else {
        // nothing
    }
}
echo $OUTPUT->footer();
/* END UPDATED */


