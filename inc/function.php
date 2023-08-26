<?php
//传递log
function stream_log($arr)
{
    $s_back = array(
        'content' => "",
        'pluginLog' => $arr,
        'is_end' => false,
    );
    echo json_encode($s_back);
    flush();
}
//传递对话
function stream_content($content,$is_end = false)
{
    $s_back = array(
        'content' => $content,
        'pluginLog' => [],
        'is_end' => $is_end,
    );
    echo json_encode($s_back);
    flush();
    if ($is_end) {
        die;
    }
}
// 获取插件目录下的所有文件夹
function get_active_plugins($details = 0)
{
    $plugin_directories = glob(INC_ROOT . 'plugins/*', GLOB_ONLYDIR);
    $active_plugins = array();

    foreach ($plugin_directories as $directory) {
        // 获取插件文件夹的名称
        $plugin_name = basename($directory);
        if (file_exists($directory . '/main.php') && file_exists($directory . '/info.php')) {
            include $directory . '/info.php';
            // 将插件信息添加到活动插件数组中
            if (!$plugin_info['active']) continue;

            if ($details == 1) {

                $active_plugins[] = array(
                    'name' => $plugin_info['name'],
                    'description' => $plugin_info['description'],
                    'file_name' => $plugin_name,
                    'directory' => $directory
                );
            } else {

                $active_plugins[] = array(
                    'name' => $plugin_info['name'],
                    'description' => $plugin_info['description'],
                    'file_name' => $plugin_name,
                );
            }
        }
    }

    return $active_plugins;
}
if (!function_exists("is_https")) {
    function is_https()
    {
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        } elseif (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && $_SERVER['HTTP_X_CLIENT_SCHEME'] == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        } elseif (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_EWS_CUSTOME_SCHEME']) && $_SERVER['HTTP_EWS_CUSTOME_SCHEME'] == 'https') {
            return true;
        }
        return false;
    }
}
function curl_get($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("CURL Error: " . $error);
    }

    curl_close($ch);

    return $response;
}

/**
 * 获取某个字符串之后的内容（包括该字符串）
 *
 * @param string $haystack 原始字符串
 * @param string $needle 目标字符串
 * @return string|bool 返回目标字符串之后的内容，如果未找到目标字符串则返回false
 */
function get_after_str($haystack, $needle)
{
    $pos = mb_strpos($haystack, $needle);
    if ($pos === false) {
        return false;
    }
    return mb_substr($haystack, $pos + mb_strlen($needle));
}
function getUserRequestHeaders()
{
    $headers = array();

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            }
        }
    }

    return $headers;
}
function pr($a)
{
    var_dump($a);
    exit;
}
function tip($data)
{
    exit(json_encode($data));
}
