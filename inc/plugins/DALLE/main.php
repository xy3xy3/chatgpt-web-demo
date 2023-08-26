<?php
class dalle
{
    var $input, $output, $info;
    //解析函数的参数是pluginManager的引用
    public function __construct(&$pluginManager)
    {
        //监听的地点，类名字，类的实例，类的方法
        $pluginManager->register('beforeApiSend', 'dalle', $this, 'beforeApiSend');
        include dirname(__FILE__) . '/info.php';
        $this->info = $plugin_info;
    }
    //必须，调用记录
    public function pluginLog()
    {
        $arr = [];
        $arr['name'] = $this->info['name'];
        $arr['input'] = $this->input;
        $arr['output'] = $this->output;
        $arr['msg'] = "";
        return $arr;
    }
    //必须，返回插件信息
    public function pluginInfo()
    {
        return $this->info;
    }

    public function beforeApiSend($prompt, $history = [])
    {
        // //假消息用于测试
        // $this->input = $this->output = 233;
        // $arr = ['msg' => '2222', 'log' => $this->pluginLog()];
        // return $arr;
        //真正的实现
        $arr = $this->get_prompt($prompt, $history);
        // $arr = $this->call_dalle($draw_prompt);
        $msg = $this->build_new_con($arr['filename'], $arr['draw_prompt']);
        $arr = ['msg' => $msg, 'log' => $this->pluginLog()];
        return $arr;
    }
    public function build_new_con($filename, $draw_prompt)
    {
        $p =  is_https() ? "https://" : "http://";
        $url = $p . $_SERVER['HTTP_HOST'] . '/cache/' . basename($filename);
        $this->output = $url;//设置plugin输出
        $msg = "当你想发送一张图片时，请使用 Markdown ,并且 不要有反斜线, 不要用代码块。假设你是一个绘画机器人，你完成了一幅作品，图片描述：'{$draw_prompt}'，图片url'{$url}'，现在简练的介绍这幅画并且返回给用户图片,使用中文回复";
        return $msg;
    }
    //调用gpt获取提示词
    public function get_prompt($prompt, $history = [])
    {
        global $gpt, $model;
        //获取绘画prompt
        $arr = [];
        $arr['name'] = $this->pluginInfo()['name'];
        $arr['input'] = "";
        $arr['output'] = "";
        $arr['msg'] = "获取绘画提示词";
        stream_log($arr);
        $msg_arr = [];
        if (!empty($history)) {
            //加入上下文
            foreach ($history as $item) {
                $msg_arr[] = ['role' => $item['name'] == '用户' ? 'user' : 'assistant', 'content' => $item['msg']];
            }
        }
        $msg_arr[] = ['role' => 'user', 'content' => $this->chat_dalle_prompt() . $prompt];
        $postdata = [
            "model" => $model,
            "temperature" => 0.2,
            "messages" => $msg_arr,
            "functions" => $this->gpt_fun(),
            'function_call' => 'auto',
        ];
        // $postdata = json_encode($postdata);
        $data = $gpt->chat_once($postdata);
        if (!$json = json_decode($data, true)) {
            return false;
        }
        if (!$json['choices']) {
            return false;
        }
        $message = $json['choices'][0]['message'];
        if (!empty($message["function_call"])) {
            $funname = $message["function_call"]['name'];
            $args = json_decode($message["function_call"]['arguments']);
            $arr = [];
            foreach ($args as $arg) {
                $arr[] = $arg;
            }
            $f = call_user_func_array([$this, $funname], $arr);
            return $f;
        } else {
            $draw_prompt = $message['content'];
            return $draw_prompt;
        }
    }
    public function chat_dalle_prompt()
    {
        return "你必须使用function_call回调；如果直接说明画画，则用户是在与你聊天交流，使用`direct_answer`函数，回复用户；如果说明了要画画，使用`call_dalle`函数，将用户描述翻译成对应英文你翻译后的内容主要服务于一个绘画AI，它只能理解具象的描述而非抽象的概念，同时根据你对绘画AI的理解，比如它可能的训练模型、自然语言处理方式等方面，进行翻译优化。由于我的描述可能会很散乱，不连贯，你需要综合考虑这些问题，然后对翻译后的英文内容再次优化或重组，从而使绘画AI更能清楚我在说什么。输入：\n";
    }
    //回复用户要求详细
    public function direct_answer($msg)
    {
        $arr = [];
        $arr['name'] = $this->pluginInfo()['name'];
        $arr['input'] = "";
        $arr['output'] = "直接回复";
        $arr['msg'] = "";
        stream_log($arr);
        sleep(1);
        stream_content($msg, true);
        return;
    }
    //调用gpt绘画api
    public function call_dalle($draw_prompt)
    {
        global $gpt, $model;
        $this->input = $draw_prompt;//设置log输入
        $arr = [];
        $arr['name'] = $this->pluginInfo()['name'];
        $arr['input'] = "";
        $arr['output'] = "";
        $arr['msg'] = "发送绘画请求";
        stream_log($arr);
        //发送绘画请求
        $data = $gpt->generate_image($draw_prompt, 1);
        if (!$json = json_decode($data, true)) return false;
        $picture = $json['data'][0]['url'];
        //保存图片数据
        $img_data = curl_get($picture);
        $filename = SITE_ROOT . '/cache/' . date('YmdHis') .  md5($picture) . '.png';
        file_put_contents($filename, $img_data);
        return ['filename' => $filename, 'draw_prompt' => $draw_prompt];
    }
    public function gpt_fun()
    {
        $arr =  [
            [
                "name" => "call_dalle",
                "description" => "use dalle model to draw a picture",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "draw_prompt" => [
                            "type" => "string",
                            "description" => "Use English to describe the picture in 1000 chars",
                        ],
                    ],
                    "required" => ["draw_prompt"],
                ]
            ],
            [
                "name" => "direct_answer",
                "description" => "直接用中文回复问题",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "msg" => [
                            "type" => "string",
                            "description" => "Reply in Chinese",
                        ],
                    ],
                    "required" => ["msg"],
                ]
            ],
        ];
        return $arr;
    }
}
