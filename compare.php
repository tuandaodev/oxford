<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<!--    <title>English Tool - Phân tích và so sánh các đoạn văn</title>-->
  </head>
  <?php
  
    require_once('PHPExcel/autoload.php');
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $text_data = array();
    $text_list_data = '';
    
    if (!file_exists('input')) {
        mkdir('input', 0777, true);
    }
    if (!file_exists('export')) {
        mkdir('export', 0777, true);
    }
    
    $file_input = 'input/compare_input.txt';
    $file_list_input = 'input/compare_list_input.txt';
    
    $file_exported = 'export/compare_exported.xlsx';
    $need_export = false;
    $have_exported_file = false;
    
    if (isset($_POST) && !empty($_POST)) {
        
        if (isset($_POST['type_submit']) && $_POST['type_submit'] == 'submit') {
            
            if (isset($_POST['doan_van']) && count($_POST['doan_van']) > 0) {
                foreach ($_POST['doan_van'] as $doan_van) {
                    if ($doan_van) {
                        $text_data[] = $doan_van;
                    }
                }
            }
            
            
            if (isset($_FILES['importfile']) && ($_FILES['importfile']['error'] == 0)) {
                $file_input = $_FILES['importfile']['tmp_name'];
                $import_data = read_excel($file_input);
                $text_data = array_merge($text_data, $import_data);
            }
            
            file_put_contents($file_input, json_encode($text_data));

            if (isset($_POST['list_co_san'])) {
                $text_list_data = $_POST['list_co_san'];
            }
            file_put_contents($file_list_input, json_encode($text_list_data));
            
        }
        
        if (isset($_POST['type_reset']) && $_POST['type_reset'] == 'reset') {
            if (file_exists($file_input)) unlink($file_input);
            if (file_exists($file_list_input)) unlink($file_list_input);
            if (file_exists($file_exported)) unlink($file_exported);
        }
        
        
        
    } else {
        if (file_exists($file_input)) {
            $text_data = file_get_contents($file_input);
            $text_data = json_decode($text_data);
        }
        if (file_exists($file_list_input)) {
            $text_list_data = file_get_contents($file_list_input);
        }
    }
    
    $all_list_words = array();
    if ($text_list_data) {
        $text_list_data_temp = strtolower($text_list_data);
        // new function
        $all_list_words = utf8_str_word_count($text_list_data_temp, 1);
        $all_list_words = array_unique($all_list_words);
    }
    
    $final_result = array();
    if ($text_data) {
        foreach ($text_data as $key => $data) {
            $data = strtolower($data);
            $all_words = utf8_str_word_count($data, 1);
            $all_words = array_count_values($all_words);
            $count = 0;
            foreach ($all_words as $word => $time) {
                if (count($all_list_words) > 0 && in_array($word, $all_list_words)) {
                    $count = $count + $time;
                }
            }
            $temp['text'] = $data;
            $temp['count'] = $count;
            $final_result[] = $temp;
        }
    }
    
$count_textarea = count($final_result);
if (!$count_textarea) {
    $count_textarea = 1;
} else {
    usort($final_result, 'sortByCount');
    write_excel($file_exported, $final_result);
}

if (file_exists($file_exported)) {
    $have_exported_file = true;
}

function write_excel($file_exported, $list_data) {
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    if (count($list_data) == 0) return false;
    $count = 0;
    foreach ($list_data as $data) {
        $count++;
        $sheet->setCellValue('A' . $count, $data['text']);
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($file_exported);
}

function read_excel($file_input) {
    
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($file_input);
    $worksheet = $spreadsheet->getActiveSheet();
    $import_data = array();
    foreach ($worksheet->getRowIterator() AS $rowObj) {
        $cellIterator = $rowObj->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
        $cells = [];
        foreach ($cellIterator as $key => $cell) {
            $import_data[] = $cell->getValue();
        }
    }
    
    return $import_data;
}

function sortByCount($a, $b) {
    return $b['count'] - $a['count'];
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
    
  ?>
  <body>
      <div id="page-wrapper">
          <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Tools</h1>
                </div>
            </div>
          <div class="row">
              <div class="col-lg-12">
                  <div class="panel panel-default">
                        <!--<div class="panel-heading">Phân tích và so sánh các đoạn văn</div>-->
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-lg-7">
                                        <button type="button" class="btn btn-primary" onclick="add_textarea()">Add Paragraph</button>
                                        <button type="submit" class="btn btn-success" name="type_submit" value="submit">Do Analysis</button>
                                        <button type="submit" class="btn btn-default" name="type_reset" value="reset">Reset</button>
                                        <input type="file" name="importfile" id="importfile" style='margin-top: 10px'/>
                                        <div id="textarea-container" class="form-group" style="margin-top: 10px;">
                                        <?php 
                                        
                                        if (count($final_result) > 0) {
                                        $html_count = 0;
                                        foreach ($final_result as $text_area) { 
                                            
                                            $html_count++;
                                            ?>
                                            <div id="textarea<?php echo $html_count ?>" class="multi-textarea">
                                                <label>Đoạn #<?php echo $html_count ?>:</label>
                                                <textarea data-autoresize class="form-control" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_area['text'] ?></textarea>
                                            </div>
                                        <?php } } else { ?> 
                                            <div id="textarea1" class="multi-textarea">
                                                <label>Đoạn #1:</label>
                                                <textarea data-autoresize class="form-control" rows="5" name="doan_van[]" style="resize:vertical;"></textarea>
                                            </div>
                                        <?php } ?>
                                        </div>
                                </div>
                                    <div class="col-md-5">
                                        <div>
                                            <?php if ($have_exported_file) { ?>
                                            <label>Kết quả: </label>
                                            <a href='<?php echo $file_exported ?>' target='_blank'><b>Download</b></a><br/>
                                            <?php } ?>
                                            <label>List có sẵn:</label>
                                            <textarea data-autoresize class="form-control" rows="10" name="list_co_san" style="resize:vertical;"><?php echo $text_list_data ?></textarea>
                                        </div>
                                    </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
          </div>
          
          <?php 
          
//        echo "<pre>";
//        print_r($final_result);
//        echo "</pre>";
          
          ?>
          
          
          
          <footer class="page-footer font-small teal pt-4">
            <div class="footer-copyright py-3" style='text-align: right'>© 2018 Developer by
              <a href='skype:live:tuandao.dev?chat'> Tuan Dao</a>
            </div>
          </footer>
      </div>

    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script>
        function do_autoresize() {
            jQuery.each(jQuery('textarea[data-autoresize]'), function() {
                var offset = this.offsetHeight - this.clientHeight;

                var resizeTextarea = function(el) {
                    jQuery(el).css('height', 'auto').css('height', el.scrollHeight + offset);
                };
                jQuery(this).on('keyup input', function() { resizeTextarea(this); }).removeAttr('data-autoresize');
            });
        }
        $(document).ready(function () {
            do_autoresize();
            $('#doan_van').keyup();
        });
        var count_tx = <?php echo $count_textarea ?>;
        function add_textarea() {
            count_tx++;
            var html_code = "<div id='textarea" + count_tx + "' class='multi-textarea'><label>Đoạn #" + count_tx + ":</label><textarea data-autoresize class='form-control' rows='5' name='doan_van[]' style='resize:vertical;'></textarea></div>";
            $('#textarea-container').append(html_code);
            
            do_autoresize();
        }
    </script>
  </body>
</html>


