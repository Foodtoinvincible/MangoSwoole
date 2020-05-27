<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 9:57
 * @Email : 30191306465@qq.com
 */

namespace Mango\Route;


use Mango\Component\Singleton;
use Mango\Exception\NotFountRouteException;
use Mango\Http\Request;

class Route{

    static $rule  = [
        '*'       => [],
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'patch'   => [],
        'delete'  => [],
        'head'    => [],
        'options' => [],
    ];

    /**
     * 添加路由
     * @param string $rule      规则
     * @param string $to        路由到
     * @param string $method    请求方法 ,隔开 * 全部
     */
    public static function rule(string $rule,string $to,string $method = '*'){
        $methods = explode(',',$method);
        foreach ($methods as $item){
            if (isset(self::$rule[$item])){
                self::$rule[$item][] = new RuleItem($rule,$to);
            }
        }
    }

    /**
     * 添加GET 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function get(string $rule,string $to){
        self::$rule['get'][] = new RuleItem($rule,$to);
    }

    /**
     * 添加POST 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function post(string $rule,string $to){
        self::$rule['post'][] = new RuleItem($rule,$to);
    }
    /**
     * 添加PUT 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function put(string $rule,string $to){
        self::$rule['put'][] = new RuleItem($rule,$to);
    }

    /**
     * 添加PATCH 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function patch(string $rule,string $to){
        self::$rule['patch'][] = new RuleItem($rule,$to);
    }
    /**
     * 添加DELETE 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function delete(string $rule,string $to){
        self::$rule['delete'][] = new RuleItem($rule,$to);
    }

    /**
     * 添加OPTIONS 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function options(string $rule,string $to){
        self::$rule['options'][] = new RuleItem($rule,$to);
    }

    /**
     * 添加HEAD 路由
     * @param string $rule      规则
     * @param string $to        路由到
     */
    public static function head(string $rule,string $to){
        self::$rule['head'][] = new RuleItem($rule,$to);
    }

    /**
     * 匹配路由
     * @param Request $request
     * @param string  $url
     * @param string  $method
     * @return Dispatch|null
     */
    public static function match(Request $request,string $url,string $method): ?Dispatch{
        $method = strtolower($method);
        $url = ltrim($url,'/');

        /**
         * @var $rule RuleItem[]
         */
        $rule = self::$rule[$method];
        if ($method != '*')
            $rule = array_merge(self::$rule['*'],$rule);

        foreach ($rule as $item){
            $dispatch = $item->check($request,$url);
            if ($dispatch){
                return $dispatch;
            }
        }
        throw new NotFountRouteException("Route not exits: {$url}");
    }
}