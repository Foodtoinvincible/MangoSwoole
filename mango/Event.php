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
 * Class Event
 * @package Mango
 */
class Event{

    const Message = 'message';
    const Close = 'close';
    const Handshake = 'Handshake';
    const Open  = 'Open';

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
     * @return mixed|null
     */
    public static function get(string $name){
        return self::$event[strtolower($name)] ?? null;
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