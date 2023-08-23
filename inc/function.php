<?php

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
