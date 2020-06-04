<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/26 22:08
 * @Email : 30191306465@qq.com
 */

namespace Mango;


use Mango\Component\Singleton;

/**
 * 事件管理。
 * Class EventRegister
 * @package Mango
 */
class EventRegister{

    const Message               = 'Message';
    const Close                 = 'Close';
    const Handshake             = 'Handshake';
    const Open                  = 'Open';
    const Start                 = 'Start';
    const Shutdown              = 'Shutdown';
    const WorkerStart           = 'WorkerStart';
    const WorkerStop            = 'WorkerStop';
    const WorkerExit            = 'WorkerExit';
    const Connect               = 'Connect';
    const Receive               = 'Receive';
    const Packet                = 'Packet';
    const Task                  = 'Task';
    const Finish                = 'Finish';
    const PipeMessage           = 'PipeMessage';
    const WorkerError           = 'WorkerError';
    const ManagerStart          = 'ManagerStart';
    const ManagerStop           = 'ManagerStop';
    const BeforeReload          = 'BeforeReload';
    const AfterReload           = 'AfterReload';
    const Request               = 'request';
    // 服务创建前事件
    const ServerCreateBefore    = 'ServerCreateBefore';
    // 服务创建后事件
    const ServerCreateAfter     = 'ServerCreateAfter';

    /**
     * 事件
     * @var array
     */
    private static $event = [];


    /**
     * 添加一个事件
     * @param string   $name 事件名称
     * @param callable $callback  回调
     */
    public static function add(string $name,callable $callback){
        self::$event[strtolower($name)] = $callback;
    }

    /**
     * 删除事件
     * @param string $name 事件名称
     */
    public static function delete(string $name){
        if (isset(self::$event[strtolower($name)])){
            unset(self::$event[strtolower($name)]);
        }
    }

    /**
     * 获取事件
     * @param string $name
     * @param null   $default 默认
     * @return mixed|null
     */
    public static function get(string $name,$default = null){
        return self::$event[strtolower($name)] ?? $default;
    }

    /**
     * 执行指定事件
     * @param string $name
     * @param array  $vars
     * @return mixed|null
     */
    public static function exec(string $name,array $vars=  []){

        if (self::has($name)){
            return call_user_func_array(self::get($name),$vars);
        }else{
            return null;
        }
    }

    /**
     * 判断事件是否存在
     * @param string $name
     * @return bool
     */
    public static function has(string $name){
        return isset(self::$event[strtolower($name)]);
    }
}