<?php
function httpGet($url)
{
    $ch = curl_init();  
 
    curl_setopt($ch,CURLOPT_URL,'https://api.urlmeta.org/?url='.$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
//  curl_setopt($ch,CURLOPT_HEADER, false); 
 
    $output=curl_exec($ch);
 
    curl_close($ch);
    return $output;
}
 
$url = $_POST['url'];

$json = httpGet($url);
/*$decode = json_decode($json, true);
echo $decode['result']['status'];*/
header('Content-type: application/json');
echo $json; 

 