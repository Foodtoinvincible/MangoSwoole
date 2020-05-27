<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/7 19:41
 * @Email: 30191306465@qq.com
 */


if (!function_exists('config')) {

    /**
     * 获取配置
     * @param string $name 参数名
     * @param mixed $value 参数值
     * @return array|bool|mixed|null
     */
    function config(string $name = '',$value = ''){

        if (!empty($value) && !empty($name)){
            return \Mango\Config::set($name,$value);
        }

        return \Mango\Config::get($name);
    }
}

if (!function_exists('server')) {

    /**
     * 快速获取当前Swoole服务对象
     * @return swoole_http_server|swoole_server|swoole_server_port|swoole_websocket_server|null
     */
    function server(){
        return EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer();
    }
}


if (!function_exists('each_conn_list')) {


    /**
     * 遍历当前连接标识
     * @param callable $callback 回调函数
     */
    function each_conn_list(callable $callback): void{

        $server = server();
        $conn_list = $server->connection_list();
        if($conn_list === false or count($conn_list) === 0) {
            return;
        }

        foreach($conn_list as $fd) {
            $callback($server,$fd);
        }

    }
}