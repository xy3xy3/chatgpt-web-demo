<?php

include 'inc/inc.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//静态
$api_key = "sk-gePYPPA9qdXlrfXvZAtQGcA9bcwhwbJXmhwY3s1KkZZVKzav";
$api_url = "https://api.chatanywhere.com.cn";
$gpt = new chatgpt($api_key,$api_url);
$model =  'gpt-3.5-turbo';
//测试插件
$cj = new chart($plugin);
$a  = $cj->beforeApiSend("画一个2021重庆12月降雨图");

var_dump($a);






exit;
$api_key = "sk-gePYPPA9qdXlrfXvZAtQGcA9bcwhwbJXmhwY3s1KkZZVKzav";
$api_url = "https://api.chatanywhere.com.cn";
$gpt = new chatgpt($api_key,$api_url);

$a = $gpt->generate_image("a white cat in school",1);

pr($a);