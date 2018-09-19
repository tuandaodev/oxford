<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>English Tool - Phân tích và so sánh các đoạn văn</title>
  </head>
  <?php
  
    $text_data = '';
    $file_saved = 'input/saved_input.txt';
    $file_exported = 'export/exported.csv';
    $need_export = false;
    $have_exported_file = false;
    
    if (isset($_POST) && !empty($_POST)) {
        
        echo "<pre>";
        print_r($_POST);
        echo "<pre>";
        exit;
        
        
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
                        <div class="panel-heading">Phân tích và so sánh các đoạn văn
                          </div>
                        <div class="panel-body">
                            <form method="POST">
                            <div class="row">
                                <div class="col-lg-7">
                                        <button type="button" class="btn btn-primary" onclick="add_textarea()">Add Paragraph</button>
                                        <button type="submit" class="btn btn-success" name="type_submit" value="submit">Do Analysis</button>
                                        <button type="submit" class="btn btn-default" name="type_reset" value="reset">Reset</button>    
                                        <div id="textarea-container" class="form-group" style="margin-top: 10px;">
                                            <div id="textarea1" class="multi-textarea">
                                                <label>Đoạn #1:</label>
                                                <textarea data-autoresize class="form-control" rows="5" name="doan_van[]" style="resize:vertical;"><?php echo $text_data ?></textarea>
                                            </div>
                                        </div>
                                </div>
                                    <div class="col-md-5">
                                        <div>
                                            <label>List có sẵn:</label>
                                            <textarea data-autoresize class="form-control" rows="10" name="list_co_san" style="resize:vertical;"></textarea>
                                        </div>
                                    </div>
                            </div>
                            </form>
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
        var count_tx = 1;
        function add_textarea() {
            count_tx++;
            var html_code = "<div id='textarea" + count_tx + "' class='multi-textarea'><label>Đoạn #" + count_tx + ":</label><textarea data-autoresize class='form-control' rows='5' name='doan_van[]' style='resize:vertical;'></textarea></div>";
            $('#textarea-container').append(html_code);
            
            do_autoresize();
        }
    </script>
  </body>
</html>


