<?php
function training_schedule_search_field_box($q){
    global $DB;
    $search_html = '';
    /* 
    $search_html .= html_writer::start_tag('',array());
    $search_html .= html_writer::end_tag('',array());
    */
    $search_html .= html_writer::start_tag('form',array('action'=>'./manager.php','method'=>'post'));
        $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                $search_html .= html_writer::start_tag('div',array('class'=>'col-md-6'));
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                        $search_html .= html_format_date('Từ ngày','col-md-4',
                                        $q['array_from_d'],$q['array_from_m'],$q['array_from_y'],
                                        'sent_from_d','sent_from_m','sent_from_y');
                    $search_html .= html_writer::end_tag('div');
                    $search_html .= '<br>'; // Enter new line
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                        $search_html .= html_format_date('đến ngày','col-md-4',
                                        $q['array_to_d'],$q['array_to_m'],$q['array_to_y'],
                                        'sent_to_d','sent_to_m','sent_to_y');
                    $search_html .= html_writer::end_tag('div');
                $search_html .= html_writer::end_tag('div');

                $search_html .= html_writer::start_tag('div',array('class'=>'col-md-6'));
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                        $search_html .= html_fill_input_box('Mã lớp','col-md-4',
                                        'col-md-5','sent_cohort_idnumber',$q['array_cohort_idnumber']);
                    $search_html .= html_writer::end_tag('div');
                    $search_html .= '<br>';
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                        
                    $search_html .= html_writer::end_tag('div');
                $search_html .= html_writer::end_tag('div');
                $search_html .= '<br><br><br><br>';
                $search_html .= html_writer::start_tag('div',array('class'=>'col-md-6'));
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                    // ADD BLANK SPACE NEXT TO BUTTON 
                    $blank_space_number = 4; $blank_space = '';
                    for($i=0;$i<$blank_space_number;$i++){
                        $blank_space .= '&nbsp;';
                    }   $search_html .= $blank_space;
                        $search_html .= search_button('Tìm');
                    $search_html .= html_writer::end_tag('div');
                    $search_html .= '<br><br>'; // add 2 rows to make it more beautiful
                    $search_html .= html_writer::start_tag('div',array('class'=>'row'));
                        
                    $search_html .= html_writer::end_tag('div');
                $search_html .= html_writer::end_tag('div');
                
        $search_html .= html_writer::end_tag('div');
    $search_html .= html_writer::end_tag('form',array());
    echo $search_html;
}
// Get cohorts quantity and detail
function offline_quantity(){
    global $DB;
    $quantity = $DB->get_record_sql('select count(id) as sl from {cohort} 
    where online = ?',
    array('online'=>0));
    return $quantity->sl;
}
function online_quantity(){
    global $DB;
    $quantity = $DB->get_record_sql('select count(id) as sl from {cohort} 
    where online = ?',
    array('online'=>1));
    return $quantity->sl;
}
function both_quantity(){
    global $DB;
    $quantity = $DB->get_record_sql('select count(id) as sl from {cohort} 
    where online is not null',
    array());
    return $quantity->sl;
}
function training_schedule_cohorts_info(){
    global $DB; $index = 0; $html_cohort_info = '';
    $number_space_blank = 25; // number of &nbsp;
     $space_blank = '';
    for($i=0;$i<$number_space_blank;$i++){
        $space_blank .= '&nbsp;';
    }
    $online = online_quantity(); $offline = offline_quantity(); $all = both_quantity();
    $info_array = array('Lớp Online','Lớp Offline','Tất cả lớp');
    $quantity_array = array($online,$offline,$all);
    $color_array = array('#FF3535','#00DA03','#343434');
    for($i=0;$i<3;$i++){
        $html_cohort_info .= html_writer::start_tag('strong',array('style'=>'color:'.$color_array[$index])).$info_array[$index].': '.$quantity_array[$index].html_writer::end_tag('strong',array());
        $html_cohort_info .= $space_blank;
        $index ++;
    }
    echo $html_cohort_info;
}
function get_training_schedule_cohort_info($q){
    global $DB;
    $date = array();
    $date['from'] = render_date_format($q['array_from_d'].'/'.$q['array_from_m'].'/'.$q['array_from_y']);
    $date['to'] = render_date_format($q['array_to_d'].'/'.$q['array_to_m'].'/'.$q['array_to_y']);
    $sql = "
    select c.id cid,c.idnumber tenlop,c.name,c.online,c.office,co.idnumber loaikhoa,c.address_training,
    c.datestart,c.dateend,cma.examdate,kv.tenkhuvuc,count(cm.id) as members_quantity
    from {cohort} c
    inner join {course} co on c.courseid = co.id
    left join {cohortphl_khuvuc} kv on kv.id = c.city_training
    left join {cohort_examcode} cma on cma.cohortid = c.id
    and c.examcodeid = cma.id
    left join mdl232x0_cohort_members cm on cm.cohortid = c.id 
	left join mdl232x0_user u on u.id = cm.userid
    where c.online is not null 
    and c.idnumber like '%".$q['array_cohort_idnumber']."%'
    and c.datestart BETWEEN ".strtotime($date['from'])." AND ".strtotime($date['to'])."
    group by c.id ,c.idnumber,c.name,c.online,c.office,co.idnumber,c.address_training,
    c.datestart,c.dateend,cma.examdate,kv.tenkhuvuc
    order by c.idnumber";
    $cohorts = $DB->get_records_sql($sql,array());
    return $cohorts;
}
/*  DEPRECATED
function get_quantity_cohort_info_table($cid){
    global $DB;
    $quantity = $DB->get_record_sql('
    select c.idnumber,count(cm.id) as members_quantity
    from {cohort_members} cm
    inner join {cohort} c on c.id = cm.cohortid 
    inner join {user} u on u.id = cm.userid
    where c.id = ? group by c.idnumber
    ',array('id'=>$cid));
    if($quantity->members_quantity){     // if count = 0 it will fall into 
        return $quantity->members_quantity;
    } else {
        return 0;
    }
}*/
function html_format_table_show_cohort_information($td_arrays,$headers){
    global $DB, $CFG; $html_table = '';
    // STYLE THE TABLES
    $table_style = '';
    $table_style .= html_writer::start_tag('style');
    $table_style .= "
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
      }
      
      #customTableID th {
          background-color:white;
          border: 1px solid #b88d27;
          text-align: left;
        padding: 8px;
      }
      
      td{
        border: 1px solid black;
        text-align: left;
        padding: 8px;
      }
      
      tr:nth-child(even) {
        background-color: #dddddd;
    }";
    $table_style .= html_writer::end_tag('style');
    $html_table .= $table_style;
    $html_table .= html_writer::start_tag('table',array('id'=>'customTableID'));
    $html_table .= html_writer::start_tag('tr',array());
    $column_number = 0;
    foreach($headers as $header_element){
        $html_table .= html_writer::start_tag('th',array('class'=>'header c'.$column_number,'scope'=>'col'));
        $html_table .= $header_element; $column_number ++;
        $html_table .= html_writer::end_tag('th');
    }
    $html_table .= html_writer::end_tag('tr');
    // tr and td goes here
    foreach($td_arrays as $td){
        $html_table .= html_writer::start_tag('tr',array());
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->tenlop;
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->name;
        $html_table .= html_writer::end_tag('td');
        //$html_table .= html_writer::start_tag('td');
        if($td->online == 1){
            $html_table .= html_writer::start_tag('td',array('style'=>"color:red"));
            $html_table .= html_writer::start_tag('b').'Online'.html_writer::end_tag('b');
            $html_table .= html_writer::end_tag('td');
        }else{
            $html_table .= html_writer::start_tag('td',array("style"=>"color:#00DA03"));
            $html_table .= html_writer::start_tag('b').'Offline'.html_writer::end_tag('b');
            $html_table .= html_writer::end_tag('td');
        }
        //$html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->office;
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->loaikhoa;
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= check_date_null($td->datestart);
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= check_date_null($td->dateend);
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= check_date_null($td->examdate);
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->address_training;
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= $td->tenkhuvuc;
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');

        //link to the detailed Offline cohort.
        $html_table .= html_writer::start_tag('a',array('href'=>$CFG->wwwroot.'/phlcohort/training_schedule/index.php?id='.$td->cid
                        ,'style'=>'color:black'));
        $html_table .= html_writer::start_tag('strong');
        $html_table .= $td->members_quantity;//get_quantity_cohort_info_table($td->cid);
        $html_table .= html_writer::end_tag('strong');
        $html_table .= html_writer::end_tag('a');

        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::start_tag('td');
        $html_table .= '';
        $html_table .= html_writer::end_tag('td');
        $html_table .= html_writer::end_tag('tr');
    }
    //
    $html_table .= html_writer::end_tag('table');
    echo $html_table;
}
function training_schedule_offline_cohort($id){
    global $DB;
    $offline_cohort_info = $DB->get_record_sql('
    select c.id,c.idnumber malop, c.name tenlop , co.fullname khoahoc , c.trainer trainer, 
    c.datestart batdau , c.dateend ketthuc , cma.examdate ngaythi  , c.courseid templatecourse ,
    c.online online_cohort
    , c.area khuvuc
    from {cohort} c
    inner join {course} co on co.id = c.courseid 
    left join {cohort_examcode} cma on cma.cohortid = c.id
    where c.id = ?',array('id'=>$id));
    return $offline_cohort_info; // -- $cohortObj
}
function get_template_path($coid,$cid){
    global $DB, $CFG;
    $folder = array();
    $folder_download = '/phlcohort/training_schedule/download/';
    $folder_upload = '/phlcohort/training_schedule/upload/';
    $templateid = $DB->get_record_sql('select templateid from {course} 
    where id = ?',array('id'=>$coid));
    if(!$templateid->templateid || empty($templateid->templateid)){
        $folder['download'] = '#';
        $folder['upload'] = '#';
    }   else {
        if($templateid->templateid == 1){
            $folder['download'] = $CFG->wwwroot.''.$folder_download.'write_to_excel_pss.php?id='.$cid;
            $folder['upload'] = $CFG->wwwroot.''.$folder_upload.'upload_diemdanh_pss.php';
        }   else    {
            if($templateid->templateid == 3){
                $folder['download'] = $CFG->wwwroot.''.$folder_download.'write_to_excel_kynang.php?id='.$cid;
                $folder['upload'] = $CFG->wwwroot.''.$folder_upload.'upload_diemdanh_kynang.php';
            }
        }
    }
    return $folder;
}
function training_schedule_offline_cohort_on_top($cohortObj){
    global $DB, $CFG; $html_top_cohort_headers = ''; //$html_top_cohort_headers .=
    // Button path based on course template
    $path = get_template_path($cohortObj->templatecourse,$cohortObj->id);
    // Row 1
    $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'row'));
        $html_div_name_array = array('Mã lớp:',$cohortObj->malop,'Tên lớp:',$cohortObj->tenlop);
        $html_div_column_size = array('1','3','1','4'); $html_div_column_size_index = 0;
        foreach($html_div_name_array as $rowOne){
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-'.$html_div_column_size[$html_div_column_size_index]));
            if($html_div_column_size_index == 0 || $html_div_column_size_index == 2){
                $html_top_cohort_headers .= $html_div_name_array[$html_div_column_size_index];
            }   else    {
                $html_top_cohort_headers .= html_writer::start_tag('strong').$html_div_name_array[$html_div_column_size_index].html_writer::end_tag('strong');
            }
            $html_top_cohort_headers .= html_writer::end_tag('div');
            $html_div_column_size_index ++;
        }
        $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-1'));
        $html_top_cohort_headers .= html_writer::start_tag('a',array('href'=>$CFG->wwwroot.'/phlcohort/training_schedule/assign.php?id='.$cohortObj->id,'class'=>'btn btn-primary','style'=>'color:white'));
        $html_top_cohort_headers .= html_writer::start_tag('strong').'Thêm/xóa học viên'.html_writer::end_tag('strong');
        $html_top_cohort_headers .= html_writer::end_tag('a');
        $html_top_cohort_headers .= html_writer::end_tag('div');
    $html_top_cohort_headers .= html_writer::end_tag('div');
    // Row 2
    $html_top_cohort_headers .= '<br>'.html_writer::start_tag('div',array('class'=>'row'));
        $html_div_name_array = array('Khóa học:',$cohortObj->khoahoc,'Trainer:',$cohortObj->trainer);
        $html_div_column_size_index = 0; // reset column size array index
        foreach($html_div_name_array as $rowTwo){
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-'.$html_div_column_size[$html_div_column_size_index]));
            if($html_div_column_size_index == 0 || $html_div_column_size_index == 2){
                $html_top_cohort_headers .= $html_div_name_array[$html_div_column_size_index];
            }   else    {
                $html_top_cohort_headers .= html_writer::start_tag('strong').$html_div_name_array[$html_div_column_size_index].html_writer::end_tag('strong');
            }
            $html_top_cohort_headers .= html_writer::end_tag('div');
            $html_div_column_size_index ++;
        }
        if($cohortObj->online_cohort == 0){ // Download/Upload for Offline cohort
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-1'));
            $html_top_cohort_headers .= html_writer::start_tag('a',array('href'=>$path['download'],'class'=>'btn btn-primary','style'=>'color:white'));
            $html_top_cohort_headers .= html_writer::start_tag('strong').'Download điểm danh'.html_writer::end_tag('strong');
            $html_top_cohort_headers .= html_writer::end_tag('a');
            $html_top_cohort_headers .= html_writer::end_tag('div');
        }
    $html_top_cohort_headers .= html_writer::end_tag('div');
    // Row 3
    $batdau = check_date_null($cohortObj->batdau); $ketthuc = check_date_null($cohortObj->ketthuc);
    $html_top_cohort_headers .= '<br>'.html_writer::start_tag('div',array('class'=>'row'));
        $html_div_name_array = array('Mở lớp:',$batdau,'Đóng lớp:',$ketthuc);
        $html_div_column_size_index = 0; // reset column size array index
        foreach($html_div_name_array as $rowThree){
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-'.$html_div_column_size[$html_div_column_size_index]));
            if($html_div_column_size_index == 0 || $html_div_column_size_index == 2){
                $html_top_cohort_headers .= $html_div_name_array[$html_div_column_size_index];
            }   else    {
                $html_top_cohort_headers .= html_writer::start_tag('strong').$html_div_name_array[$html_div_column_size_index].html_writer::end_tag('strong');
            }
            $html_top_cohort_headers .= html_writer::end_tag('div');
            $html_div_column_size_index ++;
        }
        if($cohortObj->online_cohort == 0){ // Download/Upload for Offline cohort
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-1'));
            $html_top_cohort_headers .= html_writer::start_tag('a',array('href'=>$path['upload'],'class'=>'btn btn-primary','style'=>'color:white'));
            $html_top_cohort_headers .= html_writer::start_tag('strong').'Upload điểm danh'.html_writer::end_tag('strong');
            $html_top_cohort_headers .= html_writer::end_tag('a');
            $html_top_cohort_headers .= html_writer::end_tag('div');
        }
    $html_top_cohort_headers .= html_writer::end_tag('div');
    //Row 4
    $ngaythi = check_date_null($cohortObj->ngaythi); 
    $html_top_cohort_headers .= '<br>'.html_writer::start_tag('div',array('class'=>'row'));
        $html_div_name_array = array('Ngày thi:',$ngaythi,'Khu vực:',$cohortObj->khuvuc);
        $html_div_column_size_index = 0; // reset column size array index
        foreach($html_div_name_array as $rowFour){
            $html_top_cohort_headers .= html_writer::start_tag('div',array('class'=>'col-md-'.$html_div_column_size[$html_div_column_size_index]));
            if($html_div_column_size_index == 0 || $html_div_column_size_index == 2){
                $html_top_cohort_headers .= $html_div_name_array[$html_div_column_size_index];
            }   else    {
                $html_top_cohort_headers .= html_writer::start_tag('strong').$html_div_name_array[$html_div_column_size_index].html_writer::end_tag('strong');
            }
            $html_top_cohort_headers .= html_writer::end_tag('div');
            $html_div_column_size_index ++;
        }
    $html_top_cohort_headers .= html_writer::end_tag('div');
    echo $html_top_cohort_headers;
}
function get_cohort_members_details($id){
    global $DB;
    $cohort_members = $DB->get_records_sql('
    select u.id , u.firstname , u.lastname , u.username
    ,u.email , u.phone1 , cm.date_1 , cm.date_2 , cm.date_3,
    cm.date_4 , cm.date_5 , cm.date_6 , cm.participate_condition ,
    cm.note
    from {cohort_members} cm
    inner join {cohort} c on c.id = cm.cohortid
    inner join {user} u on u.id = cm.userid
    where c.id = ?',array('id'=>$id));
    if($cohort_members){
        return $cohort_members;
    }   else    {
        return false;
    }
}
function training_schedule_offline_cohort_members_table($cohortObj,$headers){
    global $DB; $html_offline_members = '';
    // STYLE THE TABLES
    $table_style = '';
    $table_style .= html_writer::start_tag('style');
    $table_style .= "
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
      }
      
      #customTableID th {
          background-color:white;
          border: 1px solid #b88d27;
          text-align: left;
        padding: 8px;
      }
      
      td{
        border: 1px solid black;
        text-align: left;
        padding: 8px;
      }
      
      tr:nth-child(even) {
        background-color: #dddddd;
    }";
    $table_style .= html_writer::end_tag('style');
    $html_offline_members .= $table_style;

    $members = get_cohort_members_details($cohortObj->id);
    if($members){
        $quantity_added_members = count($members);
    } else {
        $quantity_added_members = 0; //avoid count null object as legit quantity '1'
    }
    
    
    $html_offline_members .= html_writer::start_tag('div',array('class'=>'row'));
    $html_offline_members .= html_writer::start_tag('div',array('class'=>'col-md-6'));
    $html_offline_members .= 'Danh sách học viên đã đăng ký: '.html_writer::start_tag('strong').$quantity_added_members.html_writer::end_tag('strong');
    $html_offline_members .= html_writer::end_tag('div');
    $html_offline_members .= html_writer::end_tag('div');

    $html_offline_members .=    html_writer::start_tag('table',array('id'=>'customTableID'));
    $html_offline_members .= html_writer::start_tag('tr',array());
    $column_number = 0;
    foreach($headers as $header_element){
        $html_offline_members .= html_writer::start_tag('th',array('class'=>'header c'.$column_number,'scope'=>'col'));
        $html_offline_members .= $header_element; $column_number ++;
        $html_offline_members .= html_writer::end_tag('th');
    }
    $html_offline_members .= html_writer::end_tag('tr');
    $table_index = 1;
    if(!$members || empty($members)){
        $html_offline_members.=html_writer::start_tag('tr');
        for($i=0;$i<13;$i++){
            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.='';
            $html_offline_members.=html_writer::end_tag('td');
        }
        $html_offline_members.=html_writer::end_tag('tr');
    }   else    {
        foreach($members as $cm){
            $html_offline_members.=html_writer::start_tag('tr');
            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$table_index;
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$cm->firstname.' '.$cm->lastname;
            $html_offline_members.=html_writer::end_tag('td');
            
            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$cm->username;
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$cm->email;
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$cm->phone1;
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_1.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_2.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_3.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_4.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_5.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->date_6.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=html_writer::start_tag('strong').$cm->participate_condition.html_writer::end_tag('strong');
            $html_offline_members.=html_writer::end_tag('td');

            $html_offline_members.=html_writer::start_tag('td');
            $html_offline_members.=$cm->note;
            $html_offline_members.=html_writer::end_tag('td');            
            $html_offline_members.=html_writer::end_tag('tr');
            $table_index ++;
        }
    }
    $html_offline_members.=html_writer::end_tag('table');
    echo $html_offline_members;
}
function get_cohort_pss_template($cid){
    global $DB;
    $template_cohort = $DB->get_record_sql('
    select c.id cid, c.address_training diadiemhuanluyen , 
    c.idnumber malop , c.trainer cvhl , c.datestart thoigianhoc ,
    cma.examcode makythi , cma.examdate ngaythi
    from {cohort} c
    left join {cohort_examcode} cma on cma.cohortid = c.id
    where c.id = ?
    ',array('id'=>$cid));
    if($template_cohort->cid){
        return $template_cohort;
    }   else    {
        return false;
    }
}
function get_cohort_pss_members_to_download($cid){
    global $DB;
    // check if TABLE_USER_INFO_DATA NULL
    $sql_check_table_user = 'select max(id) [data] from _Table_USER_INFO_DATA';
    $check_table_user = $DB->get_record_sql($sql_check_table_user,array());
    if($check_table_user->data){
        $sql = '
        SELECT u.id, u.cmnd , 
        u.fullname , u.ngaysinh , u.thangsinh , u.namsinh,
        u.AD ad_info , u.vanphong vp , u.area mien , 
        cm.date_1 ,cm.date_2 ,cm.date_3 ,cm.date_4 ,cm.date_5 ,cm.date_6 ,cm.participate_condition,
        cm.note
        from mdl232x0_cohort_members cm 
        inner join _Table_USER_INFO_DATA u on u.id = cm.userid
        inner join mdl232x0_cohort c on c.id = cm.cohortid
        where c.id = ?';
        $user_info_pss = $DB->get_records_sql($sql,array('id'=>$cid));
        return $user_info_pss;
    }   else    { // get user from system instead
        $sql = '
        SELECT u.id, u.username, u.firstname, u.lastname,
        cm.date_1 ,cm.date_2 ,cm.date_3 ,cm.date_4 ,cm.date_5 ,cm.date_6 ,cm.participate_condition,
        cm.note
        from mdl232x0_cohort_members cm 
        inner join mdl232x0_user u on u.id = cm.userid
        inner join mdl232x0_cohort c on c.id = cm.cohortid
        where c.id = ?';
        $user_info_pss = $DB->get_records_sql($sql,array('id'=>$cid));
        return $user_info_pss;
    }
}
function use_moodle_user_info_data_instead($id){
    global $DB;
    $user = $DB->get_record_sql('select * from {user}
    where id = ?',array('id'=>$id));
    return $user;
}
function get_cells_pss(){
    $arrayCells = array('B','L','N','P',
    'B','L','N','P',
    'B','L','N','P',
    'B','B','B','B');
    return $arrayCells;
}
function get_cell_rows_pss($theLastOfUs){
    $arrayRows = array($theLastOfUs,$theLastOfUs,$theLastOfUs,$theLastOfUs
    ,$theLastOfUs+1,$theLastOfUs+1,$theLastOfUs+1,$theLastOfUs+1,
    $theLastOfUs+2,$theLastOfUs+2,$theLastOfUs+2,$theLastOfUs+2,
    $theLastOfUs+4,$theLastOfUs+3,$theLastOfUs+3,$theLastOfUs+4);
    return $arrayRows;
}
function get_cell_names_pss(){
    $arrayNames = array('Số lượng đủ điều kiện dự thi:','Đăng ký:','Đăng ký:','Đăng ký:',
    'Số lượng dự thi thực tế:','Thực tế:','Thực tế:','Thực tế:',
    'Tỷ lệ :','Tỷ lệ :','Tỷ lệ :','Tỷ lệ :',
    'Số lượng dự thi thực tế:','Số lượng thi đạt:','Tỷ lệ :','Chữ ký chuyên viên huấn luyện:');
    return $arrayNames;
}
function get_master_cell_pss(){
    $arrayMaster = array('C4','C5','C6','L4','L5','L6');
    return $arrayMaster;
}
function get_loop_cell_pss(){
    $arrayLoop = array('A','B','C','D','E','F','G','H','I',
    'J','K','L','M','N','O','R','S');
    return $arrayLoop;
}
function get_master_cell_cdp(){
    $arrayMaster = array('C3','C4','C5','C6','C7');
    return $arrayMaster;
}
function get_loop_cell_cdp(){
    $arrayLoop = array('A','B','C','D','E','F','G','H','I',
    'J','K','L');
    return $arrayLoop;
}
function get_cohort_cdp_template($id){
    global $DB;
    $template_cohort = $DB->get_record_sql('
    select c.id cid , co.idnumber tenchuongtrinh, 
    c.idnumber malop , c.datestart thoigianhoc ,
    c.address_training diadiemhuanluyen , c.trainer cvhl
    from {cohort} c
    inner join {course} co on co.id = c.courseid
    where c.id = ?',array('id'=>$id));
    if($template_cohort->cid){
        return $template_cohort;
    } else {
        return false;
    }
}
function get_cohort_cdp_members_to_download($cid){
    global $DB;
    // check if TABLE_USER_INFO_DATA NULL
    $sql_check_table_user = 'select max(id) [data] from _Table_USER_INFO_DATA';
    $check_table_user = $DB->get_record_sql($sql_check_table_user,array());
    if($check_table_user->data){
        $sql = '
        SELECT u.id, u.cmnd , 
        u.fullname ,
        u.AD ad_info , u.vanphong vp , u.phone1 dienthoai , 
        cm.date_1 ,cm.date_2 ,cm.date_3 ,cm.date_4 ,cm.date_5 ,cm.date_6 ,cm.participate_condition,
        cm.note
        from mdl232x0_cohort_members cm 
        inner join _Table_USER_INFO_DATA u on u.id = cm.userid
        inner join mdl232x0_cohort c on c.id = cm.cohortid
        where c.id = ?';
        $user_info_pss = $DB->get_records_sql($sql,array('id'=>$cid));
        return $user_info_pss;
    }   else    { // get user from system instead
        $sql = '
        SELECT u.id, u.username, u.firstname, u.lastname, u.phone1 dienthoai
        cm.date_1 ,cm.date_2 ,cm.date_3 ,cm.date_4 ,cm.date_5 ,cm.date_6 ,cm.participate_condition,
        cm.note
        from mdl232x0_cohort_members cm 
        inner join mdl232x0_user u on u.id = cm.userid
        inner join mdl232x0_cohort c on c.id = cm.cohortid
        where c.id = ?';
        $user_info_pss = $DB->get_records_sql($sql,array('id'=>$cid));
        return $user_info_pss;
    }
}
function training_schedule_types_array(){
    $array = array('Tất cả lớp','Upload lớp học');
    return $array;
}
function training_schedule_type_link($url){
    $links = array($url.'/phlcohort/training_schedule/manager.php',
    $url.'/phlcohort/training_schedule/upload/upload_lophoc.php');
    return $links;
}
/* UPLOAD COHORT HEADERS */
function training_schedule_cohort_types_array(){
    $array = array('Tất cả lớp','Upload lớp học','Upload điểm danh PSS');
    return $array;
}
function training_schedule_cohort_type_link($url){
    $links = array($url.'/phlcohort/training_schedule/manager.php',
    $url.'/phlcohort/training_schedule/upload/upload_lophoc.php','');
    return $links;
}
/* UPLOAD COHORT PSS HEADERS */
function training_schedule_cdp_types_array(){
    $array = array('Tất cả lớp','Upload lớp học','Upload điểm danh kỹ năng');
    return $array;
}
function training_schedule_cdp_type_link($url){
    $links = array($url.'/phlcohort/training_schedule/manager.php',
    $url.'/phlcohort/training_schedule/upload/upload_lophoc.php','');
    return $links;
}
/* UPLOAD COHORT CDP HEADERS */
