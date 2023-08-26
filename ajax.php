<?php
//允许跨域
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');

include 'inc/inc.php';
$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 0;
switch ($act) {
    case 'getRoles':
        $f = file_get_contents('./prompts-zh.json');
        $json = json_decode($f,true);
        $tip_arr = [
            "data" => $json,
            "code" => 0
        ];
        tip($tip_arr);
        break;
}
