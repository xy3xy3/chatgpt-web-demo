<?php

use Hisune\EchartsPHP\ECharts;
use Hisune\EchartsPHP\Doc\IDE\XAxis;
use Hisune\EchartsPHP\Doc\IDE\YAxis;

class chart
{
    var $input, $output, $info;
    //解析函数的参数是pluginManager的引用
    public function __construct(&$pluginManager)
    {
        //监听的地点，类名字，类的实例，类的方法
        $pluginManager->register('beforeApiSend', 'chart', $this, 'beforeApiSend');
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
        //真正的实现
        $arr = $this->get_prompt($prompt, $history);
        if (!is_array($arr)) return false;
        $msg = $this->build_new_con($arr['filename'],$arr['description']);
        $arr = ['msg' => $msg, 'log' => $this->pluginLog()];
        return $arr;
    }
    public function build_new_con($filename,$description)
    {
        $p =  is_https() ? "https://" : "http://";
        $url = $p . $_SERVER['HTTP_HOST'] . '/chart.php?filename=' . $filename;
        $this->input = $description;
        $this->output = $url; //设置plugin输出
        $msg = "假设你是一个制作图表机器人，假设你完成了一个图表，描述：'{$description}'，url'{$url}'，现在简练的介绍图表并且返回给用户图表链接使用markdown的超链接,使用中文回复";
        return $msg;
    }
    //调用gpt获取提示词
    public function get_prompt($prompt, $history = [])
    {
        global $gpt, $model;
        //获取绘画prompt
        $_stream = [];
        $_stream['name'] = $this->pluginInfo()['name'];
        $_stream['input'] = "";
        $_stream['output'] = "";
        $_stream['msg'] = "构建图表参数";
        stream_log($_stream);
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
            $args = json_decode($message["function_call"]['arguments'],true);
            $arr = [];
            foreach ($args as $arg) {
                if (json_decode($arg) !== null) {
                    // 是有效的 JSON，进行解码
                    $arg = json_decode($arg,true);
                }
                $arr[] = $arg;
            }
            //这里要间接使用
            if ($funname == 'direct_answer') {
                $f = call_user_func_array([$this, $funname], $arr);
                return $f;
            }
            $_stream = [];
            $_stream['name'] = $this->pluginInfo()['name'];
            $_stream['input'] = "";
            $_stream['output'] = "";
            $_stream['msg'] = "调用图表函数";
            stream_log($_stream);
            //调用生产缓存数据
            $res = $this->make_cache($funname, $arr);
            return $res;
        } else {
            $prompt = $message['content'];
            return $prompt;
        }
    }
    public function chat_dalle_prompt()
    {
        return '你必须使用function_call回调；
        如果直接说明画图表，则用户是在与你聊天交流，使用`direct_answer`函数，回复用户；
        如果说明了要画图表，选择最适合表示用户需求的图表，如果用户提供了数据，使用用户的数据，如果未提供，编造合理的数据
        ' . "输入：\n";
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
        return false;
    }
    //调用php图表
    public function make_cache($funname, $arr)
    {
        $j_arr = ['funname'=>$funname,'args'=>$arr];
        $code = json_encode($j_arr);
        $fname = date('YmdHis').md5($code);
        $filename = SITE_ROOT . '/cache/' . $fname . '.json';
        file_put_contents($filename, $code);
        return ['filename' => $fname,'description'=>$code];
    }
    //状图
    public static function createBarChart($xData, $yData, $xLabel, $yLabel)
    {
        $chart = new ECharts();
        $chart->tooltip->show = true;
        $chart->legend->data[] = $xLabel;
        $chart->xAxis[] = array(
            'type' => 'category',
            'data' => $xData
        );
        $chart->yAxis[] = array(
            'type' => 'value'
        );
        $chart->series[] = array(
            'name' => $yLabel,
            'type' => 'bar',
            'data' => $yData
        );
        echo $chart->render('chart');
    }
    //折线图
    public static function createLineChart($xData, $yData, $xLabel, $yLabel)
    {
        $chart = new ECharts();
        $chart->tooltip->show = true;
        $chart->legend->data[] = $xLabel;
        $chart->xAxis[] = array(
            'type' => 'category',
            'data' => $xData
        );
        $chart->yAxis[] = array(
            'type' => 'value'
        );
        $chart->series[] = array(
            'name' => $yLabel,
            'type' => 'line',
            'data' => $yData
        );
        echo $chart->render('chart');
    }
    //饼图
    public static function createPieChart($data)
    {
        $chart = new ECharts();
        $chart->tooltip->show = true;
        $chart->series[] = array(
            'type' => 'pie',
            'data' => $data
        );
        echo $chart->render('chart');
    }
    public function gpt_fun()
    {
        $arr =  [
            [
                "name" => "createBarChart",
                "description" => "创建柱状图",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "xData" => [
                            "type" => "string",
                            "description" => "X轴数据的一维数组，用json格式表示"
                        ],
                        "yData" => [
                            "type" => "string",
                            "description" => "Y轴数据的一维数组，用json格式表示"
                        ],
                        "xLabel" => [
                            "type" => "string",
                            "description" => "X轴标签"
                        ],
                        "yLabel" => [
                            "type" => "string",
                            "description" => "Y轴标签"
                        ]
                    ],
                    "required" => ["xData", "yData", "xLabel", "yLabel"]
                ]
            ],
            [
                "name" => "createLineChart",
                "description" => "创建折线图",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "xData" => [
                            "type" => "string",
                            "description" => "X轴数据的一维数组，用json格式表示"
                        ],
                        "yData" => [
                            "type" => "string",
                            "description" => "Y轴数据的一维数组，用json格式表示"
                        ],
                        "xLabel" => [
                            "type" => "string",
                            "description" => "X轴标签"
                        ],
                        "yLabel" => [
                            "type" => "string",
                            "description" => "Y轴标签"
                        ]
                    ],
                    "required" => ["xData", "yData", "xLabel", "yLabel"]
                ]
            ],
            [
                "name" => "createPieChart",
                "description" => "创建饼图",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "data" => [
                            "type" => "string",
                            "description" => "包含图表数据的数组，每个元素有value和name，用json格式表示"
                        ]
                    ],
                    "required" => ["data"]
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
