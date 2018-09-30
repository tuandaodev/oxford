<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="favicon.ico">
    <title>English Tool - Phân tích và so sánh các đoạn văn</title>
    <link rel="stylesheet" href="css/custom.css">
  </head>
  <?php
  
    ini_set('memory_limit', '2048M');
    set_time_limit(1800);
  
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
    
    $file_input = 'input/compare_input4.txt';
    $file_list_input = 'input/compare_list_input4.txt';
    
    $file_exported = 'export/compare_exported4.xlsx';
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
                foreach ($import_data as $key => $temp_data) {
                    $import_data[$key] = str_replace(array("\r", "\n"), '', $temp_data);
                }
                $text_data = array_merge($text_data, $import_data);
            }
            
            file_put_contents($file_input, json_encode($text_data));

            $text_list_data_raw = '';
            if (isset($_POST['list_co_san'])) {
                $text_list_data_raw = $_POST['list_co_san'];
            }
            
            if (isset($_FILES['importfile2']) && ($_FILES['importfile2']['error'] == 0)) {  
                $file_input = $_FILES['importfile2']['tmp_name'];
                
                $list_string = array();
                if (strpos($_FILES['importfile2']['name'], '.csv') !== false) {
                    $file = fopen($file_input,"r");
                    while (!feof($file)) {
                        $temp_data = fgetcsv($file);
                        $list_string[] = $temp_data[1];
                    }
                    fclose($file);
                } else {
                    $list_string = read_excel($file_input);
                }
                
                if (count($list_string) > 0) {
                    $text_list_data_raw .= implode(" ", $list_string);
                }
            }
            
            $text_list_data = array();
            if (!empty($text_list_data_raw)) {
                $text_list_data_raw = strtolower($text_list_data_raw);
                $text_list_data_raw = str_replace("’", "'", $text_list_data_raw);
                // new function
                $text_list_data_raw = utf8_str_word_count($text_list_data_raw, 1);
                $text_list_data = array_unique($text_list_data_raw);
                
                foreach ($text_list_data as $key => $temp_word) {
                    if ($temp_word == "-" || $temp_word == "_") {
                        unset($text_list_data[$key]);
                    }
                }
                
                if (is_array($text_list_data) && !empty($text_list_data)) {
                    file_put_contents($file_list_input, json_encode($text_list_data));
                } else {
                    file_put_contents($file_list_input, '');
                }
            }
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
            $text_list_data = json_decode($text_list_data);
        }
    }
    
    $all_list_words = $text_list_data;
    $text_list_data_view = '';
//    $text_list_data_hightlight = '';
    $count_list_co_san = 0;
    if (!empty($text_list_data)) {
        $count_list_co_san = count($text_list_data);
        
        foreach ($text_list_data as $key => $temp_word) {
            if ($temp_word == '-' || $temp_word == '_') {
                unset($text_list_data[$key]);
            }
        }
        
        if (is_array($text_list_data) && !empty($text_list_data)) {
            $text_list_data_view = implode("\n", $text_list_data);
        }
    }
    
    $final_result = array();
    if ($text_data) {
        foreach ($text_data as $key => $data) {
                $data = strtolower($data);
                $data = str_replace("’", "'", $data);
                $all_words = utf8_str_word_count($data, 1);
//                $all_words = array_count_values($all_words);
                $all_words = array_unique($all_words);
                
                if (count($all_words) < 10) {
                    unset($text_data[$key]);
                    continue;
                }
                
                $count = 0;
                $temp_words = array();
                foreach ($all_words as $word) {
                    if (is_array($all_list_words) && !empty($all_list_words)) {
                        if (count($all_list_words) > 0 && in_array($word, $all_list_words)) {   
                            $count = $count + 1;
                            $temp_words[] = $word;
                        }
                    }
                }
                $temp['text'] = $data;
                $temp['count'] = $count;
                $temp['words'] = $temp_words;
                $temp['percentMatched'] = $count/count($all_words)*100;
                $html_data = $data;
                
                $TEMPall_list_words = $all_list_words;
                
                if (is_array($TEMPall_list_words) && !empty($TEMPall_list_words)) {
                    usort($TEMPall_list_words, function($a, $b) {
                        return strlen($b) - strlen($a);
                    });

                    foreach ($TEMPall_list_words as $word) {
                        $html_data = highlight($html_data, $word);
                    }
                }
                $temp['html'] = $html_data;
                
                $final_result[] = $temp;
            }
        }
    
$count_textarea = count($final_result);
if (!$count_textarea) {
    $count_textarea = 1;
} else {
    array_multisort(array_column($final_result, 'percentMatched'), SORT_DESC, array_column($final_result, 'count'), SORT_DESC, $final_result);
    write_excel($file_exported, $final_result);
}

