<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/6/1 0:04
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http\Middleware;


use Mango\Http\Request;

/**
 * HTTP中间件
 * Class Middleware
 * @package Mango\Http\Middleware
 */
abstract class Middleware{


    /**
     * 中间件处理
     * @param Request  $request
     * @param \Closure $next
     */
    public function handler(Request $request,\Closure $next){
        $next($request);
    }
}