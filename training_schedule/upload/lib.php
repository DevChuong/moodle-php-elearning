<?php
function switch_date_month($DateTime){
    $array = explode('/', $DateTime);
    $tmp = $array[0];
    $array[0] = $array[1];
    $array[1] = $tmp;
    unset($tmp);
    $result = implode('/', $array);
    return $result;
}
function check_exist_examcode($examcode){
    global $DB;
    $examData = $DB->get_records_sql('
    select id,examcode,examdate from {examcode} where examcode = ?',
    array('examcode'=>$examcode));
    if($examData){
        if( count($examData)> 1){   //avoid duplicated examcode, it must be unique 1:1
            throw new coding_exception("Lỗi CSDL: Có 2 mã thi chữ bị trùng lặp trong bảng examcode.");
        } else {
            $obj_examcode = new stdClass();
            foreach($examData as $e){
                $obj_examcode->id = $e->id;
                $obj_examcode->examcode = $e->examcode;
                $obj_examcode->examdate = $e->examdate;
            }
            return $obj_examcode; // return object of id and examdate
        /*    $examid = 0;
            foreach($examData as $d){
                $examid = $d->id;
            }
            return $examid; */
        }
    } else {
        return false;
    }
}
function check_exist_cohort($idnumber){
    global $DB;
    $cohortData = $DB->get_record_sql('
    select id, online, examcodeid from {cohort} where idnumber = ?',
    array('idnumber'=>$idnumber));
    if($cohortData){
        return $cohortData;
    } else {
        return false;
    }
}

