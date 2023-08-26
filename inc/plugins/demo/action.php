<?php
/**
* 这是一个Hello World简单插件的实现
*/
/**
*需要注意的几个默认规则：
*    1. 本插件类的文件名必须是action
*    2. 插件类的名称必须是{插件名_actions}
*/
class demo
{
    //解析函数的参数是pluginManager的引用
    function __construct(&$pluginManager)
    {
        //注册这个插件
        //第一个参数是钩子的名称
        //第二个参数是pluginManager的引用
        //第三个是插件所执行的方法
        $pluginManager->register('demo', $this, 'say_hello');
    }

    function say_hello()
    {
        echo 'Hello World';
    }
}