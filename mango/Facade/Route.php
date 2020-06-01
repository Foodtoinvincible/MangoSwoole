<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/31 20:05
 * @Email : 30191306465@qq.com
 */

namespace Mango\Facade;


use Mango\Route\RuleGroup;

/**
 * 使用外观模式。建立 Route 方法静态调用
 * Class Route
 * @package Mango\Facade
 * @mixin \Mango\Route\Route
 * @method void rule(string $name,$route,string $method = "*") static    定义一个路由
 * @method void post(string $name,$route) static    定义POST路由
 * @method void get(string $name,$route) static     定义GET路由
 * @method void put(string $name,$route) static     定义PUT路由
 * @method void patch(string $name,$route) static   定义PATCH路由
 * @method void options(string $name,$route) static 定义OPTIONS路由
 * @method void head(string $name,$route) static    定义HEAD路由
 * @method void delete(string $name,$route) static  定义DELETE路由
 * @method RuleGroup group($name,$route = null) static  定义路由组
 */
class Route extends Facade{

    protected static function getFacadeClass()
    {
        return \Mango\Route\Route::class;
    }
}