<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/7 21:45
 * @Email : 30191306465@qq.com
 */

namespace Mango\Component;


trait Singleton
{
    private static $instance;

    public static function getInstance(...$args)
    {
        if(!isset(self::$instance)){
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}