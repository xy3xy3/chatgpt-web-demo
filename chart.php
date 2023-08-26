<?php
include 'inc/inc.php';
//测试插件

$filename = $_GET['filename'];
$filename = str_replace(array('.', '/'), '', $filename);
$filename = SITE_ROOT . '/cache/' . $filename . '.json';
$json = file_get_contents($filename);
$arr = json_decode($json,true);
#pr($arr);
call_user_func_array(['chart',$arr['funname']],$arr['args']);
return;

$xData = array('A', 'B', 'C', 'D', 'E');
$yData = array(10, 20, 30, 40, 50);
$xLabel = 'X轴';
$yLabel = 'Y轴';

chart::createBarChart($xData, $yData, $xLabel, $yLabel);
return;