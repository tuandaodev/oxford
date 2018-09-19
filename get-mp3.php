<?php

set_time_limit(0);

$main_url = "https://www.oxfordlearnersdictionaries.com/definition/english/";

$data = file_get_contents('list.txt');

$words = explode("\n", $data);

$count = 0;
foreach ($words as $word) {
    
    $count++;
//    if ($count++ > 5) exit;
    
    $word = trim($word);
    
    $word_url = $main_url . $word;
    $word_data = get_remote_data($word_url);
    
    $replacement = "";
    
    $rex = '/<span class="collapse"(.*?)<\/span><\/span><\/span>/m';
    $word_data = preg_replace($rex, $replacement, $word_data);
    
    $rex = '/data-src-mp3="(.*?).mp3"/m';
    $result = '';
    preg_match_all($rex, $word_data, $result);
    
    if (isset($result[1]) && count($result[1]) > 0) {
        $final_result = $result[1];
        $final_result = array_unique($final_result);
        foreach ($final_result as $mp3_url) {
            $mp3_url = $mp3_url . '.mp3';
            
//            echo $mp3_url . "<br/>";
            
            $file_name = basename($mp3_url);
            $file_path = 'download/' . $file_name;
            file_put_contents($file_path, fopen($mp3_url, 'r'));
        }
        echo "$count [DONE]:   " . $word . " " . count($final_result) . " " . $word_url . "<br/>";
    } else {
        echo "$count [ERROR]:   " . $word . " " . $word_url . "<br/>";
    }
}

echo "ALL DONE. " . $count;

function get_remote_data($url, $post_paramtrs=false)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    if($post_paramtrs)
    {
        curl_setopt($c, CURLOPT_POST,TRUE);
        curl_setopt($c, CURLOPT_POSTFIELDS, "var1=bla&".$post_paramtrs );
    }
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:33.0) Gecko/20100101 Firefox/33.0");
    curl_setopt($c, CURLOPT_COOKIE, 'CookieName1=Value;');
    curl_setopt($c, CURLOPT_MAXREDIRS, 10);
    $follow_allowed= ( ini_get('open_basedir') || ini_get('safe_mode')) ? false:true;
    if ($follow_allowed)
    {
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 9);
    curl_setopt($c, CURLOPT_REFERER, $url);
    curl_setopt($c, CURLOPT_TIMEOUT, 60);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_ENCODING, 'gzip,deflate');
    $data=curl_exec($c);
    $status=curl_getinfo($c);
    curl_close($c);
    preg_match('/(http(|s)):\/\/(.*?)\/(.*\/|)/si',  $status['url'],$link); $data=preg_replace('/(src|href|action)=(\'|\")((?!(http|https|javascript:|\/\/|\/)).*?)(\'|\")/si','$1=$2'.$link[0].'$3$4$5', $data);   $data=preg_replace('/(src|href|action)=(\'|\")((?!(http|https|javascript:|\/\/)).*?)(\'|\")/si','$1=$2'.$link[1].'://'.$link[3].'$3$4$5', $data);
    if($status['http_code']==200)
    {
        return $data;
    }
    elseif($status['http_code']==301 || $status['http_code']==302)
    {
        if (!$follow_allowed)
        {
            if (!empty($status['redirect_url']))
            {
                $redirURL=$status['redirect_url'];
            }
            else
            {
                preg_match('/href\=\"(.*?)\"/si',$data,$m);
                if (!empty($m[1]))
                {
                    $redirURL=$m[1];
                }
            }
            if(!empty($redirURL))
            {
                return  call_user_func( __FUNCTION__, $redirURL, $post_paramtrs);
            }
        }
    }
    return "ERRORCODE22 with $url!!<br/>Last status codes<b/>:".json_encode($status)."<br/><br/>Last data got<br/>:$data";
}