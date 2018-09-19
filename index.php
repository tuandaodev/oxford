<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tools</title>
  </head>
  <?php
  
    $text_data = '';
    $file_saved = 'saved_input.txt';
    $file_exported = 'exported.csv';
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
        }
        
        if (isset($_POST['type_reset']) && $_POST['type_reset'] == 'reset') {
            if (file_exists($file_saved)) unlink($file_saved);
            if (file_exists($file_exported)) unlink($file_exported);
        }
        
    } else {
        if (file_exists($file_saved)) {
            $text_data = file_get_contents($file_saved);
            if (file_exists($file_exported)) {
                $have_exported_file = true;
            }
        }
    }
    
    if ($text_data) {
        $text_data = strtolower($text_data);
//        $all_words = str_word_count($text_data, 1);
        // new function
        $all_words = utf8_str_word_count($text_data, 1);
        
        $result = array_count_values($all_words);
        arsort($result);
        
        if ($need_export && count($result) > 0) {
            $fp = fopen($file_exported, 'w');
            
            $count = 0;
            foreach ($result as $word => $time) {
                $count++;
                $temp = array($count,$word,$time);
                fputcsv($fp, $temp);
            }
            fclose($fp);
            $have_exported_file = true;
        }
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
                                                                if (isset($result) && count($result > 0)) {
                                                                    $count = 0;
                                                                    foreach ($result as $word => $time) {
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

    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
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


