<?php


defined('MOODLE_INTERNAL') || die();
function render_user_check_mark($data)
{
    if($data == 1 || $data == 'x' || $data == 'X')
    {
        $result = 'x';
        return $result;
    }
    else{
        return NULL;
    }
}
/**
 * phlstudent core course renderer renderer from the moodle core course renderer
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phl_csv{
    /**
     * @var int import identifier
     */
    private $_iid;

    /**
     * @var string which script imports?
     */
    private $_type;

    /**
     * @var string|null Null if ok, error msg otherwise
     */
    private $_error;

    /**
     * @var array cached columns
     */
    private $_columns;

    /**
     * @var object file handle used during import
     */
    private $_fp;

    /**
     * Contructor
     *
     * @param int $iid import identifier
     * @param string $type which script imports?
     */
    public function __construct($iid, $type) {
        $this->_iid  = $iid;
        $this->_type = $type;
    }

    /**
     * Make sure the file is closed when this object is discarded.
     */
    public function __destruct() {
        $this->close();
    }


    public function phl_load_csv_content($content, $encoding, $delimiter_name, $column_validation=null, $enclosure='"') {
        global $USER, $CFG, $DB;

        $this->close();
        $this->_error = null;

        /*
         $content = core_text::convert($content, $encoding, 'utf-8');
         // remove Unicode BOM from first line
         $content = core_text::trim_utf8_bom($content);
         // Fix mac/dos newlines
         $content = preg_replace('!\r\n?!', "\n", $content);
         // Remove any spaces or new lines at the end of the file.
         if ($delimiter_name == 'tab') {
         // trim() by default removes tabs from the end of content which is undesirable in a tab separated file.
         $content = trim($content, chr(0x20) . chr(0x0A) . chr(0x0D) . chr(0x00) . chr(0x0B));
         } else {
         $content = trim($content);
         }
         */
        $csv_delimiter = csv_import_reader::get_delimiter($delimiter_name);
        $csv_encode    = csv_import_reader::get_encoded_delimiter($delimiter_name);

        // Create a temporary file and store the csv file there,
        // do not try using fgetcsv() because there is nothing
        // to split rows properly - fgetcsv() itself can not do it.
        $tempfile = tempnam(make_temp_directory('/csvimport'), 'tmp');
        if (!$fp = fopen($tempfile, 'w+b')) {
            $this->_error = get_string('cannotsavedata', 'error');
            @unlink($tempfile);
            return false;
        }
        fwrite($fp, $content);
        fseek($fp, 0);
        // Create an array to store the imported data for error checking.
        $columns = array();
        // str_getcsv doesn't iterate through the csv data properly. It has
        // problems with line returns.
        while ($fgetdata = fgetcsv($fp, 0, $csv_delimiter, $enclosure)) {
            // Check to see if we have an empty line.
            if (count($fgetdata) == 1) {
                if ($fgetdata[0] !== null) {
                    // The element has data. Add it to the array.
                    $columns[] = $fgetdata;
                }
            } else {
                $columns[] = $fgetdata;
            }
        }
        $col_count = 0;
        // process header - list of columns
        //var_dump($columns[0]);
        if (!isset($columns[0])) {
            $this->_error = get_string('csvemptyfile', 'error');
            fclose($fp);
            unlink($tempfile);

            return false;
        } else {
            //echo $columns[1][2];
            $idnumber_arrange = $columns[1][2];
            $mycohorts = $DB->get_records_sql('select * from {cohort} where idnumber=?', array('idnumber' =>$idnumber_arrange));
            foreach ($mycohorts as $mycohort) {
                $cohort_id = $mycohort->id;
            }
            $count_members=$DB->count_records('cohort_members', array('cohortid'=>$cohort_id));
            $col_count = count($columns[0]);
            //var_dump($columns);
            $cohortNameCol=0;
            //Custom Code
            // insert or update
            $mycohorts = $DB->get_records_sql('select * from {cohort_members} where cohortid=?', array('cohortid' =>$cohort_id));
            foreach ($mycohorts as $mycohort) {
                $cohort_members_id = $mycohort->id;
            }
            //
            for($i=4;$i<=$count_members+3;$i++){
                if(empty($columns[$i][9])){
                    $columns[$i][9] = '';
                }
                if(empty($columns[$i][10])){
                    $columns[$i][10] = '';
                }
                if(empty($columns[$i][11])){
                    $columns[$i][11] = '';
                }
                if(empty($columns[$i][12])){
                    $columns[$i][12] = '';
                }
                if(empty($columns[$i][13])){
                    $columns[$i][13] = '';
                }
                if(empty($columns[$i][14])){
                    $columns[$i][14] = '';
                }
                if(empty($columns[$i][17])){
                    $columns[$i][17] = '';
                }
                $name = $columns[$i][1];
                $username = $columns[$i][5];
                $array_username_sql = "
        select id from mdl232x0_user where username = N'$username'
";

                $users = $DB->get_records_sql($array_username_sql,array());
                foreach($users as $user){
                    $user_id= $user->id;
                }
                $a1 = render_user_check_mark($columns[$i][9]);
                $a2 = render_user_check_mark($columns[$i][10]);
                $a3 = render_user_check_mark($columns[$i][11]);
                $a4 = render_user_check_mark($columns[$i][12]);
                $a5 = render_user_check_mark($columns[$i][13]);
                $a6 = render_user_check_mark($columns[$i][14]);
                $a7 = render_user_check_mark($columns[$i][17]);
                $a8 = $columns[$i][18];
                $sql = "
            update mdl232x0_cohort_members
            set ";
            if($a1=='')
                {
                    $sql .= " date_1 = NULL";
                }
                else
                {
                    $sql .= " date_1 = N'$a1'";
                }
            if($a2=='')
                {
                    $sql .= ", date_2 = NULL";
                }
                else
                {
                    $sql .= ", date_2 = N'$a2'";
                }
            if($a3=='')
                {
                    $sql .= ", date_3 = NULL";
                }
                else
                {
                    $sql .= ", date_3 = N'$a3'";
                }
            if($a4=='')
                {
                    $sql .= ", date_4 = NULL";
                }
                else
                {
                    $sql .= ", date_4 = N'$a4'";
                }
            if($a5=='')
                {
                    $sql .= ", date_5 = NULL";
                }
                else
                {
                    $sql .= ", date_5 = N'$a5'";
                }
            if($a6=='')
                {
                    $sql .= ", date_6 = NULL";
                }
                else
                {
                    $sql .= ", date_6 = N'$a6'";
                }
                if($a7=='')
                {
                    $sql .= ", participate_condition = NULL";
                }
                else
                {
                    $sql .= ", participate_condition = N'$a7'";
                }
                if($a8=='')
                {
                    $sql .= ", note = NULL";
                }
                else
                {
                    $sql .= ", note = N'$a8'";
                }
            $sql .= " where userid = $user_id and cohortid = $cohort_id";
                $DB->execute($sql,array());
            }
            echo "
<style>
.bubble{
   position:relative;
   padding: 10px;
   background: #FFCB41;
}

.bubble:after{
   position:absolute;
   content: '';
   top:15px;right:-10px;
   border-width: 10px 0 10px 15px;
   border-color: transparent  #FFCB41;
   border-style: solid;
 }​
</style>
<p><span class='bubble'>Cập nhật điểm danh thành công</span>&nbsp;&nbsp;&nbsp;</p>
";
 
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
  background-color: #D9D9D9;
}
th {
  background-color: #EDAC00;
}
</style>
";
            echo "<table>
  <tr>
    <th style='color:white;'>Tên học viên</th>
<th style='color:white;'>Ngày/Tháng/Năm sinh</th>
<th style='color:white;'>CMTND</th>
<th style='color:white;'>Buổi 1</th>
<th style='color:white;'>Buổi 2</th>
<th style='color:white;'>Buổi 3</th>
<th style='color:white;'>Buổi 4</th>
<th style='color:white;'>Buổi 5</th>
<th style='color:white;'>Buổi 6</th>
<th style='color:white;'>Điều kiện tham dự</th>
<th style='color:white;'>Ghi chú</th>
  </tr>
  ";

            for($i=4;$i<=$count_members+3;$i++){
                echo "<tr><td>".$columns[$i][1]."</td>";
                if(empty($columns[$i][2])||empty($columns[$i][3])||empty($columns[$i][4]))
                {
                    echo "<td></td>";
                }
                else{
                    echo "<td>".$columns[$i][2].'/'.$columns[$i][3].'/'.$columns[$i][4]."</td>";
                }
                echo "<td>".$columns[$i][5]."</td>";
                echo "<td>".$columns[$i][9]."</td>";
                echo "<td>".$columns[$i][10]."</td>";
                echo "<td>".$columns[$i][11]."</td>";
                echo "<td>".$columns[$i][12]."</td>";
                echo "<td>".$columns[$i][13]."</td>";
                echo "<td>".$columns[$i][14]."</td>";
                echo "<td>".$columns[$i][17]."</td>";
                echo "<td>".$columns[$i][18]."</td></tr>";
            }

            echo "
</table>
<br>
";
            echo "
<style>
.button {
  background-color: #FFCB41; /* Green */
  border: none;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
  -webkit-transition-duration: 0.4s; /* Safari */
  transition-duration: 0.4s;
}

.button1  {
  box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
}

.button2 {
  box-shadow: 0 12px 16px 0 rgba(0,0,0,0.24),0 17px 50px 0 rgba(0,0,0,0.19);
}
.mform{ display : none;}
</style>";
            echo "<a style='display:inline-block; width:45%;text-align:center;' href='$CFG->wwwroot/phlcohort/training_schedule/index.php?id=$cohort_id'><button class='button button1'>Back</button></a>";
            for($i=1;$i<count($columns);$i++) {


                $columns[$i][23]=$columns[$i][$cohortNameCol];
                /*
                 $columns[$i][$col_count+1]=$lastName;
                 $columns[$i][$col_count+2]='vi';
                 $columns[$i][$col_count+3]='allstudent';
                 */
                //var_dump($columns[$i]);
            }
            $col_count=count($columns[0]);
            //End Custom Code

        }

        // Column validation.
        if ($column_validation) {

            $result = $column_validation($columns[0]);
            if ($result !== true) {

                $this->_error = $result;
                fclose($fp);
                unlink($tempfile);
                return false;
            }
        }
        //var_dump( $columns[0]);
        $this->_columns = $columns[0]; // cached columns
        // check to make sure that the data columns match up with the headers.
        foreach ($columns as $rowdata) {
            //var_dump($rowdata);
            //echo count($rowdata)."XXX".$col_count;
            if (count($rowdata) !== $col_count) {

                $this->_error = get_string('csvweirdcolumns', 'error');
                fclose($fp);
                unlink($tempfile);
                $this->cleanup();
                return false;
            }
            break;
        }

        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        $filepointer = fopen($filename, "w");
        // The information has been stored in csv format, as serialized data has issues
        // with special characters and line returns.
        $storedata = csv_export_writer::print_array($columns, ',', '"', true);

        fwrite($filepointer, $storedata);

        fclose($fp);
        unlink($tempfile);
        fclose($filepointer);

        $datacount = count($columns);

        //var_dump($columns[0]);
        return $datacount;
    }
    /**
     * Returns list of columns
     *
     * @return array
     */
    public function get_columns() {
        if (isset($this->_columns)) {
            return $this->_columns;
        }

        global $USER, $CFG;

        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        echo "filename = ".$filename."<br>";
        if (!file_exists($filename)) {
            return false;
        }
        $fp = fopen($filename, "r");
        $line = fgetcsv($fp);
        fclose($fp);
        if ($line === false) {
            return false;
        }
        $this->_columns = $line;
        return $this->_columns;
    }

    /**
     * Init iterator.
     *
     * @global object
     * @global object
     * @return bool Success
     */
    public function init() {
        global $CFG, $USER;

        if (!empty($this->_fp)) {
            $this->close();
        }
        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return false;
        }
        if (!$this->_fp = fopen($filename, "r")) {
            return false;
        }
        //skip header
        return (fgetcsv($this->_fp) !== false);
    }

    /**
     * Get next line
     *
     * @return mixed false, or an array of values
     */
    public function next() {
        if (empty($this->_fp) or feof($this->_fp)) {
            return false;
        }
        if ($ser = fgetcsv($this->_fp)) {
            return $ser;
        } else {
            return false;
        }
    }

    /**
     * Release iteration related resources
     *
     * @return void
     */
    public function close() {
        if (!empty($this->_fp)) {
            fclose($this->_fp);
            $this->_fp = null;
        }
    }

    /**
     * Get last error
     *
     * @return string error text of null if none
     */
    public function get_error() {
        return $this->_error;
    }

    /**
     * Cleanup temporary data
     *
     * @global object
     * @global object
     * @param boolean $full true means do a full cleanup - all sessions for current user, false only the active iid
     */
    public function cleanup($full=false) {
        global $USER, $CFG;

        if ($full) {
            @remove_dir($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id);
        } else {
            @unlink($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid);
        }
    }

    /**
     * Get list of cvs delimiters
     *
     * @return array suitable for selection box
     */
    public static function get_delimiter_list() {
        global $CFG;
        $delimiters = array('comma'=>',', 'semicolon'=>';', 'colon'=>':', 'tab'=>'\\t');
        if (isset($CFG->CSV_DELIMITER) and strlen($CFG->CSV_DELIMITER) === 1 and !in_array($CFG->CSV_DELIMITER, $delimiters)) {
            $delimiters['cfg'] = $CFG->CSV_DELIMITER;
        }
        return $delimiters;
    }

    /**
     * Get delimiter character
     *
     * @param string separator name
     * @return string delimiter char
     */
    public static function get_delimiter($delimiter_name) {
        global $CFG;
        switch ($delimiter_name) {
            case 'colon':     return ':';
            case 'semicolon': return ';';
            case 'tab':       return "\t";
            case 'cfg':       if (isset($CFG->CSV_DELIMITER)) { return $CFG->CSV_DELIMITER; } // no break; fall back to comma
            case 'comma':     return ',';
            default :         return ',';  // If anything else comes in, default to comma.
        }
    }

    /**
     * Get encoded delimiter character
     *
     * @global object
     * @param string separator name
     * @return string encoded delimiter char
     */
    public static function get_encoded_delimiter($delimiter_name) {
        global $CFG;
        if ($delimiter_name == 'cfg' and isset($CFG->CSV_ENCODE)) {
            return $CFG->CSV_ENCODE;
        }
        $delimiter = csv_import_reader::get_delimiter($delimiter_name);
        return '&#'.ord($delimiter);
    }

    /**
     * Create new import id
     *
     * @global object
     * @param string who imports?
     * @return int iid
     */
    public static function get_new_iid($type) {
        global $USER;

        $filename = make_temp_directory('csvimport/'.$type.'/'.$USER->id);

        // use current (non-conflicting) time stamp
        $iiid = time();
        while (file_exists($filename.'/'.$iiid)) {
            $iiid--;
        }

        return $iiid;
    }

}
