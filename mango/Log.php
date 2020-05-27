<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/13 17:18
 * @Email: 30191306465@qq.com
 */

namespace Mango;

use Mango\Log\Log as LogServer;

/**
 * @package Mango
 * @mixin LogServer
 * @method bool save() static 保存日志到文件
 * @method bool write($msg, $type = 'info', $force = false) static 实时写入日志信息 并支持行为
 * @method void log($level, $message, array $context = []) static 记录日志信息
 * @method void emergency($message, array $context = []) static 记录emergency级日志
 * @method void alert($message, array $context = []) static 记录alert级日志
 * @method void critical($message, array $context = []) static 记录critical级日志
 * @method void error($message, array $context = []) static 记录error级日志
 * @method void warning($message, array $context = []) static 记录warning级日志
 * @method void notice($message, array $context = []) static 记录notice级日志
 * @method void info($message, array $context = []) static 记录info级日志
 * @method void debug($message, array $context = []) static 记录debug级日志
 * @method void sql($message, array $context = []) static 记录sql信息
 * @method LogServer clear() static 清空日志信息
 * @method LogServer key($key) static 当前日志记录的授权key
 * @method bool check($config) static 检查日志写入权限
 * @method bool close() static 关闭日志写入
 * Class Log
 * @package Mango
 */
class Log{


    public static function __callStatic($name, $arguments){
        return call_user_func([LogServer::getInstance(),$name],$arguments);
    }

}