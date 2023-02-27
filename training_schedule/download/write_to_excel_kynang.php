<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2015 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    ##VERSION##, ##DATE##
 */

/** Error reporting */
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
//date_default_timezone_set('Europe/London');
require('../../../config.php');
require_once ($CFG->dirroot.'/phlcohort/excel_reader/PHPExcel/Classes/PHPExcel.php');
require_once($CFG->dirroot.'/phlcohort/training_schedule/lib.php');
$id=optional_param('id',0, PARAM_INT);
$cell=10; $columnA = 1; // starting position in excel
// Get cohort info to download
$objPHPExcel = PHPExcel_IOFactory::load($CFG->dirroot.'/phlcohort/csv/lopkynang.xlsx');
if(!$objPHPExcel){
    throw new coding_exception('folder_csv_cdp_null');
}
$master_cell_array = get_master_cell_cdp(); $loop_cell_users = get_loop_cell_cdp();
$master_cell_index = 0;
$template_cohort = get_cohort_cdp_template($id);
$master_cell_filled = array($template_cohort->tenchuongtrinh,$template_cohort->malop,
        date("d/m/Y",$template_cohort->thoigianhoc),$template_cohort->diadiemhuanluyen,
        $template_cohort->cvhl);
if(!$template_cohort||empty($template_cohort)){
    throw new coding_exception('cohort_null');
}   else    {
    $user_info = get_cohort_cdp_members_to_download($template_cohort->cid);
}
if(!$user_info||empty($user_info)){
    throw new coding_exception('user_info_null');
}
// EXCEL COHORT
for($i=0;$i<5;$i++){
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($master_cell_array[$master_cell_index],$master_cell_filled[$master_cell_index]);
    $master_cell_index ++;
}
// EXCEL USERS
foreach($user_info as $u){
    $loop_cell = 0; $theLastOfUs = $cell;
    if(!$u->cmnd||empty($u->cmnd)){
        require_once($CFG->dirroot.'/user/profile/lib.php');
        //require($CFG->dirroot.'/user/profile/lib.php');
        $loadUser = use_moodle_user_info_data_instead($u->id);
        profile_load_custom_fields($loadUser);
        $members_array = array($columnA,$u->firstname.' '.$u->lastname,
        $u->username,$u->dienthoai,$loadUser->profile['vanphong'],$loadUser->profile['AD'],
        $u->date_1,$u->date_2,$u->date_3,$u->date_4,
        $u->participate_condition,$u->note);
        for($i=0;$i<12;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($loop_cell_users[$loop_cell].''.$cell,''.$members_array[$loop_cell]);
            $loop_cell ++;
        }
    } else {
        $members_array = array($columnA,$u->fullname,
        $u->cmnd,$u->dienthoai,$u->vp,$u->ad_info,
        $u->date_1,$u->date_2,$u->date_3,$u->date_4,
        $u->participate_condition,$u->note);
        for($i=0;$i<12;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($loop_cell_users[$loop_cell].''.$cell,''.$members_array[$loop_cell]);
            $loop_cell ++;
        }
    }
    $columnA++; $cell++;
}
$objPHPExcel->getActiveSheet()->duplicateStyle($objPHPExcel->getActiveSheet()->getStyle('B10'),'A10:U'.$cell);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="CDP_'.$template_cohort->malop.'.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;