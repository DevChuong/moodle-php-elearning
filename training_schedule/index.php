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
$q = array();
$q['array_cohortid']=optional_param('id',0, PARAM_INT);
// Input in here
$tabs_array = array('Điểm danh');
$href_arrays = array($CFG->wwwroot.'/phlcohort/training_schedule/manager.php',
    $CFG->wwwroot.'/phlcohort/training_schedule/upload/upload_lophoc.php');
$PAGE->set_heading('Thông tin lớp điểm danh');
$activeTab = 'Điểm danh';
//
context_tabs($tabs_array,$href_arrays,$activeTab);
echo $OUTPUT->header();
$detail_cohort_result = training_schedule_offline_cohort($q['array_cohortid']);
if($detail_cohort_result){
    $headers = array('STT','Họ tên','CMTND','Email','Phone','Buổi 1'
    ,'Buổi 2','Buổi 3','Buổi 4','Buổi 5','Buổi 6','Đạt điều kiện tham dự','Ghi chú');
    training_schedule_offline_cohort_on_top($detail_cohort_result);
    echo '<br>';
    training_schedule_offline_cohort_members_table($detail_cohort_result,$headers);
}
echo $OUTPUT->footer();