<?php
//引用所有类文件
$files = glob(dirname(__FILE__) . '/class/*');
foreach ($files as $file) {
    if (is_file($file) && strstr($file, '.php')) {
        include $file;
    }
}