<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/13 11:42
 * @Email: 30191306465@qq.com
 */

namespace Mango\Util;


/**
 * 变量工具类
 * 提供变量挂载功能，支持不同的协程访问同一个挂载点
 * Class VarUtil
 * @package Mango\Util
 */
class VarUtil{

    /**
     * 存放挂载的变量
     * @var array
     */
    private static $vars = [];


    /**
     * 挂载变量
     * @param string $name 变量名称
     * @param mixed $val 变量值
     * @param null $cid 协程id 不传递默认读取当前协程id
     */
    public static function set(string $name,$val,$cid=null): void {
        $cid = $cid ?? \Swoole\Coroutine::getCid();
        self::$vars[$cid][$name] = $val;
        // 协程执行结束释放变量
        defer(function () use ($cid){
            unset(self::$vars[$cid]);
        });
    }

    /**
     * 获取挂载的变量 支持 . 语法
     * @param string $name 变量名称
     * @param null $cid 协程id 默认当前协程id
     * @return mixed|null
     */
    public static function get($name = '',$cid = null){

        $cid = $cid ?? \Swoole\Coroutine::getCid();
        if (isset(self::$vars[$cid])){
            $vars = self::$vars[$cid];
            if (!is_array($vars) || strpos('.',$name) == false)
                return $vars;
            $name = explode('.',$name);
            foreach ($name as $val){
                if (empty($val)){
                    return $vars;
                }
                if (isset($vars[$val])){
                    $vars =  $vars[$val];
                } else{
                    return null;
                }
            }
            return $vars;
        }
        return null;
    }

    /**
     * 获取所有挂载的变量
     * @return array
     */
    public static function all(){
        return self::$vars;
    }

    /**
     * 设置挂载变量
     * 注意是直接赋值的
     * @param $val
     */
    public static function setVars($val){
        self::$vars = $val;
    }
}