function create_new_examcode($file){
    global $DB;
    //throw new coding_exception('create_new_examcode');
    $exam_data = new stdClass();
    $exam_data->examcode = $file->testcode;
    $exam_data->timecreated = time();
    $exam_data->timemodified = time();
    $exam_data->examdate = $file->datetest;
    $exam_data->address_test = $file->address_test;
    $exam_data->test_form = $file->test_form;
    $city_test = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$file->city_test));
    $exam_data->city_test = $city_test->id;
    $exam_data->examiner = $file->examiner;
    $id = $DB->insert_record('examcode',$exam_data);

    return $id;
}
/*

$allowedcolumns = array('

cohorttype','OK_area','aduser',
'trainer','datetest','note'
,'office','testcode','examiner',
'datestart','dateend','address_training',
'city_training','pss_quantity','address_test',
'cancel_flag','error_desc','city_test','test_form',
'online','idnumber','name'

);

*/
function create_new_cohort($examcodeid,$file){
    global $DB;
    
    $cohort_data = new stdClass();

    $contextid = 0; // default;
    $context_arrays = $DB->get_records_sql('
    select ctx.id from {context} ctx
    inner join {course_categories} cc
    on cc.id = ctx.instanceid
    inner join {course} co
    on co.category = cc.id
    where co.idnumber = ?',array('idnumber'=>
    $file->cohorttype));
    foreach($context_arrays as $ctx){
        $contextid = $ctx->id;
    }
    $cohort_data->contextid = $contextid ;

    $cohort_data->name = $file->idnumber ;
    $cohort_data->idnumber = $file->idnumber ;
    $cohort_data->description = $file->test_form;
    
    if (!isset($file->descriptionformat)) {
        $cohort_data->descriptionformat = FORMAT_HTML;
    }
    if (!isset($file->visible)) {
        $cohort_data->visible = 1;
    }
    if (empty($file->component)) {
        $cohort_data->component = '';
    }
    if (!isset($cohort_data->timecreated)) {
        $cohort_data->timecreated = time();
    }
    if (!isset($cohort_data->timemodified)) {
        $cohort_data->timemodified = 0;
    }
    $cohort_data->area = $file->area ; // 
    $cohort_data->office = $file->office ;//
    $cohort_data->datestart = $file->datestart ;
    $cohort_data->dateend = $file->dateend ;
    $cohort_data->datetest = $file->datetest; // fix bug 03/02/2023
    $cohort_data->trainer = $file->trainer ;
    $cohort_data->address_training = $file->address_training ;
    
    $city_training = $DB->get_record_sql('select id from {cohortphl_khuvuc} 
    where tenkhuvuc = ?',array('tenkhuvuc'=>$file->city_training));
    $cohort_data->city_training = $city_training->id ;
    
    $cohort_data->aduser = $file->aduser ;
    $cohort_data->pss_quantity = $file->pss_quantity ;
    $cohort_data->cancel_flag = $file->cancel_flag ;
    $cohort_data->error_desc = 'Test is trimmed' ;
    $cohort_data->online = $file->online ;
    $note_check = 'Tao lop ';
    if($examcodeid != 0){
        $cohort_data->examcodeid = $examcodeid;
        $note_check .= ' co ma thi ';
    }   
    $course = $DB->get_record_sql('select id from {course}
    where idnumber = ?',array('idnumber'=>$file->cohorttype));
    $cohort_data->note = $note_check;
    $cohort_data->courseid = $course->id ;

    $id = $DB->insert_record('cohort',$cohort_data);
    return $id;
}
/*  check cohort examcode pair in old table must match with 2 separate tables   */
function check_cohort_examcode($cohortid, $examcodeid){
    global $DB;
    $cohort_examcode = $DB->get_record_sql('
    select id,examdate from {cohort_examcode} 
    where cohortid  =   ?
    and examcodeid  =   ?',
    array('cohortid'=>$cohortid,'examcodeid'=>$examcodeid));
    if($cohort_examcode){
        return $cohort_examcode;
    }else{return false;}
}

function check_or_insert_enrol_cohort($idnumber,$cohortid){
    global $DB;

    $check_enrol = $DB->get_record_sql('
    select id from {enrol} where
    customint1 = ?',array('customint1'=>$cohortid));

    if( $check_enrol ){ // bài hoc chưa tạo
        return $check_enrol->id;
    } else {

        $course = $DB->get_record_sql('
        select id from {course} where idnumber = ?',
        array('idnumber'=>$idnumber));
        $courseid = $course->id;
        $sql_enrol_sortorder = "
        select top 1 sortorder as max_sortorder from mdl232x0_enrol 
        where courseid = $courseid order by sortorder desc
        ";
        $max_sortorder = $DB->get_record_sql($sql_enrol_sortorder,array());
        $sortorder = $max_sortorder->max_sortorder + 1;
        $enrol = new stdClass();
        $enrol->enrol = 'cohort';
        $enrol->status = 0;
        $enrol->courseid = $courseid;
        $enrol->sortorder = $sortorder;
        $enrol->name = 'Upload Cohort '.$cohortid;
        $enrol->enrolperiod = 0;
        $enrol->enrolstartdate = 0;
        $enrol->enrolenddate = 0;
        $enrol->expirynotify = 0;
        $enrol->expirythreshold = 0;
        $enrol->notifyall = 0;
        $enrol->roleid = 5;
        $enrol->customint1 = $cohortid;
        $enrol->customint2 = 0;
        $enrol->timecreated = time();
        $enrol->timemodified = time();
        $enrol->id = $DB->insert_record('enrol', $enrol);
        return $enrol->id;
    }
}
/* old case, keep it */
function create_examcode_old_table($file,$cohortid,$examcodeid){
    global $DB;
    $exam_data = new stdClass();
    $exam_data->examcodeid = $examcodeid;
    $exam_data->examcode = $file->testcode;
    $exam_data->timecreated = time();
    $exam_data->timemodified = time();
    $exam_data->cohortid = $cohortid;
    $exam_data->examdate = $file->datetest;
    $exam_data->address_test = $file->address_test;
    $exam_data->test_form = $file->test_form;
    $city_test = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$file->city_test));
    $exam_data->city_test = $city_test->id;
    $exam_data->examiner = $file->examiner;
    $id = $DB->insert_record('cohort_examcode',$exam_data);
}   
/* end old case*/

