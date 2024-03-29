<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="favicon.ico">
    <title>English - Tools</title>
  </head>
  <?php
  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
  
    require_once('PHPExcel/autoload.php');
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
  
    $text_data = '';
    $file_saved = 'input/saved_input.txt';
    $file_result = 'input/saved_result.txt';
    $file_exported = 'export/exported.xlsx';
    $need_export = false;
    $have_exported_file = false;
    
    if (isset($_POST) && !empty($_POST)) {
        
        if (isset($_POST['type_submit']) && $_POST['type_submit'] == 'submit') {
            $need_export = true;
            if ($_POST['doan_van']) {
                $string = $_POST['doan_van'];
                file_put_contents($file_saved, $string);
                $text_data = $string;
            }
            
            $text_data = str_replace("’", "", $text_data);
            $temp_data = strtolower($text_data);
            
            $array_strings = str_split($temp_data, 5000);
            $all_words = array();
            
            $count_test = 0;
            foreach ($array_strings as $temp_string) {
                $all_words_temp = utf8_str_word_count($temp_string, 1);
                $all_words = array_merge($all_words, $all_words_temp);
            }
            
            $all_words = array_replace($all_words,array_fill_keys(array_keys($all_words, null),''));
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
        
        if (isset($_POST['type_reset']) && $_POST['type_reset'] == 'reset') {
            if (file_exists($file_saved)) unlink($file_saved);
            if (file_exists($file_exported)) unlink($file_exported);
            if (file_exists($file_result)) unlink($file_result);
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
  <body>
      <nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href=".">English - Tools</a>
    </div>
    <ul class="nav navbar-nav">
      <li class="active"><a href=".">Count Words</a></li>
      <li><a href="compare.php">Compare</a></li>
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
                        <div class="panel-heading">Tools tách từ và đếm số lần lặp lại
                          </div>
                        <div class="panel-body">
                            <div class="row show-grid">
                                <form method="POST">
                                    <div class="col-md-7" style="margin-bottom: 20px">
                                        <button type="submit" class="btn btn-success" name="type_submit" value="submit">Submit Button</button>
                                        <button type="submit" class="btn btn-default" name="type_reset" value="reset">Reset Button</button>
                                    </div>
                                    <div class="col-md-5">
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label>Nhập đoạn văn ở đây:</label>
                                            <textarea data-autoresize class="form-control" rows="10" id="doan_van" name="doan_van" style="resize:vertical;"><?php echo $text_data ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Kết quả:</label>
                                            
                                            <?php if ($have_exported_file) { ?>
                                            <a href='<?php echo $file_exported ?>'><b>Download</b></a>
                                            <?php } ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th class='text-center' style="width: 10%">STT</th>
                                                            <th class='text-center'>Từ</th>
                                                            <th class='text-center' style="width: 20%">Số lần xuất hiện</th>
                                                            <?php
                                                                if (isset($final_result) && count($final_result > 0)) {
                                                                    $count = 0;
                                                                    foreach ($final_result as $word => $time) {
                                                                        $count++;
                                                                        echo "<tr>
                                                                                <td class='text-center'>$count</td>
                                                                                <td>$word</td>
                                                                                <td class='text-center'>$time</td>
                                                                            </tr>";
                                                                    }
                                                                }
                                                            ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
          </div>
          <footer class="page-footer font-small teal pt-4">
            <div class="footer-copyright py-3" style='text-align: right'>© 2018 Developer by
              <a href='skype:live:tuandao.dev?chat'> Tuan Dao</a>
            </div>
          </footer>
      </div>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script>
        jQuery.each(jQuery('textarea[data-autoresize]'), function() {
            var offset = this.offsetHeight - this.clientHeight;

            var resizeTextarea = function(el) {
                jQuery(el).css('height', 'auto').css('height', el.scrollHeight + offset);
            };
            jQuery(this).on('keyup input', function() { resizeTextarea(this); }).removeAttr('data-autoresize');
        });
        $(document).ready(function () {
            $('#doan_van').keyup();
        });
    </script>
  </body>
</html>


