<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/7 19:35
 * @Email: 30191306465@qq.com
 */

namespace Mango\Exception;



/**
 * 服务异常处理
 * Class ServerExceptionHandel
 * @package Mango\Exception
 */
class ServerExceptionHandel extends Exception {


    /**
     * 异常处理
     * 该方法必须存在，否则出现异常服务将退出
     * @param \swoole_server $server
     * @param \Throwable $throwable
     * @param $raw
     * @param $client
     * @param $response
     */
    public static function handel(\swoole_server $server, \Throwable $throwable, $raw, $client, $response){



    }


}