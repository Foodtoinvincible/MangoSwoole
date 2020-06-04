<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/26 22:41
 * @Email : 30191306465@qq.com
 */

namespace Mango\Socket;

/**
 * 服务接口
 * Interface ServerInterface
 * @package Mango\Websocket
 */
interface ServerInterface{


    /**
     * 绑定事件
     * @param string   $name
     * @param callable $callback
     * @return mixed
     */
    public function bindEvent(string $name,callable $callback);

    /**
     * 启动
     * @return mixed
     */
    public function boot();

}