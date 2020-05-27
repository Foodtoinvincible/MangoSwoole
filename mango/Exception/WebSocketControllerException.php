<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/6 17:22
 * @Email: 30191306465@qq.com
 */

namespace Mango\Exception;


class WebSocketControllerException extends Exception {


    /**
     * WebSocket控制器异常处理
     * 该方法必须存在，否则出现异常服务将退出
     * @param \swoole_websocket_server $server
     * @param \Throwable $throwable
     * @param $raw
     * @param $client
     * @param $response
     */
    public static function handel(\swoole_websocket_server $server,\Throwable $throwable,$raw,$client,$response){




    }

}