function cohort_upload_add_cohort_debut($file_cohort) {
    global $DB;

    $datetest = strtotime(switch_date_month($file_cohort->datetest));
    $file_cohort->datetest = $datetest;
    $datestart = strtotime(switch_date_month($file_cohort->datestart));  
    $file_cohort->datestart = $datestart;
    $dateend = strtotime(switch_date_month($file_cohort->dateend));
    $file_cohort->dateend = $dateend;
    if (empty($file_cohort->cancel_flag)) {
        $file_cohort->cancel_flag = 0;
    }

    $cohort_exist = check_exist_cohort($file_cohort->idnumber);
    $return_cohort_id = 0; // default value, must have value after creating or updating cohort
    $file_cohort->testcode = trim($file_cohort->testcode);
    if( (!empty($file_cohort->testcode))  ){ // TH lớp CÓ ĐI THI
        $examcode_exist = check_exist_examcode($file_cohort->testcode);
        if( (!$cohort_exist) && (!$examcode_exist)  ){
            //throw new coding_exception('595');
            $new_examcode_id = create_new_examcode($file_cohort);
            $return_cohort_id = create_new_cohort($new_examcode_id,$file_cohort);
            // Tạo mã thi và lớp đi thi (cohort và examcode)

            /* UPDATED 21/12/2022 */
            //if($new_examcode_id){
            if($new_examcode_id&&$return_cohort_id){   // Giữ nguyên insert bảng cũ cohort_examcode
                //create_examcode_old_table($file_cohort,$return_cohort_id);
                create_examcode_old_table($file_cohort,$return_cohort_id,$new_examcode_id);
            }
        } else {
            if ( (!$cohort_exist) && $examcode_exist ){
                //throw new coding_exception('600');
                $return_cohort_id = create_new_cohort($examcode_exist->id,$file_cohort);
                //create pair cohort examcode right away
                create_examcode_old_table($file_cohort,$return_cohort_id,$examcode_exist->id);
                
            } else {
                if ( ($cohort_exist) && $examcode_exist ){
                    // TH kiểm tra lớp phải đúng mã thi đã thực hiện bên form
                    //throw new coding_exception('605');
                    $updated->online = $file_cohort->online;
                    $updated->cancel_flag = $file_cohort->cancel_flag;
                    $updated->note = 'Updated lop di thi';
                    $updated->id = $cohort_exist->id;
                    $DB->update_record('cohort',$updated);
                    // UPDATED 22/12/2022: no longer allowed to change exam date
                    //$updated_examcode->examdate = $file_cohort->datetest;
                    //$updated_examcode->id = $examcode_exist->id;
                    //$DB->update_record('examcode',$updated_examcode);
                    // Cập nhật ngày thi
                    $return_cohort_id =$updated->id;

                    $cohort_examcode = check_cohort_examcode($cohort_exist->id,$examcode_exist->id);
                    if(!$cohort_examcode){ // in case pair has not exist yet
                        create_examcode_old_table($file_cohort,$cohort_exist->id,$examcode_exist->id);
                    }
                    /* DEPRECATED cancel_flag in cohort_examcode, using only in cohort 28/12/2022
                    else{ // update pair
                        $updated_cohort_examcode->cancel_flag = '1';
                        $updated_cohort_examcode->id = $cohort_examcode->id;
                        $DB->update_record('cohort_examcode',$updated_cohort_examcode);
                    }   */
        /*            $cohort_examcode = $DB->get_record_sql('select id from {cohort_examcode}
                    where cohortid = ? and examcodeid = ?',array('cohortid'=>$cohort_exist->id,
                    'examcodeid'=>$examcode_exist->id));
                    $updated_cohort_examcode->cancel_flag = '8';
                    $updated_cohort_examcode->id = $cohort_examcode->id;
                    $DB->update_record('cohort_examcode',$updated_cohort_examcode); */

                } else {
                    if ( ($cohort_exist) && (!$examcode_exist) ){
                        // TH case cũ , update cột examcodeid và tạo row mới bảng examcode
                        //throw new coding_exception('605');
                    /*    $new_examcode_id = create_new_examcode($file_cohort);
                        $updated->online = $file_cohort->online;
                        $updated->examcodeid = $new_examcode_id;
                        $updated->note = 'Updated data old case';
                        $updated->id = $cohort_exist->id;
                        $DB->update_record('cohort',$updated);
                        // Cập nhật ngày thi
                        $return_cohort_id =$updated->id;        */
                    }
                }
            }
        }

    }   else {  // TH lớp KHÔNG ĐI THI
            if( $cohort_exist ){
                //throw new coding_exception('618');
                $updated->online = $file_cohort->online;
                $updated->note = 'Updated khong di thi';
                $updated->cancel_flag = $file_cohort->cancel_flag;
                $updated->id = $cohort_exist->id;
                $DB->update_record('cohort',$updated);
                // Cập nhật lớp không thi
                
                $return_cohort_id =$updated->id;
            } else {    // TH lớp không thi, giá trị = 0
                        // truyền vào thì không tạo giá trị cột examcodeid
                //throw new coding_exception('622');
                $return_cohort_id = create_new_cohort(0,$file_cohort);
                // Tạo lớp không thi
            }
    }

    if ($file_cohort->online == 1){ // TH lớp đi thi, check dữ liệu bài học enrolments.
        $enrol_data = check_or_insert_enrol_cohort($file_cohort->cohorttype,$return_cohort_id);
        if (!$enrol_data){// must have value updated or new
            throw new coding_exception('Lỗi ID enrolment không được tạo.');
        }   
    }
    

    if ($return_cohort_id == 0){ // ID cannot be the same as the initialized value
        throw new coding_exception('Lỗi ID lớp không được tạo.');
    } else {
        return $return_cohort_id;
    }
}