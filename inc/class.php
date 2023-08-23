<?php
//调用gpt
class chatgpt
{
    public $apikey, $url;
    //构造函数
    public function __construct($apikey, $url)
    {
        $this->apikey = $apikey;
        $this->url = isset($url) ? $url : 'https://api.openai.com';
    }
    public function chat_once($prompts,$stream)
    {
        $url = $this->url . '/v1/chat/completions';
        $post = json_encode($prompts);
        $addheader = ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $this->apikey];
        $data = $this->get_curl($url, $post, $addheader, $stream);
        return $data;
    }
    //curl语句
    protected function get_curl($url, $post = 0, $addheader = 0, $stream = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader = [];
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2";
        $httpheader[] = "Connection: close";
        if ($addheader) {
            $httpheader = array_merge($httpheader, $addheader);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($stream) {
            // 设置回调函数
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $stream);
            curl_exec($ch);
            curl_close($ch);
            return true;
        } else {
            $ret = curl_exec($ch);
            curl_close($ch);
            return $ret;
        }
    }
    protected function call_back($ch, $data)
    {
        echo $data;
        flush();
        return strlen($data);
    }
}
