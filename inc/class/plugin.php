<?php

/**
 *
 * 插件机制的实现核心类

 */
class plugin
{
    /**
     * 监听已注册的插件
     *
     * @access private
     * @var array
     */
    private $_listeners = array();
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $plugins = get_active_plugins(1);
        if ($plugins) {
            foreach ($plugins as $plugin) { //假定每个插件文件夹中包含一个actions.php文件，它是插件的具体实现
                if (@file_exists($plugin['directory'] . '/main.php')) {
                    include_once($plugin['directory'] . '/main.php');
                    $class = $plugin['file_name'];
                    if (class_exists($class)) {
                        //初始化所有插件
                        new $class($this);
                    }
                }
            }
        }
        #此处做些日志记录方面的东西
    }

    /**
     * 注册需要监听的插件方法（钩子）
     *
     * @param string $hook
     * @param object $reference
     * @param string $method
     */
    function register($hook, $class_name, &$reference, $method)
    {
        //获取插件要实现的方法
        $key = get_class($reference) . '->' . $method;
        //将插件的引用连同方法push进监听数组中
        $this->_listeners[$hook][$key] = array($class_name, &$reference, $method);
        #此处做些日志记录方面的东西
    }
    /**
     * 触发一个钩子
     *
     * @param string $hook 钩子的名称
     * @param mixed $data 钩子的入参
     *    @return mixed
     */
    function trigger($hook, $data = [], $available = [])
    {
        $result = [];
        //查看要实现的钩子，是否在监听数组之中
        if (isset($this->_listeners[$hook]) && is_array($this->_listeners[$hook]) && count($this->_listeners[$hook]) > 0) {
            // 循环调用开始
            foreach ($this->_listeners[$hook] as $listener) {
                // 取出插件对象的引用和方法
                $class_name = $listener[0];
                $class = &$listener[1];
                $method = $listener[2];
                $available = array_map('strtolower', $available);
                if (!empty($available) && !in_array(strtolower($class_name), $available)) {
                    continue;
                }
                if (method_exists($class, $method)) {
                    //返回开始使用log给客户端
                    $arr = [];
                    $arr['name'] = $class->pluginInfo()['name'];
                    $arr['input'] = "";
                    $arr['output'] = "";
                    $arr['msg'] = "调用插件";
                    stream_log($arr);
                    // 动态调用插件的方法
                    $result[$class_name] = call_user_func_array([$class, $method], $data);
                    if (!empty($result[$class_name]['log'])) {
                        $s_back = array(
                            'content' => "",
                            'pluginLog' => $result[$class_name]['log'],
                            'is_end' => false,
                        );
                        echo json_encode($s_back);
                        flush();
                    }
                }
            }
        }
        #此处做些日志记录方面的东西
        return $result;
    }
}
