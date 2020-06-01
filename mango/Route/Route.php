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


    /**
     * @var RuleGroup
     */
    protected $group;

    public function __construct(){
        $this->group = new RuleGroup($this);
    }

    /**
     * 设置当前分组
     * @access public
     * @param RuleGroup $group 域名
     * @return void
     */
    public function setGroup(RuleGroup $group): void
    {
        $this->group = $group;
    }

    /**
     * 获取当前分组
     * @return RuleGroup
     */
    public function getGroup(): RuleGroup{
       return $this->group;
    }

    /**
     * 添加路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     * @param string $method    请求方法 |隔开 * 全部
     * @return RuleItem
     */
    public function rule(string $name,string $route,string $method = '*'){
        return $this->group->addRule($name, $route, $method);
    }

    /**
     * 添加GET 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function get(string $name,string $route){
        $this->rule($name,$route,'GET');
    }

    /**
     * 添加POST 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function post(string $name,$route){
        $this->rule($name,$route,'POST');
    }
    /**
     * 添加PUT 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function put(string $name,$route){
        $this->rule($name,$route,'PUT');
    }

    /**
     * 添加PATCH 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function patch(string $name,$route){
        $this->rule($name,$route,'PATCH');
    }
    /**
     * 添加DELETE 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function delete(string $name,$route){
        $this->rule($name,$route,'DELETE');
    }

    /**
     * 添加OPTIONS 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function options(string $name,$route){
        $this->rule($name,$route,'OPTIONS');
    }

    /**
     * 添加HEAD 路由
     * @param string $name      路由名称
     * @param mixed  $route     路由地址
     */
    public function head(string $name,$route){
        $this->rule($name,$route,'HEAD');
    }

    /**
     * 路由分组
     * @param mixed $name
     * @param null $route
     * @return RuleGroup
     */
    public function group($name,$route = null){
        if ($name instanceof \Closure){
            $route = $name;
            $name = '';
        }
        $group = new RuleGroup($this,$this->group,$name,$route);
        return $group->parseRule();
    }

    /**
     * 匹配路由
     * @param string $url       请求地址
     * @param string $method    请求类型
     * @return array|void       返回数组 [0 => 路由变量[],1 => RuleItem]
     */
    public function match(string $url,string $method): array {
        $method = strtolower($method);
        $url = ltrim($url,'/');

        $result = $this->group->getTop()->check($url,$method);
        if ($result)
            return $result;
        throw new NotFountRouteException("Route not exits: {$url}");
    }
}