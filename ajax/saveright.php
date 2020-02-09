<?php
if(isset($_POST['update']))
{
function isJson($string) {
    return ((is_string($string) &&
            (is_object(json_decode($string)) ||
            is_array(json_decode($string))))) ? true : false;
}
$data=$_POST['update'];

	if(isJson($data)){
$file_list_input = '../input/compare_list_input.txt';
$data=json_decode($data);

file_put_contents($file_list_input, json_encode($data));
}
}
?>