<?php
include 'inc/inc.php';
// 获取请求体数据
$requestBody = file_get_contents('php://input');
// 将请求体数据解析为关联数组
if (!$bodyData = json_decode($requestBody, true)) {
    $tip_arr = [
        "object" => "error",
        "message" => "body解析失败",
        "code" => 1
    ];
    tip($tip_arr);
}
$api_url = isset($bodyData['ApiUrl']) ? $bodyData['ApiUrl'] : 0;
$api_key = isset($bodyData['ApiKey']) ? $bodyData['ApiKey'] : 0;
//构造gpt对象
$gpt = new chatgpt($api_key, $api_url);
//获取模型，prompt
$prompt = $bodyData['message'];
$history = isset($bodyData['history']) ? $bodyData['history'] : 0;
if (!$prompt) {
    $tip_arr = [
        "object" => "error",
        "message" => "数据错误",
        "code" => 1
    ];
    tip($tip_arr);
}
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/event-stream");
header("X-Accel-Buffering: no");
$temperature = isset($bodyData['temperature']) ? $bodyData['temperature'] : 0;
$model = isset($bodyData['model']) ? $bodyData['model'] : 'gpt-3.5-turbo';
$top_p = isset($bodyData['top_p']) ? $bodyData['top_p'] : 0.7;
$pluginSelect = !empty($bodyData['pluginSelect']) ? $bodyData['pluginSelect'] : [];
$postdata = [
    "model" => $model,
    "temperature" => $temperature,
    "stream" => true,
    "top_p" => $top_p,
];
$msg_arr = [];
if (!empty($history)) {
    //加入上下文
    foreach ($history as $item) {
        $msg_arr[] = ['role' => $item['name'] == '用户' ? 'user' : 'assistant', 'content' => $item['msg']];
    }
}
//钩子，修改用户对话
$hook = $plugin->trigger('beforeApiSend', [$prompt, $history], $pluginSelect);
if (!empty($hook)) {
    foreach ($hook as $item) {
        if (!empty($item['msg'])) {
            $prompt = $item['msg'].$prompt;
        }
    }
}
//  exit;
$msg_arr[] = ['role' => 'user', 'content' => $prompt];
$postdata['messages'] = $msg_arr;
$data_buffer = '';
$streamCallback = function ($ch, $data) use (&$data_buffer) {
    $result = json_decode($data, true);
    if (is_array($result)) {
        $s_back = array(
            'msg' => "openai 请求错误：" . json_encode($result),
            'is_end' => true,
        );
        echo json_encode($s_back);
        return strlen($data);
    }

    /*
        此处步骤仅针对 openai 接口而言
        每次触发回调函数时，里边会有多条data数据，需要分割
        如某次收到 $data 如下所示：
        data: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"role":"assistant"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"以下"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"是"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"使用"},"index":0,"finish_reason":null}]}

        最后两条一般是这样的：
        data: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{},"index":0,"finish_reason":"stop"}]}\n\ndata: [DONE]

        根据以上 openai 的数据格式，分割步骤如下：
    */

    // 0、把上次缓冲区内数据拼接上本次的data
    $buffer = $data_buffer . $data;

    //拼接完之后，要把缓冲字符串清空
    $data_buffer = '';

    // 1、把所有的 'data: {' 替换为 '{' ，'data: [' 换成 '['
    $buffer = str_replace('data: {', '{', $buffer);
    $buffer = str_replace('data: [', '[', $buffer);

    // 2、把所有的 '}\n\n{' 替换维 '}[br]{' ， '}\n\n[' 替换为 '}[br]['
    $buffer = str_replace("}\n\n{", '}[br]{', $buffer);
    $buffer = str_replace("}\n\n[", '}[br][', $buffer);

    // 3、用 '[br]' 分割成多行数组
    $lines = explode('[br]', $buffer);

    // 4、循环处理每一行，对于最后一行需要判断是否是完整的json
    $line_c = count($lines);
    foreach ($lines as $li => $line) {
        if (trim($line) == '[DONE]') {
            //数据传输结束
            $data_buffer = '';
            $s_back = array(
                'content' => "",
                'pluginLog' => [],
                'is_end' => true,
            );
            echo json_encode($s_back);
            break;
        }
        $line_data = json_decode(trim($line), TRUE);
        if (!is_array($line_data) || !isset($line_data['choices']) || !isset($line_data['choices'][0])) {
            if ($li == ($line_c - 1)) {
                //如果是最后一行
                $data_buffer = $line;
                break;
            }
            continue;
        }

        if (isset($line_data['choices'][0]['delta']) && isset($line_data['choices'][0]['delta']['content'])) {
            $s_back = array(
                'content' => $line_data['choices'][0]['delta']['content'],
                'pluginLog' => [],
                'is_end' => false,
            );
            echo json_encode($s_back);
            flush();
        }
    }

    return strlen($data);
};
$gpt->chat_once($postdata, $streamCallback);
// $s_back = array(
//     'content' => "",
//     'pluginLog' => [],
//     'is_end' => true,
// );
// echo json_encode($s_back);