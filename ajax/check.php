<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(1800);
  
    require_once('../PHPExcel/autoload.php');
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    $file_exported = '../export/compare_exported.xlsx';
    $file_input = '../input/compare_input.txt';
    $file_er = '../export/compare_r_exported.xlsx';
    function rmvstr($str){
    	$str2="";
    	$str2 = str_replace(array("\r", "\n"," "), '', (string)$str);
        return $str2;  

    }
    if (isset($_REQUEST) && !empty($_REQUEST)) {
        
        if (isset($_REQUEST['remove']) && @($_REQUEST['remove']) != '') {

        	if (file_exists($file_input)) {
        		$text_data = file_get_contents($file_input);
            	$import_data = (json_decode($text_data,true));
            	$addt=rmvstr(urldecode($_REQUEST['remove']));
                foreach ($import_data as $key => $temp_data) {
                	if(rmvstr(urldecode($temp_data))==$addt)
                	{
						//xlsx remove
						$objPHPExcel = IOFactory::load($file_exported);
$objWorksheet = $objPHPExcel->getActiveSheet();
$highestRow = $objWorksheet->getHighestRow();
$highestColumn = $objWorksheet->getHighestColumn();

for($row=1; $row <= $highestRow; ++$row){
   $value = $objPHPExcel->getActiveSheet()->getCell('A'.$row)->getValue();
//echo rmvstr(@$value) ."==". $addt."<br><br><br><br><br>";
   if (rmvstr(@$value) == $addt) {
      $objPHPExcel->getActiveSheet()->removeRow($row,1);

$objWriter = new Xlsx($objPHPExcel);
$objWriter->save($file_exported);

                		unset($import_data[$key]);
						file_put_contents($file_input, json_encode($import_data));

if (!file_exists($file_er)) {
write_excel($file_er,urldecode($_REQUEST['remove']));
}
else
{
add_row_excel($file_er,urldecode($_REQUEST['remove']));
}
die("ok");
      }
}


                	}
                }
            }
        }

        if (isset($_REQUEST['add']) && @($_REQUEST['add']) != '') {
        		if (file_exists($file_input)) {
        		$text_data = file_get_contents($file_input);
            	$import_data = (json_decode($text_data,true));
            	$addt=urldecode($_REQUEST['add']);
            	$checkhas=false;
                foreach ($import_data as $key => $temp_data) {
                	//echo rmvstr(urldecode($temp_data)) ."==". rmvstr($addt)."<br><br><br><br><br>";
                	if(rmvstr(urldecode($temp_data))==rmvstr($addt))
                		{$checkhas=true;}
                }
                if(!$checkhas){
                	
                		
						//xlsx remove
						$objPHPExcel = IOFactory::load($file_exported);
$objWorksheet = $objPHPExcel->getActiveSheet();
$highestRow = $objWorksheet->getHighestRow();
$highestColumn = $objWorksheet->getHighestColumn();
$row=$highestRow+1;
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $addt);
$import_data[count($import_data)]=$addt;
echo "ok";
file_put_contents($file_input, json_encode($import_data));
$objWriter = new Xlsx($objPHPExcel);
$objWriter->save($file_exported);
                	
                }
            }
        }
    }
function write_excel($file_exported, $list_data) {
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    if (count($list_data) == 0) return false;
    $count = 0;
    
        $count++;
        $sheet->setCellValue('A' . $count, $list_data);
        $sheet->getStyle('A' . $count)->getAlignment()->setWrapText(true);
    
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($file_exported);
}
function add_row_excel($file_exported, $data) {
    $objPHPExcel = IOFactory::load($file_exported);
	$objWorksheet = $objPHPExcel->getActiveSheet();
	$highestRow = $objWorksheet->getHighestRow();
	$highestColumn = $objWorksheet->getHighestColumn();
	$row=$highestRow+1;
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data);
	$objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getAlignment()->setWrapText(true);
	$objWriter = new Xlsx($objPHPExcel);
	$objWriter->save($file_exported);
}

?>