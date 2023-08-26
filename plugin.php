<?php
include 'inc/inc.php';
$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 0;
$pluginName = isset($_REQUEST['pluginName']) ? $_REQUEST['pluginName'] : false;
$fun = isset($_REQUEST['fun']) ? $_REQUEST['fun'] : false;
switch ($act) {
    case 'list':

        $plugins = get_active_plugins(0);
        $tip_arr = [
            "data" => $plugins,
            "code" => 0
        ];
        tip($tip_arr);
        break;
    case 'call':
        $plugins = get_active_plugins(1);
        break;
}