$hide_textarea = false;
if ($count_textarea > 200) {
    $hide_textarea = true;
}

if (file_exists($file_exported)) {
    $have_exported_file = true;
}

function highlight($text, $word) {
    
    $word = preg_quote($word, "/");
    
    $highlighted = preg_filter("/\b($word)\b(?!')/i", '<zz>$0</zz>', $text);
    if (!empty($highlighted)) {
        $text = $highlighted;
    }
    return $text;
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
            if ($cell->getValue()) {
                $import_data[] = $cell->getValue();
            }
        }
    }
    
    return $import_data;
}

function sortByCount($a, $b) {
    return $b['percentMatched'] - $a['percentMatched'];
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
      <nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href=".">English - Tools</a>
    </div>
    <ul class="nav navbar-nav">
      <li><a href=".">Count Words</a></li>
      <li><a href="compare.php">Compare</a></li>
      <li><a href="compare2.php">Compare 2</a></li>
      <li><a href="compare3.php">Compare 3</a></li>
      <li class="active"><a href="compare4.php">Compare 4</a></li>
    </ul>
  </div>
</nav>
      <div id="page-wrapper">
          <div class="row">
              <div class="col-lg-12" style="margin-top: 20px;">
                  <div class="panel panel-default">
                        <div class="panel-heading">Phân tích và so sánh các đoạn văn</div>
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
                                                <button type="button" onclick="updateText(this)" status="1" class="btn btn-default btn-circle btn-xs"><i class="fa fa-check"></i></button>
                                                <label>Đoạn #<?php echo $html_count ?>: (Số từ matched: <zz><?php echo $text_area['count'] . '</zz>-<i>' . round($text_area['percentMatched'],2) ?>%</i>)</label>
                                                <div class="doan-van"><?php echo $text_area['html'] ?></div>
                                                <div class="script-words"><?php echo json_encode($text_area['words']) ?></div>
                                                <?php if (!$hide_textarea) { ?>
                                                <textarea data-autoresize class="form-control" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_area['text'] ?></textarea>
                                                <?php } else { ?>
                                                <textarea data-autoresize class="form-control" style="display: none" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_area['text'] ?></textarea>
                                                <?php } ?>
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
                                            <input type="file" name="importfile2" id="importfile2" style='margin-top: 10px; margin-bottom: 10px'/>
                                            <label>List có sẵn: </label> <span id="count-list-co-san"><?php echo $count_list_co_san ?></span>
                                            <textarea data-autoresize class="form-control" rows="10" id="list_co_san" name="list_co_san" style="resize:vertical;"><?php echo $text_list_data_view ?></textarea>
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
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <link rel="stylesheet" href="css/jquery.highlighttextarea.min.css">
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="js/jquery.highlighttextarea.min.js"></script>
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
            
            $('#list_co_san').keyup();
        });
        var count_tx = <?php echo $count_textarea ?>;
        function add_textarea() {
            count_tx++;
            var html_code = "<div id='textarea" + count_tx + "' class='multi-textarea'><label>Đoạn #" + count_tx + ":</label><textarea data-autoresize class='form-control' rows='5' name='doan_van[]' style='resize:vertical;'></textarea></div>";
            $('#textarea-container').append(html_code);
            
            do_autoresize();
        }
        function updateText(element) {
            var words_string = $(element).siblings('.script-words').html();
            var words = JSON.parse(words_string);
            console.log(words);
            if ($(element).attr('status') === "1") {
                $(element).html('<i class="fa fa-times"></i>');
                $(element).attr('status', '0');
                var textInList = $('#list_co_san').val();
                console.log(textInList);
                for (i=0; i< words.length; i++) {
                    var regex = new RegExp('\\b(' + words[i] + ')\\b(?!\')',"g");
                    textInList = textInList.replace(regex, "");
                }
                textInList = textInList.replace(/^\s*[\r\n]/gm, '');
                textInList = textInList.trim();
                
                $('textarea[name="list_co_san"]').val(textInList);
                $(element).siblings('textarea').attr('disabled', true);
                
                var countWords = textInList.split("\n").length;
                $("#count-list-co-san").html(countWords);
            } else {
                $(element).html('<i class="fa fa-check"></i>');
                $(element).attr('status', '1');
                var textInList = $('#list_co_san').val();
                console.log(textInList);
                for (i=0; i< words.length; i++) {
                    textInList = textInList + '\n' + words[i];
                    console.log(textInList);
                }
                textInList = textInList.replace(/^\s*[\r\n]/gm, '');
                textInList = textInList.trim();
                
                $('#list_co_san').val(textInList);
                $(element).siblings('textarea').attr('disabled', false);
                
                var countWords = textInList.split("\n").length;
                $("#count-list-co-san").html(countWords);
            }
            
            $('#list_co_san').keyup();
        }
    </script>
  </body>
</html>


