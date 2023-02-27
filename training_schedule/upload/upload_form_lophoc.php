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

defined('MOODLE_INTERNAL') || die();
header('Content-type: text/plain; charset=utf-8');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/phlcohort/training_schedule/upload/phlcsv_lophoc.php');

/**
 * Cohort upload form class
 *
 * @package    core_cohort
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_upload_form extends moodleform {
    /** @var array new cohorts that need to be created */
    public $processeddata = null;
    /** @var array cached list of available contexts */
    protected $contextoptions = null;
    /** @var array temporary cache for retrieved categories */
    protected $categoriescache = array();

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        $data  = (object)$this->_customdata;

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);

        $mform->addElement('header', 'cohortfileuploadform', get_string('uploadafile'));

        $filepickeroptions = array();
        $filepickeroptions['filetypes'] = '*';
        $filepickeroptions['maxbytes'] = get_max_upload_file_size();
        $mform->addElement('filepicker', 'cohortfile', get_string('file'), null, $filepickeroptions);

        /*
         $choices = csv_import_reader::get_delimiter_list();
         $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_uploadcourse'), $choices);
         if (array_key_exists('cfg', $choices)) {
         $mform->setDefault('delimiter', 'cfg');
         } else if (get_string('listsep', 'langconfig') == ';') {
         $mform->setDefault('delimiter', 'semicolon');
         } else {
         $mform->setDefault('delimiter', 'comma');
         }
         $mform->addHelpButton('delimiter', 'csvdelimiter', 'tool_uploadcourse');
         */

        $mform->addElement('hidden', 'delimiter','comma');
        $mform->setType('delimiter', PARAM_RAW);
        $mform->addElement('hidden', 'encoding','UTF-8');
        $mform->setType('encoding', PARAM_RAW);
        $mform->addElement('hidden', 'contextid',4409);
        $mform->setType('contextid', PARAM_RAW);

        /*
         $choices = core_text::get_encodings();
         $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploadcourse'), $choices);
         $mform->setDefault('encoding', 'UTF-8');
         $mform->addHelpButton('encoding', 'encoding', 'tool_uploadcourse');

         $options = $this->get_context_options();
         $mform->addElement('select', 'contextid', get_string('defaultcontext', 'cohort'), $options);
         */

        $this->add_cohort_upload_buttons(true);
        $this->set_data($data);
    }

    /**
     * Add buttons to the form ("Upload cohorts", "Preview", "Cancel")
     */
    protected function add_cohort_upload_buttons() {
        $mform = $this->_form;

        $buttonarray = array();

        $submitlabel = get_string('uploadcohorts', 'cohort');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $submitlabel);

        $previewlabel = get_string('preview', 'cohort');
        $buttonarray[] = $mform->createElement('submit', 'previewbutton', $previewlabel);
        $mform->registerNoSubmitButton('previewbutton');

        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Process the uploaded file and allow the submit button only if it doest not have errors.
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $cohortfile = $mform->getElementValue('cohortfile');
        $allowsubmitform = false;
        if ($cohortfile && ($file = $this->get_cohort_file($cohortfile))) {
            // File was uploaded. Parse it.
            $encoding = $mform->getElementValue('encoding');
            $delimiter = $mform->getElementValue('delimiter');
            $contextid = $mform->getElementValue('contextid');
            if (!empty($contextid) && ($context = context::instance_by_id($contextid, IGNORE_MISSING))) {
                $this->processeddata = $this->process_upload_file($file, $encoding, $delimiter, $context);
                if ($this->processeddata && count($this->processeddata) > 1 && !$this->processeddata[0]['errors']) {
                    $allowsubmitform = true;
                }
            }
        }
        if (!$allowsubmitform) {
            // Hide submit button.
            $el = $mform->getElement('buttonar')->getElements()[0];
            $el->setValue('');
            $el->freeze();
        } else {
            $mform->setExpanded('cohortfileuploadform', false);
        }

    }

    /**
     * Returns the list of contexts where current user can create cohorts.
     *
     * @return array
     */
    protected function get_context_options() {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');
        if ($this->contextoptions === null) {
            $this->contextoptions = array();
            $displaylist = coursecat::make_categories_list('moodle/cohort:manage');
            // We need to index the options array by context id instead of category id and add option for system context.
            $syscontext = context_system::instance();
            if (has_capability('moodle/cohort:manage', $syscontext)) {
                $this->contextoptions[$syscontext->id] = $syscontext->get_context_name();
            }
            foreach ($displaylist as $cid => $name) {
                $context = context_coursecat::instance($cid);
                $this->contextoptions[$context->id] = $name;
            }
        }
        return $this->contextoptions;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            if (empty($data['cohortfile']) || !($file = $this->get_cohort_file($data['cohortfile']))) {
                $errors['cohortfile'] = get_string('required');
            } else {
                if (!empty($this->processeddata[0]['errors'])) {
                    // Any value in $errors will notify that validation did not pass. The detailed errors will be shown in preview.
                    $errors['dummy'] = '';
                }
            }
        }
        return $errors;
    }

    /**
     * Returns the uploaded file if it is present.
     *
     * @param int $draftid
     * @return stored_file|null
     */
    protected function get_cohort_file($draftid) {
        global $USER;
        // We can not use moodleform::get_file_content() method because we need the content before the form is validated.
        if (!$draftid) {
            return null;
        }
        $fs = get_file_storage();
        $context = context_user::instance($USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
            return null;
        }
        $file = reset($files);

        return $file;

    }

    /**
     * Returns the list of prepared objects to be added as cohorts
     *
     * @return array of stdClass objects, each can be passed to {@link cohort_add_cohort()}
     */
    public function get_cohorts_data() {
        $cohorts = array();
        if ($this->processeddata) {
            foreach ($this->processeddata as $idx => $line) {
                if ($idx && !empty($line['data'])) {
                    $cohorts[] = (object)$line['data'];
                }
            }
        }
        return $cohorts;
    }

    /**
     * Displays the preview of the uploaded file
     */
    protected function preview_uploaded_cohorts() {
        global $OUTPUT;
        if (empty($this->processeddata)) {
            return;
        }
        foreach ($this->processeddata[0]['errors'] as $error) {
            echo $OUTPUT->notification($error);
        }
        foreach ($this->processeddata[0]['warnings'] as $warning) {
            echo $OUTPUT->notification($warning, 'notifymessage');
        }
        $table = new html_table();
        $table->id = 'previewuploadedcohorts';
        $columns = $this->processeddata[0]['data'];
        $columns['contextid'] = get_string('context', 'role');

        // Add column names to the preview table.
        $table->head = array('');
        foreach ($columns as $key => $value) {
            if($value=='idnumber')
                $table->head[] = 'malop';
                elseif($value=='name')
                $table->head[] = 'tenlop';
                elseif($value=='khoahoc')
                $table->head[] = 'chuongtrinh';
                else
                    $table->head[] = $value;
        }
        $table->head[] = get_string('status');

        // Add (some) rows to the preview table.
        $previewdrows = $this->get_previewed_rows();
        foreach ($previewdrows as $idx) {
            $line = $this->processeddata[$idx];
            $cells = array(new html_table_cell($idx));
            $context = context::instance_by_id($line['data']['contextid']);
            foreach ($columns as $key => $value) {
                if ($key === 'contextid') {
                    $text = html_writer::link(new moodle_url('/phlcohort/mnanager.php', array('contextid' => $context->id)),
                        $context->get_context_name(false));
                } else {
                    $text = s($line['data'][$key]);
                }
                $cells[] = new html_table_cell($text);
            }
            $text = '';
            if ($line['errors']) {
                $text .= html_writer::div(join('<br>', $line['errors']), 'notifyproblem');
            }
            if ($line['warnings']) {
                $text .= html_writer::div(join('<br>', $line['warnings']));
            }
            $cells[] = new html_table_cell($text);
            $table->data[] = new html_table_row($cells);
        }
        if ($notdisplayed = count($this->processeddata) - count($previewdrows) - 1) {
            $cell = new html_table_cell(get_string('displayedrows', 'cohort',
                (object)array('displayed' => count($previewdrows), 'total' => count($this->processeddata) - 1)));
            $cell->colspan = count($columns) + 2;
            $table->data[] = new html_table_row(array($cell));
        }
        echo html_writer::table($table);
    }

    /**
     * Find up rows to show in preview
     *
     * Number of previewed rows is limited but rows with errors and warnings have priority.
     *
     * @return array
     */
    protected function get_previewed_rows() {
        $previewlimit = 1000;
        if (count($this->processeddata) <= 1) {
            $rows = array();
        } else if (count($this->processeddata) < $previewlimit + 1) {
            // Return all rows.
            $rows = range(1, count($this->processeddata) - 1);
        } else {
            // First find rows with errors and warnings (no more than 10 of each).
            $errorrows = $warningrows = array();
            foreach ($this->processeddata as $rownum => $line) {
                if ($rownum && $line['errors']) {
                    $errorrows[] = $rownum;
                    if (count($errorrows) >= $previewlimit) {
                        return $errorrows;
                    }
                } else if ($rownum && $line['warnings']) {
                    if (count($warningrows) + count($errorrows) < $previewlimit) {
                        $warningrows[] = $rownum;
                    }
                }
            }
            // Include as many error rows as possible and top them up with warning rows.
            $rows = array_merge($errorrows, array_slice($warningrows, 0, $previewlimit - count($errorrows)));
            // Keep adding good rows until we reach limit.
            for ($rownum = 1; count($rows) < $previewlimit; $rownum++) {
                if (!in_array($rownum, $rows)) {
                    $rows[] = $rownum;
                }
            }
            asort($rows);
        }
        return $rows;
    }

    public function display() {
        // Finalize the form definition if not yet done.
        if (!$this->_definition_finalized) {
            $this->_definition_finalized = true;
            $this->definition_after_data();
        }

        // Difference from the parent display() method is that we want to show preview above the form if applicable.
        $this->preview_uploaded_cohorts();

        $this->_form->display();
    }
    function sheetData($excel) {
        $excel -> ChangeSheet(0);
        $re = '';     // starts html table
        foreach ($excel as $Key => $Row)
        {
            if ($Row)
            {
                if($Row[9]!='')//CMTND not null
                {
                    $re .= implode(',',$Row);
                    $re .= "\r\n";
                }
            }
        }
        return $re;
    }
    function sheetData_BK($sheet) {
        $re = '';     // starts html table
        $x = 1;
        while($x <= $sheet['numRows']) {
            $y = 1;
            while($y <= $sheet['numCols']) {
                $cell =isset($sheet['cells'][$x][$y]) ? ('"'.$sheet['cells'][$x][$y].'",') : '"",';
                $re .= "$cell";
                $y++;
            }
            $re .= "\r\n";
            $x++;
        }
        return $re;
    }
    protected function get_contentdir_from_hash($contenthash) {
        $l1 = $contenthash[0] . $contenthash[1];
        $l2 = $contenthash[2] . $contenthash[3];
        return "$l1/$l2";
    }
    protected function isexist_khoahoc($khoahoc) {
        global $DB;

        $records = $DB->get_records_sql('select * from {course} where fullname=?', array('fullname' =>trim($khoahoc)));
        foreach ($records as $record) {
            return true;
        }
        return false;
    }
    protected function isexist_khuvuc($tenkhuvuc) {
        global $DB;

        $records = $DB->get_records_sql('select * from {cohortphl_khuvuc} where tenkhuvuc=?', array('tenkhuvuc' =>trim($tenkhuvuc)));
        foreach ($records as $record) {
            return true;
        }
        return false;
        //$newRecord=(object)array('tenkhuvuc'=>$item,'mien'=>$mien);
        //return $DB->insert_record('cohortphl_khuvuc',$newRecord);
    }

    /**
     * @param stored_file $file
     * @param string $encoding
     * @param string $delimiter
     * @param context $defaultcontext
     * @return array
     */
    /**
     * PROCESS UPLOAD COHORT FILE
     */
    /* Convert date function */
    protected function custom_render_date($DateTime){
        $array = explode('/', $DateTime);
        $tmp = $array[0];
        $array[0] = $array[1];
        $array[1] = $tmp;
        unset($tmp);
        $result = implode('/', $array);
        return $result;
    }
    protected function custom_throw_updated_date($examcode){
        global $DB;
        $datetest = $DB->get_record_sql('
        select examdate as ngaythi from {examcode} 
        where examcode = ?
        ',array('examcode'=>$examcode));
        if($datetest){
            return $datetest->ngaythi;
        } else {
            return 0;
        }
    }
    protected function compare_cohort_and_examcode($cohortid,$examcodeid){
        global $DB;
        $pairedData = $DB->get_record_sql('
        select id from {cohort} where id = ?
        and examcodeid = ?',
        array('id'=>$cohortid,'examcodeid'=>$examcodeid));
        if($pairedData){
            return true;
        } else {
            return false;
        }
    }
    protected function compare_cohort_and_course($cohortid,$courseid){
        global $DB;
        $pairedData = $DB->get_record_sql('
        select id from {cohort} where id = ?
        and courseid = ?',
        array('id'=>$cohortid,'courseid'=>$courseid));
        if($pairedData){
            return true;
        } else {
            return false;
        }
    }
    protected function sort_distinct_examcode_examdate($file_exams,$file_dates){
        global $DB;
        for($i=0;$i<count($file_exams);$i++){
            if(!empty($file_exams[$i])){
                $exams_has_dates[$i] = $file_exams[$i];
            }
        }
        $grouped_codes = array();
        $count_each_examcode = array_count_values($exams_has_dates);
        foreach($count_each_examcode as $code => $quantity){
            for($i=0;$i<count($file_exams);$i++){
                if($code == $file_exams[$i]){
                    if($quantity > 1){
                        $grouped_codes[$i] = $code;
                    }
                }
            }
        }
        $pair = array();
        foreach($grouped_codes as $g){
            for($i=0;$i<count($file_exams);$i++){
                if($g == $file_exams[$i]){
                    $pair[$i] = $file_exams[$i].'/'.$file_dates[$i];
                }
            }
        }
        $unique_codes = array_unique($grouped_codes);
        $unique_pair = array_unique($pair);
        $key_pair = array();    $key_examcode = array();
        foreach($unique_codes as $index => $pair){
            $key_examcode[] = $index;
        }
        foreach($unique_pair as $index => $pair){
            $key_pair[] = $index;
        }
        $diff_keys = array_diff($key_pair,$key_examcode);
        if($diff_keys){
            return $diff_keys;
        } else {
            return false;
        }
    }

    protected function process_upload_file($file, $encoding, $delimiter, $defaultcontext) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->libdir . '/filestorage/file_system_filedir.php');
        require($CFG->dirroot.'/phlcohort/excel_reader/PHPExcel/lib.php');     // include the class
        

        $cohorts = array(
            0 => array('errors' => array(), 'warnings' => array(), 'data' => array())
        );

        //$fs->get_file_by_hash(sha1($fullpath));
        $fileDir=new  file_system_filedir;
        //  echo "Get file dir : ".$fileDir."<br>";
        $filePath=$CFG->dataroot.'/filedir/'.$this->get_contentdir_from_hash($file->get_contenthash()).'/'.$file->get_contenthash();
        $content =readExcelToCommaCSV($filePath);
        //echo $content;
        if (!$content) {

            $cohorts[0]['errors'][] = "No file is found";
            return $cohorts;
        }

        $uploadid = phl_csv::get_new_iid('uploadcohort');
        $cir = new phl_csv($uploadid, 'uploadcohort');
        $readcount = $cir->phl_load_csv_content($content, $encoding, $delimiter);
        unset($content);
        if (!$readcount) {
            //echo "X1";
            $cohorts[0]['errors'][] = get_string('csvloaderror', 'error', $cir->get_error());

            return $cohorts;
        }
        $columns = $cir->get_columns();
        //Check that columns include 'name' and warn about extra columns.
        $allowedcolumns = array('cohorttype','area','aduser','trainer','datetest','note'
            ,'office','testcode','examiner','datestart','dateend','address_training','city_training','pss_quantity','address_test','cancel_flag'
            ,'error_desc','city_test','test_form','online','idnumber','name'
        );
       
        //$additionalcolumns = array('context', 'category', 'category_id', 'category_idnumber', 'category_path');
        $additionalcolumns = array();
        $displaycolumns = array();
        $extracolumns = array();
        $columnsmapping = array();
        foreach ($columns as $i => $columnname) {
            $columnnamelower = preg_replace('/ /', '', core_text::strtolower($columnname));
            $columnsmapping[$i] = null;
            if (in_array($columnnamelower, $allowedcolumns)) {
                $displaycolumns[$columnnamelower] = $columnname;
                $columnsmapping[$i] = $columnnamelower;
            } else if (in_array($columnnamelower, $additionalcolumns)) {
                $columnsmapping[$i] = $columnnamelower;
            } else {
                $extracolumns[] = $columnname;
            }
        }
        if (!in_array('name', $columnsmapping)) {

            $cohorts[0]['errors'][] = new lang_string('namecolumnmissing', 'cohort');
            return $cohorts;
        }
        if ($extracolumns) {
            //$cohorts[0]['warnings'][] = new lang_string('csvextracolumns', 'cohort', s(join(', ', $extracolumns)));
        }

        if (!isset($displaycolumns['contextid'])) {
            $displaycolumns['contextid'] = 'contextid';
        }
        $cohorts[0]['data'] = $displaycolumns;

        // Parse data rows.
        $cir->init();
        $rownum = 0;
        $idnumbers = array();
        $cities = array();
        $haserrors = false;
        $haswarnings = false;
        //$cohort_exist = array();
        $cohort_updated = 0;


        $ma_lop_array = array(); // 12052022 lưu mã thi chữ vào mảng để tìm dữ liệu trùng lặp
        $stt_ma_lop = 0;
        $total_ma_lop = 0;
        /*
            KIEM TRA DU LIEU
        */
        /* 14/12/2022 validate pairs */
        $file_exams = array();  $file_dates = array();  $exams_has_dates = array();
        $cohorts_error = array();   $case1 = false; $case1_cohorts = array();
        $unique_cohortid = array();
        while ($row = $cir->next()) {
            $rownum++;
            $cohorts[$rownum] = array(
                'errors' => array(),
                'warnings' => array(),
                'data' => array(),
                'OK' => array(),
            );
            $hash = array();
            foreach ($row as $i => $value) {
                if ($columnsmapping[$i]) {
                    $hash[$columnsmapping[$i]] = $value;
                }
            }
            $this->clean_cohort_data($hash);

            $warnings = $this->resolve_context($hash, $defaultcontext);
            $cohorts[$rownum]['warnings'] = array_merge($cohorts[$rownum]['warnings'], $warnings);
            $mark[$rownum] = 0;
            // kiem tra tren file excel
  /*          if (empty($hash['cohorttype']))
            {
              $cohorts[$rownum]['errors'][] = "Loại lớp bỏ trống<br>";
            }
            if (empty($hash['area']))
            {
              $cohorts[$rownum]['errors'][] = "Miền bỏ trống<br>";
            }
            if (empty($hash['office']))
            {
              $cohorts[$rownum]['errors'][] = "Văn phòng bỏ trống<br>";
            }

            /**
            * Check examcode on DB
            * nếu mã thi chữ có thì kiểm tra trong DB xem có tồn tại không? nếu chưa có thì insert vào cohort_examcode
            * Nếu có thì kiểm tra xem cohortid có đúng ? 1 lớp có nhiều mã thi và mã thi chỉ có 1 lớp
            * nếu mã thi chữ không có thì chỉ thao tác tạo lớp trên cohort và enrol (nếu là online)
            
            if (empty($hash['idnumber']))
            {
              $cohorts[$rownum]['errors'][] = "Mã lớp bỏ trống<br>";
            }else{
                $stt_ma_lop++;
                $total_ma_lop = $total_ma_lop + $stt_ma_lop;
                $ma_lop_array[$stt_ma_lop] = $hash['idnumber'];//INSERT MÃ LỚP VÀO MẢNG ĐỂ KIỂM TRA 

                if (!empty($hash['testcode'])) 
                {
                    /**
                     * Nếu có mã thi thì rang buộc các giá trị về thi
                     
                    $stt_ma_thi++;
                    $total_ma_thi = $total_ma_thi + $stt_ma_thi;
                    $ma_thi_chu_array[$stt_ma_thi] = $hash['testcode'];//INSERT MÃ THI CHỮ VÀO MẢNG ĐỂ KIỂM TRA 
                    
                    $examcode = $DB->get_record_sql('select TOP 1 id from {cohort_examcode} where examcode = ?', array('examcode'=>$hash['testcode']));
                   
                    if(!empty($examcode->id) )
                    {
                        $cohort_id = $DB->get_record_sql('select cohortid from {cohort_examcode} where examcode = ?', array('examcode'=>$hash['testcode']));
                        $cohort_idnumber = $DB->get_record_sql('select idnumber from {cohort} where id = ?', array('id'=>$cohort_id->cohortid));
                        
                        if($cohort_idnumber->idnumber != $hash['idnumber']){
                            $cohorts[$rownum]['errors'][] = 'Mã thi '.$hash['testcode'].' đã tồn tại và không đồng nhất với mã lớp.';
                        }
                    }
                    else{
                        $validate_cohort_examcode = $DB->get_record_sql('
                        select c.idnumber,cma.examcode
                        from {cohort} c
                        inner join {cohort_examcode} cma on cma.cohortid = c.id
                        where c.idnumber = ?
                        ',array('idnumber'=>$hash['idnumber']));
                        if($validate_cohort_examcode){
                            $cohorts[$rownum]['errors'][] = 'Lớp '.$validate_cohort_examcode->idnumber.' đã có mã thi '.$validate_cohort_examcode->examcode;
                        }
                    }
                    // else{
                    //     $cohort_id = $DB->get_record_sql('select id from {cohort} where idnumber = ?', array('id'=>$hash['idnumber']));
                    //     $examcode = $DB->get_record_sql('select top 1 examcode from {cohort_examcode} where cohortid = ?', array('cohortid'=>$cohort_id->id));
                    //     if(!empty($examcode->examcode)){
                    //         if($examcode->examcode != $hash['testcode']){
                    //             $cohorts[$rownum]['errors'][] = 'Mã thi '.$hash['testcode'].' không đồng nhất với mã lớp.';
                    //         }
                    //     }
                    // } 
                }               
            }
                    
            //END kiểm tra mã thi chữ

            if (empty($hash['datetest']))
            {
              $cohorts[$rownum]['errors'][] = "Ngày thi bỏ trống<br>";
            } else {
                $formatDateTest = strtotime(renderDateUploadLop($hash['datetest']));
                if(!$formatDateTest){
                    $cohorts[$rownum]['errors'][] = "Ngày thi không hợp lệ. Kiểm tra lại vị trí ngày/tháng/năm<br>";
                }
                else {
                    $isDateTest = throwDateTestUpdate($hash['idnumber']);
                    if($isDateTest!=0){
                        $convertedDateTest = date("d/m/Y",$isDateTest);
                        $convertedFileDateTest = date("d/m/Y",$formatDateTest);
                        $cohorts[$rownum]['warnings'][] = "Cập nhật ngày thi từ $convertedDateTest sang $convertedFileDateTest";
                    }
                }
            }
            if (empty($hash['datestart']))
            {
              $cohorts[$rownum]['errors'][] = "Ngày bắt đầu bỏ trống<br>";
            } else {
                if (empty($hash['dateend']))
                {
                $cohorts[$rownum]['errors'][] = "Ngày kết thúc bỏ trống<br>";
                } else {
                    $formatDateStart = strtotime(renderDateUploadLop($hash['datestart']));
                    if($formatDateStart){
                        $formatDateEnd = strtotime(renderDateUploadLop($hash['dateend']));
                        if($formatDateEnd){
                            $withinRange = $formatDateEnd - $formatDateStart;
                            if($withinRange < 0){
                                $cohorts[$rownum]['errors'][] = "Ngày bắt đầu và ngày kết thúc không hợp lệ<br>";
                            }
                        } else {
                            $cohorts[$rownum]['errors'][] = "Ngày kết thúc không hợp lệ. Kiểm tra lại vị trí ngày/tháng/năm<br>";
                        }
                    } else {
                        $cohorts[$rownum]['errors'][] = "Ngày bắt đầu không hợp lệ. Kiểm tra lại vị trí ngày/tháng/năm<br>";
                    }
                }
            }
            

            if($hash['online']==0||empty($hash['online']))
            {
			  if (empty($hash['city_test']))
              {
                $cohorts[$rownum]['errors'][] = "Thành phố thi bỏ trống<br>";
              }
              if (empty($hash['city_training']))
              {
                $cohorts[$rownum]['errors'][] = "Thành phố huấn luyện bỏ trống<br>";
              }
              if (empty($hash['address_test']))
              {
                $cohorts[$rownum]['errors'][] = "Địa điểm thi bỏ trống<br>";
              }
              if (empty($hash['address_training']))
              {
                $cohorts[$rownum]['errors'][] = "Địa điểm huấn luyện bỏ trống<br>";
              }
            }

            // ket thuc kiem tra tren file excel
            // Kiểm tra thành phố, khóa học có đúng không
            if (!$DB->record_exists('course', array('idnumber' => $hash['cohorttype']))) 
            {
              $cohorts[$rownum]['errors'][] = "Khóa học không có trong hệ thống";
            }
            if (!$DB->record_exists('cohortphl_khuvuc', array('tenkhuvuc' => $hash['city_test']))) 
            {
              $cohorts[$rownum]['errors'][] = "Thành phố thi không có trong hệ thống";
            }
            if (!$DB->record_exists('cohortphl_khuvuc', array('tenkhuvuc' => $hash['city_training']))) 
            {
              $cohorts[$rownum]['errors'][] = "Thành phố huấn luyện không có trong hệ thống";
            }
            /**
             * CHECK COHORT ONLINE TYPE WITH DATABASE
            global $DB;
			$mycohorts = $DB->get_records_sql('select id, online from {cohort} where idnumber=?', array('idnumber' =>$hash['idnumber']));
            foreach ($mycohorts as $mycohort) {
                $cohort_id = $mycohort->id;
                $ori_online = $mycohort->online;
                //echo $ori_online;
                if($ori_online == 1){
                    
                    if($hash['online']==0||empty($hash['online'])){//--------------------Reject if update cohort from online to offline
                         //echo 'TESTTTTTTTTTTTTTTTTTTT';
                        $cohorts[$rownum]['errors'][] = "Cập nhật từ online sang offline bị từ chối";      
                    }
                   
                }else{
                    if($hash['online']==1){//-----------------------Reject if update cohort from offline to online but cohort already have users
                        $demhocvien = $DB->get_records_sql('select id from {cohort_members} where cohortid = ?',array('cohortid'=>$cohort_id));
                        if(count($demhocvien) > 0)
                        {
                            $cohorts[$rownum]['errors'][] = "Cập nhật từ offline sang online bị từ chối do lớp đã có học viên";
                        }        
                    }
                }
            }    
            /**
             * CHECK COHORT ONLINE TYPE WITH DATABASE 
            //COUNT NUMBER OF NEW OR UPDATE
            if (!empty($hash['idnumber'])||isset($idnumbers[$hash['idnumber']])) {
                if ($DB->record_exists('cohort', array('idnumber' => $hash['idnumber'])))
                {
                    $cohort_exist[$rownum] = 1;
					
					
                }
                $idnumbers[$hash['idnumber']] = true;
            }
			//END COUNT NUMBER OF NEW OR UPDATE

            $cohorts[$rownum]['data'] = array_intersect_key($hash, $cohorts[0]['data']);
            $haserrors = $haserrors || !empty($cohorts[$rownum]['errors']);
            $haswarnings = $haswarnings || !empty($cohorts[$rownum]['warnings']);           */
            /* 519 -> 730 OLD FILE */
        /* UPDATED 08/12/2022 EDIT FROM HERE */
        $foundError = 0;
        $groupingError = '';
        /* File fields check empty */
        if (empty($hash['cohorttype'])){
            $foundError += 1;
            $groupingError .= $foundError." - ". "Chưa điền cột loại lớp<br>";
        }   else {
            if (empty($hash['area'])){
                $foundError += 1;
                $groupingError .= $foundError." - ". "Chưa điền cột miền<br>";
            }   else {
                if (empty($hash['office'])){
                    $foundError += 1;
                    $groupingError .= $foundError." - ". "Chưa điền cột văn phòng<br>";
                }   else {
                    if (empty($hash['idnumber'])){
                        $foundError += 1;
                        $groupingError .= $foundError." - ". "Chưa điền cột mã lớp<br>";
                    }   else {
                        if (empty($hash['datetest'])){
                            $hash['testcode'] = trim($hash['testcode']);
                            if( !(empty($hash['testcode']))){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". "Chưa điền cột ngày thi<br>";
                            }                            
                        }   else {
                            $file_date = strtotime($this->custom_render_date($hash['datetest']));
                            if(!$file_date){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) thi không hợp lệ<br>";
                            } else {
                            /*    $db_exam_date = $this->custom_throw_updated_date($hash['testcode']);
                                if($db_exam_date!=0){
                                    $rendered_db_date = date("d/m/Y",$db_exam_date);
                                    $rendered_file_date = date("d/m/Y",$file_date);
                                    $cohorts[$rownum]['warnings'][] = "Cập nhật ngày thi từ $rendered_db_date sang $rendered_file_date<br>";
                                }*/
                            }
                            if (empty($hash['datestart'])){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". "Chưa điền cột ngày bắt đầu lớp<br>";
                            }   else {
                                $start_date = strtotime($this->custom_render_date($hash['datestart']));
                                if (empty($hash['dateend'])){
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". "Chưa điền cột ngày kết thúc lớp<br>";
                                }   else {
                                    $end_date = strtotime($this->custom_render_date($hash['dateend']));
                                    if( $start_date ){
                                        if( $end_date ){
                                            $within_range = $end_date - $start_date;
                                            if( $within_range < 0){
                                                // in case end date is bigger than start date.
                                                $foundError += 1;
                                                $groupingError .= $foundError." - ". "Ngày mở lớp phải tạo trước ngày đóng lớp.<br>";
                                            }else{
                                                if($file_date < $end_date){$foundError += 1;$groupingError .= $foundError." - ". "Ngày thi phải tạo sau ngày đóng lớp.<br>";}
                                            }
                                            // some cohort has same start and end date so
                                            // we do not set case $within_range = 0
                                        } else {$foundError += 1;$groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) kết thúc lớp không hợp lệ<br>";}
                                    } else {$foundError += 1;$groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) bắt đầu lớp không hợp lệ<br>";}
                          /*          // Lớp thuộc OFFLINE phải có 4 cột thi & huấn luyện
                                    if ($hash['online'] == 0 || empty($hash['online']) ){
                                        if (empty($hash['city_test'])){
                                            $foundError += 1;
                                            $groupingError .= $foundError." - ". "Chưa điền cột TP thi<br>";
                                        }   else {
                                            if (empty($hash['city_training'])){
                                                $foundError += 1;
                                                $groupingError .= $foundError." - ". "Chưa điền cột TP huấn luyện<br>";
                                            }   else {
                                                if (empty($hash['address_test'])){
                                                    $foundError += 1;
                                                    $groupingError .= $foundError." - ". "Chưa điền cột địa điểm thi<br>";
                                                }   else {
                                                    if (empty($hash['address_training'])){
                                                        $foundError += 1;
                                                        $groupingError .= $foundError." - ". "Chưa điền cột địa điểm huấn luyện<br>";
                                                    }}}}}
                                                */
                                                }}}}}}
        }    
        if($foundError > 0){
            $cohorts[$rownum]['errors'][] = 'Lỗi file ('.$foundError.'): <br><br>'.$groupingError;
        } else {
            /* Validate basic fields - check course and related informations */
        /*    $cohorttype = $DB->get_record_sql('select id from {course} where idnumber = ?',array('idnumber'=>$hash['cohorttype']));
            if(!$cohorttype){
                $foundError += 1;
                $groupingError .= $foundError." - ". 'Loại lớp '.$hash['cohorttype'].' không có trong hệ thống<br>';
            }   else {
                if($hash['city_test']){
                    $city_test = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$hash['city_test']));
                    if(!$city_test){
                        $foundError += 1;
                        $groupingError .= $foundError." - ". 'TP thi '.$hash['city_test'].' không có trong hệ thống<br>';
                    }   else {
                        if($hash['city_training']){
                            $city_training = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$hash['city_training']));
                            if(!$city_training){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". 'TP huấn luyện '.$hash['city_training'].' không có trong hệ thống<br>';
                            }   else {
                            }}}}
            }       */
        /* Validate basic fields - check test date must be accurate before updating*/
            if($foundError > 0){
                $cohorts[$rownum]['errors'][] = 'Lỗi thông tin khóa ('.$foundError.'): <br><br>'.$groupingError;
            }else{
                        /*  Validate complicated fields - Only if examcode file Excel is filled */
                    require_once($CFG->dirroot.'/phlcohort/training_schedule/upload/lib.php');
                    $cohort_exist = check_exist_cohort($hash['idnumber']);
                    $cohort_has_examcode = false;
                    $cohort_has_data = false;
                    if($cohort_exist){
                        $cohort_has_data = true;
                        $cohort_updated+=1;
                        if($cohort_exist->examcodeid){$cohort_has_examcode = true;}}
                    // called lib.php to use 3 functions checking, validating cohort and examcode
                    $cohorttype = $DB->get_record_sql('select id from {course} where idnumber = ?',array('idnumber'=>$hash['cohorttype']));
                        if(!$cohorttype){//1
                            $foundError += 1;
                            $groupingError .= $foundError." - ". 'Loại lớp '.$hash['cohorttype'].' không có trong hệ thống<br>';
                    }
                    $hash['testcode'] = trim($hash['testcode']);
                    if( !(empty($hash['testcode'])))   { // examcode is not empty => this cohort will have examcode
                        /* UPDATED 23/12/2022 */
                        if (empty($hash['city_test'])){
                            $foundError += 1;
                            $groupingError .= $foundError." - ". "Chưa điền cột TP thi<br>";
                        }   else {
                            if (empty($hash['city_training'])){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". "Chưa điền cột TP huấn luyện<br>";
                            }   else {
                                if (empty($hash['address_test'])){
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". "Chưa điền cột địa điểm thi<br>";
                                }   else {
                                    if (empty($hash['address_training'])){
                                        $foundError += 1;
                                        $groupingError .= $foundError." - ". "Chưa điền cột địa điểm huấn luyện<br>";
                                    }}}}
                            /* Validate basic fields - check course and related informations */
                            if($hash['city_test']){
                                $city_test = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$hash['city_test']));
                                if(!$city_test){
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". 'TP thi '.$hash['city_test'].' không có trong hệ thống<br>';
                                }   else {
                                    if($hash['city_training']){
                                        $city_training = $DB->get_record_sql('select id from {cohortphl_khuvuc} where tenkhuvuc = ?',array('tenkhuvuc'=>$hash['city_training']));
                                        if(!$city_training){
                                            $foundError += 1;
                                            $groupingError .= $foundError." - ". 'TP huấn luyện '.$hash['city_training'].' không có trong hệ thống<br>';
                                        }   else {
                                            /* CONTINUE VALIDATE FROM HERE */
                                        }}}}
                        /* */

                        if($cohort_has_examcode){
                            $examcode_exist = check_exist_examcode($hash['testcode']);  // ID examcode
                            if($examcode_exist){ // uploaded examcode has exist in DB
                                // but we dont know if their id are match with cohort examcodeid field
                                if($examcode_exist->id == $cohort_exist->examcodeid){ // cohort matched with examcode
                                    if($examcode_exist->examdate == strtotime($this->custom_render_date($hash['datetest']))){// check examcode date and file date
                                        $pair_cohort_examcode = check_cohort_examcode($cohort_exist->id,$examcode_exist->id);
                                        if($pair_cohort_examcode){
                                            if($examcode_exist->examdate == $pair_cohort_examcode->examdate){// check examcode date and cohort_examcode date
                                                // INSERT/UPDATE will continue from here
                                            }else{$foundError += 1;$groupingError .= $foundError." - ". 'Ngày thi '.$hash['datetest'].' không khớp với ngày thi '.date("d/m/Y",$examcode_exist->examdate).' đã đăng ký cho mã thi '.$examcode_exist->examcode.'(TH2)<br>';}
                                        }
                                        // if pair not exist yet then we will insert it in
                                        
                                        //$foundError += 1;$groupingError .= $foundError." - ". 'Mã thi và ngày thi khớp với file<br>';
                                    }else{$foundError += 1;$groupingError .= $foundError." - ". 'Ngày thi '.$hash['datetest'].' không khớp với ngày thi '.date("d/m/Y",$examcode_exist->examdate).' đã đăng ký cho mã thi '.$examcode_exist->examcode.'(TH1)<br>';}
                                }else{$foundError += 1;$groupingError .= $foundError." - ". 'Mã thi '.$hash['testcode'].' không đúng với mã thi đã đăng ký cho lớp '.$hash['idnumber'].'<br>';}
                            }else{$foundError += 1;$groupingError .= $foundError." - ". 'Không thể đăng ký mã thi mới cho lớp '.$hash['idnumber'].' đã có mã thi từ trước<br>';}
                            //$cohort_exist = check_exist_cohort($hash['idnumber']); !! FATAL DATA ERROR AS DUPLICATED OLD IDs !!
                            
                        }else{
                            if((!$cohort_has_examcode)&&$cohort_has_data){
                                $foundError += 1;$groupingError .= $foundError." - ". 'Không được đăng ký mã thi cho những lớp không đi thi<br>';
                            }
                        }
                    }
                    /* UPDATED 22/12/2022
                        - Case: Empty examcode cell
                        - Bug: Cohort PSS DMG 0001Y (DMG 0001A) did not throw error
                        - Fixed: Line 949, added a condition if empty cell then check if cohort examcodeid is null
                    */
                    else{   //if( (empty($hash['testcode'])))
                        /* UPDATED 23/12/2022 */
                        if( empty($hash['testcode'])    ){ //put this to make sure cell is empty
                            /* */   
                            if($cohort_has_data){ //cohort exists but we dont know if it has examcode yet
                                if($cohort_has_examcode){$foundError += 1;$groupingError .= $foundError." - ". 'Lớp đi thi không được để trống cột mã kỳ thi<br>';}
                            }
                        }
                    }

                        /*            if( $examcode_exist && $cohort_exist ){
                            // If both cohort and examcode exist before then we must
                            // check their relationship, 1:N applied for examcode/cohorts
                            $paired = $this->compare_cohort_and_examcode($cohort_exist->id,$examcode_exist);
                            if(!$paired ){
                                $no_exam ='';
                                if( (!$cohort_exist->examcodeid) ){
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". 'Lớp không đi thi'.$hash['idnumber'].' không được cập nhật mã thi';    
                                    //$cohorts[$rownum]['warnings'][] = "Cập nhật mã thi cho những lớp case cũ";
                                } else {
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". 'Mã thi upload '.$hash['testcode'].' không đúng với mã thi đã đăng ký cho lớp '.$hash['idnumber'].'<br>';   
                                }
                            }
                        }           */   
                     
                    /* Validate if course is assigned to the correct cohort (if exist before) */
                    if($cohort_exist){
                        if(!($this->compare_cohort_and_course($cohort_exist->id,$cohorttype->id))){
                            $foundError += 1;
                            $groupingError .= $foundError." - ". 'Khóa học upload không đúng với khóa học đã đăng ký cho lớp '.$hash['idnumber'].$cohort_exist->id.'//'.$cohorttype->id.'<br>';                
                        }
                    }
                    /*  Validate complicated fields ENDS */

                    /* Check update cohort Online/Offline type */
                    //$cohort = $DB->get_record_sql('select online,id from {cohort} where idnumber = ?',array('idnumber'=>$hash['idnumber']));
                    if($cohort_exist){    // has value means this is an updated cohort
                        /* case 1: Cannot update Online (1) to Offline (0) */
                        if($cohort_exist->online == 1){
                            if($hash['online'] == 0 || empty($hash['online'])){
                                $foundError += 1;
                                $groupingError .= $foundError." - ". "Loại lớp Online không được update sang Offline<br>";      
                            }
                        /* case 2: Offline update to Online only if cohort_members = 0 */
                        }   else {
                            if($hash['online'] == 1){
                                $cohort_members = $DB->get_records_sql('select id from {cohort_members} where cohortid = ?',array('cohortid'=>$cohort_exist->id));
                                if (count($cohort_members) > 0){
                                    $foundError += 1;
                                    $groupingError .= $foundError." - ". 'Lớp '.$hash['idnumber'].' không được update từ Offline sang Online khi vẫn còn học viên đang đăng ký<br>';      
                                }
                            }
                        }
                    }
                    /* Check update cohort Online/Offline type ends */
                    if($foundError > 0){
                        $cohorts[$rownum]['errors'][] = 'Lỗi thông tin ('.$foundError.'): <br><br>'.$groupingError;
                    }else{}
            }
        }
        /* File fields check empty ends */

        /*$file_date = strtotime($this->custom_render_date($hash['datetest']));
        if(!$file_date){
            $foundError += 1;
            $groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) thi không hợp lệ<br>";
        } else {
            $db_exam_date = $this->custom_throw_updated_date($hash['testcode']);
            if($db_exam_date!=0){
                $rendered_db_date = date("d/m/Y",$db_exam_date);
                $rendered_file_date = date("d/m/Y",$file_date);
                $cohorts[$rownum]['warnings'][] = "Cập nhật ngày thi từ $rendered_db_date sang $rendered_file_date<br>";
            }
        }*/
        /* Validate basic fields - start and end cohort dates must be accurate */
        /*$start_date = strtotime($this->custom_render_date($hash['datestart']));
        $end_date = strtotime($this->custom_render_date($hash['dateend']));
        if( $start_date ){
            if( $end_date ){
                $within_range = $end_date - $start_date;
                if( $within_range < 0){
                    // in case end date is bigger than start date.
                    $foundError += 1;
                    $groupingError .= $foundError." - ". "Ngày mở lớp phải tạo trước ngày đóng lớp.<br>";
                }
                // some cohort has same start and end date so
                // we do not set case $within_range = 0
            } else {
                $foundError += 1;
                $groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) kết thúc lớp không hợp lệ<br>";
            }
        } else {
            $foundError += 1;
            $groupingError .= $foundError." - ". "Định dạng ngày (d/m/Y) bắt đầu lớp không hợp lệ<br>";
        }*/

        /* Validate basic fields ends */

        /* Custom finalizing all errors if found */
        if($foundError > 0){
            $case1 = true;
            $case1_cohorts[] = $hash['idnumber'];
        }  

        $cohorts[$rownum]['data'] = array_intersect_key($hash, $cohorts[0]['data']);
        $haserrors = $haserrors || !empty($cohorts[$rownum]['errors']);
        $haswarnings = $haswarnings || !empty($cohorts[$rownum]['warnings']);

        /* AFTER VALIDATE, GROUP ALL THEM IN ARRAY */
        $stt_ma_lop++;
        $total_ma_lop = $total_ma_lop + $stt_ma_lop;
        $ma_lop_array[$stt_ma_lop] = $hash['idnumber'];
        $file_exams[] = $hash['testcode']; // also grouped empty examcode
        $file_dates[] = $hash['datetest'];
    }
    /*    if  (!empty($hash['testcode'])){
            $sum_examcodes_array[$index_pair] = $hash['testcode'];
            $sum_examdate_array[$index_pair] = $hash['datetest'];
            
            $index_pair += 1;
            $quantity_pair = $index_pair + 1;
        }       */
        /* UPDATED 08/12/2022 ENDs */
        
    $differents = $this->sort_distinct_examcode_examdate($file_exams,$file_dates);
    $case2 = false; $case2_cohorts = array();
    if($differents){
    //    $df -- 2  6
        for($i=1;$i<=$stt_ma_lop;$i++){
            foreach($differents as $d){
                if($i == $d){
                    $haserrors = true;
                    $case2 = true;
                    $case2_cohorts[] = $ma_lop_array[$i+1];
                    $cohorts[$i+1]['errors'][] = "Mã thi có ngày thi không khớp trong file upload";
                }
            }
        }  
    }
        /*  - New case is checking examcode then pair
            - Each pair within a cell must be unique depend on the examcode
            - Pair must be matched with examcode quantities */
        // Merged all examcodes into an array 
    /*    $merged_examcodes = array();
        $o = 0;
        for($i=0;$i<$quantity_pair;$i++){
            $z = 0;
            for($j=0; $j< $quantity_pair; $j++){
                if($sum_examcodes_array[$o] == $sum_examcodes_array[$z] && $o!=$z){
                    $merged_examcodes[$o] = $sum_examcodes_array[$o];
                }
                $z += 1;
            }
            $o += 1;
        }
        // Merge paired examcodes - examdates
        $merged_examcode_examdate = array();
        $o = 0;
        for($i=0;$i<$quantity_pair;$i++){
            $z = 0;
            for($j=0; $j< $quantity_pair; $j++){
                if($sum_examcodes_array[$o] == $sum_examcodes_array[$z] && $o!=$z){
                    $merged_examcode_examdate[$o] = $sum_examcodes_array[$o].':'.$sum_examdate_array[$o];
                }
                $z += 1;
            }
            $o += 1;
        }
        $get_diff_pair_index = array();
        $key_examcode = array();   $key_pairs = array();
        $unique_examcodes = array_unique($merged_examcodes);
        foreach($unique_examcodes as $key => $value){
            $key_examcode[] = $key;
        }
        $unique_pairs = array_unique($merged_examcode_examdate);
        foreach($unique_pairs as $key => $value){
            $key_pairs[] = $key;
        }
        if(count($unique_pairs) != count($unique_examcodes)){
            $diff_pairs = array_diff($key_pairs,$key_examcode);
        }
        foreach($diff_pairs as $d){
            $haserrors = true;
            $cohorts[$d]['errors'][] = "Mã thi có ngày thi khác nhau trong file";
            
        }           */
        /*  - Below codes are used for checking duplicated cohorts within file.
            - Each cohort in each cell must be unique   */
        $unique = array_unique($ma_lop_array);
        $duplicates = array_diff_assoc($ma_lop_array, $unique);
        for($i=0;$i<$total_ma_lop;$i++)
        {
            if(!empty($duplicates[$i]))
            {
                $haserrors = true;
                $cohorts[$i]['errors'][] = "Mã lớp $duplicates[$i] bị trùng lặp trong file upload.";
            }
        }   
        /**
         * END KIỂM TRA MÃ THI CHỮ DUY NHẤT TRONG FILE */

