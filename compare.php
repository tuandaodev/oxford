<?php
if(!isset($_COOKIE['row']))
    $rowcount=500;
else
$rowcount=intval($_COOKIE['row']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="favicon.ico">
    <title>English Tool - Phân tích và so sánh các đoạn văn</title>
    <link rel="stylesheet" href="css/custom.css">
    <style>
    @import url('https://fonts.googleapis.com/css?family=Song+Myung:400');
    *{
        font-family: "Song Myung",Arial;
    }
    .notifi{
        position: fixed;;
        bottom: 0px;
        right: 0px;
    }
    .notifi_i{
        width: 300px;
        padding: 10px;
        border-radius: 4px;
        background: rgba(1,1,1,0.5);
        color: white;
        margin: 5px;
    }
    .doan-van b{
        color: blue;
    }
    .script-words2{
display:   none;
    }
    #textarea-container textarea.form-control{
        display: none;
    }
    </style>
  </head>
  <?php
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
function utf8_str_replace($fin='',$char="",$string){
    $arr=mb_split($fin,$string);
    return join($char,$arr);
}
//die(utf8_str_replace("사","<b>사</b>","사람은동생이에요사람은동생이에요사람은동생이에요"));
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
    
    $file_input = 'input/compare_input.txt';
    $file_input_500 = 'input/compare_input_500.txt';
    $file_list_input = 'input/compare_list_input.txt';
    
    $file_exported = 'export/compare_exported.xlsx';
    $file_er = 'export/compare_r_exported.xlsx';
    $need_export = false;
    $have_exported_file = false;
    $rawdata=array();

    if (isset($_POST) && !empty($_POST)) {
        
        if (isset($_POST['type_submit']) && $_POST['type_submit'] == 'submit') {
            
            if (isset($_POST['doan_van']) && count($_POST['doan_van']) > 0) {
                foreach ($_POST['doan_van'] as $doan_van) {
                    if ($doan_van) {
                        $text_data[] = $doan_van;
                    }
                }
                foreach ($text_data as $key => $value) {
                   $rawdata[]=(string)$value;
                }
            }
            
            
            if (isset($_FILES['importfile']) && ($_FILES['importfile']['error'] == 0)) {
                $file_input2 = $_FILES['importfile']['tmp_name'];
                $import_data = read_excel($file_input2);
                foreach ($import_data as $key => $temp_data) {
                    $import_data[$key] = str_replace(array("\r", "\n"), '', (string)$temp_data);
                    $rawdata[]=(string)$temp_data;
                }
                
                $text_data = array_merge( $import_data,$text_data);
               
                //print_r($text_data);die();
            }
            
            if (file_exists($file_input_500)) {
                $text_data_500 = file_get_contents($file_input_500);
                $text_data_500 = json_decode($text_data_500,true);
                
                if (is_array($text_data_500) && !empty($text_data_500)) {
                    $text_data = array_merge($text_data, $text_data_500);

                }
            }
            
            //file_put_contents($file_input, json_encode($text_data));
//print_r($text_data);die();
            $text_list_data_raw = '';
            if (isset($_POST['list_co_san'])) {
                $text_list_data_raw = $_POST['list_co_san'];
            }
            
            if (isset($_FILES['importfile2']) && ($_FILES['importfile2']['error'] == 0)) {  
                $file_input_excel = $_FILES['importfile2']['tmp_name'];
                
                $list_string = array();
                if (strpos($_FILES['importfile2']['name'], '.csv') !== false) {
                    $file = fopen($file_input_excel,"r");
                    while (!feof($file)) {
                        $temp_data = fgetcsv($file);
                        $list_string[] = $temp_data[1];
                    }
                    fclose($file);
                } else {
                    $list_string = read_excel($file_input_excel);
                }
                
                if (count($list_string) > 0) {
                    $text_list_data_raw .= implode(" ", $list_string);
                }
            }
            //print_r($text_list_data_raw);die();
            $text_list_data = array();
            if (!empty($text_list_data_raw)) {
                $text_list_data_raw = str_replace(array("\r", "\n"), '', $text_list_data_raw);
                
                $text_list_data_raw = strtolower($text_list_data_raw);
                $text_list_data_raw = str_replace("’", "'", $text_list_data_raw);
                if(isCjk($text_list_data_raw))
            {
                $text_list_data_raw = str_replace(" ", "", $text_list_data_raw);
                //$array_strings = explode(" ", $text_list_data_raw);
                $array_strings = utf8_str_split($text_list_data_raw,1);
                
            }
            else
            $array_strings = str_split($text_list_data_raw, 5000);


                $all_words = array();

                $count_test = 0;
                foreach ($array_strings as $temp_string) {
                    $all_words_temp = utf8_str_word_count($temp_string, 1);
                    $all_words = array_merge($all_words, $all_words_temp);
                }

                $all_words = array_replace($all_words,array_fill_keys(array_keys($all_words, null),''));
                $text_list_data = array_unique($all_words);
                
                foreach ($text_list_data as $key => $temp_word) {
                    if ($temp_word == "-" || $temp_word == "_" || $temp_word == "") {
                        unset($text_list_data[$key]);
                    }
                }
                
                if (is_array($text_list_data) && !empty($text_list_data)) {
                    file_put_contents($file_list_input, json_encode($text_list_data));
                } else {
                    if (file_exists($file_list_input)) unlink($file_list_input);
                }
            }
        }
        
        if (isset($_POST['type_reset']) && $_POST['type_reset'] == 'reset') {
            if (file_exists($file_input)) unlink($file_input);
            if (file_exists($file_input_500)) unlink($file_input_500);
            if (file_exists($file_list_input)) unlink($file_list_input);
            if (file_exists($file_exported)) unlink($file_exported);
            if (file_exists($file_er)) unlink($file_er);
        }
        
    } else {
        if (file_exists($file_input)) {
            $text_data = file_get_contents($file_input);
            $text_data = json_decode($text_data,true);
            foreach ($text_data as $key => $value) {
                   $rawdata[]=$value;
                }
        }
        if (file_exists($file_list_input)) {
            $text_list_data = file_get_contents($file_list_input);
            $text_list_data = json_decode($text_list_data, true);
        }
    }

  file_put_contents($file_input, json_encode($rawdata,true));  
  //print_r($rawdata);die();
    //
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
            else
            $text_list_data_view.=@$temp_word."\n";
        }
        $text_list_data_view=trim($text_list_data_view);
       
    }
    
    $final_result = array();
    //print_r($text_data);die();
    if ($text_data) {
        foreach ($text_data as $key => $data) {
                $data = trim(strtolower($data));
                $data = str_replace(array("\r", "\n",",",".","?"), '', $data);
                if(isCjk($data))                $data = preg_replace('/[^\x{3130}-\x{318F}\x{AC00}-\x{D7AF}\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\p{Han}+]/u', '', $data);
                //die($data);               
                $data = str_replace("’", "'", $data);
                if(isCjk($data) ){
                $data = str_replace(" ", "", $data);
                $all_words = utf8_str_split($data, 1);
                }
                else
                {
                    $data = str_replace(" ", "", $data);
                $all_words = utf8_str_split($data, 1);
                    //$all_words = utf8_str_word_count($data, 1);
                //$all_words = array_count_values($all_words);
              //$all_words = array_count_values($all_words);
              // $all_words = array_unique($all_words);
                
                //if (count($all_words) < 10) {
                  //  unset($text_data[$key]);
                    //continue;
                //}
            }
                //print_r($data."~~"."~~".count($all_words)."<br><br><br>");
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

$count = 0;
//print_r($rawdata);die();
    foreach ($final_result as $key => $result) {        
        $final_result[$key]["raw"]=$rawdata[$count];
        $count++;
    }

if (!$count_textarea) {
    $count_textarea = 1;
} else {
    array_multisort(array_column($final_result, 'percentMatched'), SORT_DESC, array_column($final_result, 'count'), SORT_DESC, $final_result);
    //array_multisort(array_column($rawdata, 'percentMatched'), SORT_DESC, array_column($rawdata, 'count'), SORT_DESC, $rawdata);
    
    write_excel($file_exported, $final_result);
}

if (count($final_result) > 500) {
    $save_more_500 = array();
    $count = 0;
    foreach ($final_result as $key => $result) {
        $count++;
        if ($count < 500) continue;
        $save_more_500[] = $result['text'];
    }
    
    if (is_array($save_more_500) && !empty($save_more_500)) {
        file_put_contents($file_input_500, json_encode($save_more_500));
    } else {
        if (file_exists($file_input_500)) unlink($file_input_500);
    }
    
    $final_result = array_slice($final_result, 0, 500);
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
        $sheet->setCellValue('A' . $count, $data['raw']);
        $sheet->getStyle('A' . $count)->getAlignment()->setWrapText(true);
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
      <li class="active"><a href="compare.php">Compare</a></li>
      <li><a href="compare2.php">Compare 2</a></li>
      <li><a href="compare3.php">Compare 3</a></li>
      <li><a href="compare4.php">Compare 4</a></li>
    </ul>
  </div>
</nav>
      <div id="page-wrapper">
          <div class="row">
              <div class="col-lg-12" style="margin-top: 20px;">
                  <div class="panel panel-default">
                        <div class="panel-heading">Phân tích và so sánh các đoạn văn <select style="float:right" id="select_crow"><option value=100 <?php if($rowcount==100)echo "selected";?>>100</option>
                                                                                                <option value=200 <?php if($rowcount==200)echo "selected";?>>200</option>
                                                                                                <option value=300 <?php if($rowcount==300)echo "selected";?>>300</option>
                                                                                                <option value=400 <?php if($rowcount==400)echo "selected";?>>400</option>
                                                                                                <option value=500 <?php if($rowcount==500)echo "selected";?>>500</option>
                                                                                                <option value=1000 <?php if($rowcount==1000)echo "selected";?>>1000</option>
                                                                                                <option value=2000 <?php if($rowcount==2000)echo "selected";?>>2000</option>
                                                                                                <option value=3000 <?php if($rowcount==3000)echo "selected";?>>3000</option>
                                                                                                <option value=4000 <?php if($rowcount==4000)echo "selected";?>>4000</option>
                                                                                                <option value=5000 <?php if($rowcount==5000)echo "selected";?>>5000</option>
                        </select></div>
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
                                            if($html_count>$rowcount)break;
                                            $text_area['raw2']=$text_area['raw'];
                                            foreach ($text_area['words'] as $key => $value) {
                                                //die($value);
                                                $text_area['raw2']=utf8_str_replace($value, "<b>".$value."</b>", $text_area['raw2']);
                                            }
                                            ?>
                                            <div id="textarea<?php echo $html_count ?>" class="multi-textarea" idx=<?php echo $html_count ?>>
                                                <button type="button" onclick="updateText(this)" status="1" class="btn btn-default btn-circle btn-xs"><i class="fa fa-check"></i></button>
                                                <label>Đoạn #<?php echo $html_count ?>: (Số từ matched: <zz><?php echo $text_area['count'] . '/'.mb_strlen($text_area['text']).'</zz>-<i class=per>' . round($text_area['percentMatched'],2) ?>%</i>)</label>
                                                <div class="doan-van"><?php echo $text_area['raw2'] ?></div>
                                                <div class="script-words"><?php echo json_encode($text_area['words']) ?></div>
                                                <div class="script-words2"><?php echo ($text_area['text']) ?></div>
                                                <?php if (!$hide_textarea) { ?>
                                                <textarea data-autoresize class="form-control" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_area['raw'] ?></textarea>
                                                <?php } else { ?>
                                                <textarea data-autoresize class="form-control" style="display: none" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_area['raw'] ?></textarea>
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
                                            <label>File loại bỏ: </label>
                                            <a href='<?php echo $file_er ?>' target='_blank'><b>Download</b></a><br/>
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

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/jquery-ui.min.css">
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

function makenotifi(text){
    $(".notifi").append("<div class=notifi_i>"+text+"</div>");
    var nti=$(".notifi_i").last();
    nti.animate({opacity:0},3000,function(){$(this).remove();});

}




        function updateText(element) {
            var words_string = $(element).siblings('.script-words').html();
            var words = JSON.parse(words_string);
            
            if ($(element).attr('status') === "1") {
                $(element).html('<i class="fa fa-times"></i>');
                $(element).attr('status', '0');
                var textInList = $('#list_co_san').val();
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
$(element).parent().hide();
                //doan ajax xoa
                var rmvTxt=$(element).parent().find("textarea").val();
                
                makenotifi("Đang xóa");
                
                $.post( "./ajax/check.php",{remove:rmvTxt})
                  .done(function(data) {
                    if(data.trim()=="ok")
                        $(element).parent().animate({opacity:0.6},1000,function(){
                           $(element).parent().hide();
                        });


                    makenotifi("Xóa xong");
                  });

addwork($(element).parent().find(".script-words2").html());















            } else {
                $(element).html('<i class="fa fa-check"></i>');
                $(element).attr('status', '1');
                var textInList = $('#list_co_san').val();
                
                for (i=0; i< words.length; i++) {
                    textInList = textInList + '\n' + words[i];
                    
                }
                textInList = textInList.replace(/^\s*[\r\n]/gm, '');
                textInList = textInList.trim();
                
                //$('#list_co_san').val(textInList);
                $(element).siblings('textarea').attr('disabled', false);
                
                var countWords = textInList.split("\n").length;
                $("#count-list-co-san").html(countWords);


//doan ajax them
                var rmvTxt=$(element).parent().find("textarea").val();
                
                makenotifi("Đang thêm lại");
                $.post( "./ajax/check.php",{add:rmvTxt})
                  .done(function(data) {
                    if(data.trim()=="ok")
                        $(element).parent().animate({opacity:1},1000,function(){
                            $(element).parent().hide();
                        });
                    makenotifi("Đã thêm xong");
                  });



            }
            
            $('#list_co_san').keyup();
        }
function setcookie(name, value, days)
{
  var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    }
    else {
        expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}
function getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
function deleteAllCookies() {
    var cookies = document.cookie.split(";");

    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
}
//deleteAllCookies();

function arrayUnique(array) {
    var a = array.concat();
    for(var i=0; i<a.length; ++i) {
        for(var j=i+1; j<a.length; ++j) {
            if(a[i] === a[j])
                a.splice(j--, 1);
        }
    }

    return a;
}
var oldarr;
const re = /[^\u3130-\u318F\uAC00-\uD7AF\u4E00-\u9FBF\u3040-\u309F\u30A0-\u30FF\p{Script=Hani}+]/;
function addwork(clist){
    
oldarr=[...$("#list_co_san").val().replace(re, '').replace(/(\r\n|\n|\r|\-)/gm, '')];
let splitString = [...clist.replace(re, '').replace(/(\r\n|\n|\r|\-)/gm, '')];
var array3 = [...oldarr, ...splitString];
array3=arrayUnique(array3);
var newclist=array3.join("\r\n");
$("#list_co_san").val(newclist);
$("#count-list-co-san").html(array3.length);
reloadj();
}
String.prototype.replaceAll = function(search, replacement) {
    var target = this;
    return target.split(search).join(replacement);
};
function matchper(clist,dom){
let splitString = [...clist.replace(re, '').replace(/(\r\n|\n|\r|\-)/gm, '')];
var nc=0;
var newj=dom.html().replaceAll("<b>","").replaceAll("</b>","");
splitString.forEach(function(it1) {
    if($.inArray(it1, oldarr) != -1)
    {
        
        newj=newj.replaceAll(it1,"<b>"+it1+"</b>");
        nc++;
    }
});
dom.html(newj);
var per=splitString.length>0?nc/splitString.length*100:0;
var paj=dom.parent();
$("#textarea-container").prepend(paj);
$(".multi-textarea").each(function(){
    var idx=parseInt($(this).attr("idx"));
    var idi=parseInt(paj.attr("idx"));
    var oper=parseFloat($(this).find(".per").html())||0;
    
    if(oper>per && idi>idx)
    {
console.log(oper,">",per,idi,idx);
        $(this).insertBefore(paj);
    }
});
        
    
return {"count":nc,"max":splitString.length,"per":(splitString.length>0?(nc/splitString.length*100).toFixed(2):0)}
}

var rlinv;
var maxper=0;
function itemreload(ii){
var that=".multi-textarea[idx='"+ii+"']";
if($(that).length>0){
var mak=matchper($(that).find(".script-words2").html(),$(that).find(".doan-van"));

$(that).find("zz").html(mak.count+"/"+mak.max);
$(that).find("i.per").html(mak.per+"%");
var jj=ii+1;
rlinv=setTimeout(function(){itemreload(jj); });
}
}

function reloadj(){
    oldarr=[...$("#list_co_san").val().replace(re, '').replace(/(\r\n|\n|\r|\-|[&\/\\#,+()$~%.'":*?<>{}])/gm, '')];
    $("#count-list-co-san").html(oldarr.length);
    clearTimeout(rlinv);
    maxper=0;
    itemreload(1);
}




var inpd;

$(document).ready(function(){
    
    $("#list_co_san").on("input",function(){        
        reloadj();

clearTimeout(inpd);
inpd=setTimeout(function(){
    $.post( "./ajax/saveright.php",{update:JSON.stringify(oldarr)})
                  .done(function(data) {
                    makenotifi("Lưu lại danh sách");
                  });
},1000);
        
    });
    $("#select_crow").on("change",function(){        
        setcookie("row",parseInt($("#select_crow").val()),365);
    });
    
});


    </script>
    <div class=notifi>
    </div>
  </body>
</html>


