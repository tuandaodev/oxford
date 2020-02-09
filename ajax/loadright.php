<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 function isCjk($string) {
    return isChinese($string) || isJapanese($string) || isKorean($string);
}

function isChinese($string) {
    return preg_match("/\p{Han}+/u", $string);
}

function isJapanese($string) {
    return preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $string);
}

function isKorean($string) {
    return preg_match('/[\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $string);
}
function utf8_str_split($str='',$len=1){
    preg_match_all("/./u", $str, $arr);
    $arr = array_chunk($arr[0], $len);
    $arr = array_map('implode', $arr);
    $arr = array_diff( $arr,['']);
    
    return $arr;
}
    require_once('PHPExcel/autoload.php');
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
  
    $text_data = '';
    $file_saved = '../input/saved_input.txt';
    $file_result = '../input/saved_result.txt';
    $file_exported = '../export/exported.xlsx';
    $need_export = false;
    $have_exported_file = false;

    if (isset($_POST) && !empty($_POST)) {
        
        if (isset($_POST['doan_van']) && $_POST['doan_van'] == 'submit') {
            $need_export = true;
            if ($_POST['doan_van']) {
                $string = $_POST['doan_van'];
                file_put_contents($file_saved, $string);
                $text_data = $string;
            }
            
            $text_data = str_replace("’", "", $text_data);
            $temp_data = strtolower($text_data);
            
            if(isCjk($temp_data))
            {
                $temp_data = str_replace(" ", "", $temp_data);
                $array_strings = explode(" ", $temp_data);
                $array_strings = utf8_str_split($temp_data,1);
                //print_r($array_strings);die();
            }
            else
            $array_strings = str_split($temp_data, 5000);
            $all_words = array();
            
            $count_test = 0;
            foreach ($array_strings as $temp_string) {
                $all_words_temp = utf8_str_word_count($temp_string, 1);
                $all_words = array_merge($all_words, $all_words_temp);
            }
            
            $all_words = array_replace($all_words,array_fill_keys(array_keys($all_words, null),''));
            $all_words = array_diff( $all_words,['']);
            $result = array_count_values($all_words);
            
            foreach ($result as $key => $temp_word) {
                if ($key == '-' || $key == '_') {
                    unset($result[$key]);
                }
            }
            
            $final_result = array();
            if (file_exists($file_result)) {
                $old_result = file_get_contents($file_result);
                $old_result = json_decode($old_result, true);
                
                foreach (array_keys($old_result + $result) as $key) {
                    $final_result[$key] = (isset($old_result[$key]) ? $old_result[$key] : 0) + (isset($result[$key]) ? $result[$key] : 0);
                }
            } else {
                $final_result = $result;
            }
            
            arsort($final_result);
            file_put_contents($file_result, json_encode($final_result));
            
            write_excel($file_exported, $final_result);
            
//            $fp = fopen($file_exported, 'w');
//            $count = 0;
//            foreach ($final_result as $word => $time) {
//                $count++;
//                $temp = array($count,$word,$time);
//                fputcsv($fp, $temp);
//            }
//            fclose($fp);
        }
        
        
        
    } else {
        if (file_exists($file_saved)) {
            $text_data = file_get_contents($file_saved);
        }
        if (file_exists($file_result)) {
            $final_result = file_get_contents($file_result);
            $final_result = json_decode($final_result, true);
        }
    }
    
    if (file_exists($file_exported)) {
        $have_exported_file = true;
    }
    
function utf8_str_word_count($string, $format = 0, $charlist = null)
{
    $result = array();
    if (preg_match_all('~[\p{L}\p{Mn}\p{Pd}\'\x{2019}' . preg_quote($charlist, '~') . ']+~u', $string, $result) > 0)
    {
        if (array_key_exists(0, $result) === true)
        {
            $result = $result[0];
        }
    }
    if ($format == 0)
    {
        $result = count($result);
    }
    return $result;
}

function write_excel($file_exported, $list_data) {
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    if (count($list_data) == 0) return false;
    $count = 0;
    
    foreach ($list_data as $word => $time) {
        $count++;
        $sheet->setCellValue('A' . $count, $count);
        $sheet->setCellValue('B' . $count, $word);
        $sheet->setCellValue('C' . $count, $time);
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($file_exported);
}
?>