/* UPDATED 08/12/2022 ENDs */
        if ($haserrors) {
            if($case1 && $case2){
                $cohort_merged = array_unique(array_merge($case1_cohorts,$case2_cohorts));
                $cohort_imploded = implode(',',$cohort_merged);
                $cohorts[0]['errors'][] = "!! Lỗi File upload - Kiểm tra ngày thi/thông tin các lớp ".$cohort_imploded; // Annoying Orange
            } else {
                if($case1){
                    $cohorts_error_name = implode(',',$case1_cohorts);
                    $cohorts[0]['errors'][] = "! Lỗi File upload - Kiểm tra thông tin lớp ".$cohorts_error_name;
                }   else {
                    if($case2){
                        $cohorts_error_name = implode(',',$case2_cohorts);
                        $cohorts[0]['errors'][] = "! Lỗi File upload - Kiểm tra ngày thi lớp ".$cohorts_error_name;
                    }
                }
            }
        }

        if ($haswarnings) {
            $cohorts[0]['warnings'][] = "Cập nhật File upload";
        }
        $new_cohort = $rownum - $cohort_updated;
        echo "
            <style>
            table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
            }

            td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
            }

            tr:nth-child(even) {
            background-color: #FFE87F;
            }
            th {
            background-color: #F3C700;
            }
            </style>
            ";
            echo "<table>
            <tr>
                <th>SL Lớp Cập Nhật</th>
                <th>SL Lớp Tạo mới</th>
            </tr>
            <tr>
                <td>$cohort_updated</td>
                <td>$new_cohort</td>
            </tr>
            </table>
            <br>
            ";
        return $cohorts;
    }
    /** 
     * END PROCESS UPLOAD COHORT FILE 
     * */
    /**
     * Cleans input data about one cohort.
     *
     * @param array $hash
     */
    protected function clean_cohort_data(&$hash) {
        foreach ($hash as $key => $value) {
            switch ($key) {
                case 'contextid': $hash[$key] = clean_param($value, PARAM_INT); break;
                case 'name': $hash[$key] = core_text::substr(clean_param($value, PARAM_TEXT), 0, 254); break;
                case 'idnumber': $hash[$key] = core_text::substr(clean_param($value, PARAM_RAW), 0, 254); break;
                case 'description': $hash[$key] = clean_param($value, PARAM_RAW); break;
                case 'descriptionformat': $hash[$key] = clean_param($value, PARAM_INT); break;
                case 'visible':
                    $tempstr = trim(core_text::strtolower($value));
                    if ($tempstr === '') {
                        // Empty string is treated as "YES" (the default value for cohort visibility).
                        $hash[$key] = 1;
                    } else {
                        if ($tempstr === core_text::strtolower(get_string('no')) || $tempstr === 'n') {
                            // Special treatment for 'no' string that is not included in clean_param().
                            $value = 0;
                        }
                        $hash[$key] = clean_param($value, PARAM_BOOL) ? 1 : 0;
                    }
                    break;
            }
        }
    }

    /**
     * Determines in which context the particular cohort will be created
     *
     * @param array $hash
     * @param context $defaultcontext
     * @return array array of warning strings
     */
    protected function resolve_context(&$hash, $defaultcontext) {
        global $DB;

        $warnings = array();

        if (!empty($hash['contextid'])) {
            // Contextid was specified, verify we can post there.
            $contextoptions = $this->get_context_options();
            if (!isset($contextoptions[$hash['contextid']])) {
                $warnings[] = new lang_string('contextnotfound', 'cohort', $hash['contextid']);
                $hash['contextid'] = $defaultcontext->id;
            }
            return $warnings;
        }

        if (!empty($hash['context'])) {
            $systemcontext = context_system::instance();
            if ((core_text::strtolower(trim($hash['context'])) ===
                core_text::strtolower($systemcontext->get_context_name())) ||
                ('' . $hash['context'] === '' . $systemcontext->id)) {
                    // User meant system context.
                    $hash['contextid'] = $systemcontext->id;
                    $contextoptions = $this->get_context_options();
                    if (!isset($contextoptions[$hash['contextid']])) {
                        $warnings[] = new lang_string('contextnotfound', 'cohort', $hash['context']);
                        $hash['contextid'] = $defaultcontext->id;
                    }
                } else {
                    // Assume it is a category.
                    $hash['category'] = trim($hash['context']);
                }
        }

        if (!empty($hash['category_path'])) {
            // We already have array with available categories, look up the value.
            $contextoptions = $this->get_context_options();
            if (!$hash['contextid'] = array_search($hash['category_path'], $contextoptions)) {
                $warnings[] = new lang_string('categorynotfound', 'cohort', s($hash['category_path']));
                $hash['contextid'] = $defaultcontext->id;
            }
            return $warnings;
        }

        if (!empty($hash['category'])) {
            // Quick search by category path first.
            // Do not issue warnings or return here, further we'll try to search by id or idnumber.
            $contextoptions = $this->get_context_options();
            if ($hash['contextid'] = array_search($hash['category'], $contextoptions)) {
                return $warnings;
            }
        }

        // Now search by category id or category idnumber.
        if (!empty($hash['category_id'])) {
            $field = 'id';
            $value = clean_param($hash['category_id'], PARAM_INT);
        } else if (!empty($hash['category_idnumber'])) {
            $field = 'idnumber';
            $value = $hash['category_idnumber'];
        } else if (!empty($hash['category'])) {
            $field = is_numeric($hash['category']) ? 'id' : 'idnumber';
            $value = $hash['category'];
        } else {
            // No category field was specified, assume default category.
            $hash['contextid'] = $defaultcontext->id;
            return $warnings;
        }

        if (empty($this->categoriescache[$field][$value])) {
            $record = $DB->get_record_sql("SELECT c.id, ctx.id contextid
                FROM {context} ctx JOIN {course_categories} c ON ctx.contextlevel = ? AND ctx.instanceid = c.id
                WHERE c.$field = ?", array(CONTEXT_COURSECAT, $value));
            if ($record && ($contextoptions = $this->get_context_options()) && isset($contextoptions[$record->contextid])) {
                $contextid = $record->contextid;
            } else {
                $warnings[] = new lang_string('categorynotfound', 'cohort', s($value));
                $contextid = $defaultcontext->id;
            }
            // Next time when we can look up and don't search by this value again.
            $this->categoriescache[$field][$value] = $contextid;
        }
        $hash['contextid'] = $this->categoriescache[$field][$value];

        return $warnings;
    }
}

/*04082022
- Ràng buộc Ngày mở - đóng lớp
04082022*/