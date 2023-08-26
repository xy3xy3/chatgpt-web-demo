<?php
define("INC_ROOT",dirname(__FILE__) . '/');
define("SITE_ROOT",dirname(dirname(__FILE__)) . '/');
include 'function.php';
include 'class.php';
require(SITE_ROOT.'vendor/autoload.php');
$_HEADER = getUserRequestHeaders();
//注册插件
$plugin = new plugin();