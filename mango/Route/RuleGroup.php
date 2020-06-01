<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/31 15:46
 * @Email : 30191306465@qq.com
 */

namespace Mango\Route;

use Closure;
/**
 * 路由分组
 * Class RuleGroup
 * @package Mango\Route
 */
class RuleGroup extends Rule{

    /**
     * 路由
     * @var array
     */
    protected $rules = [
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
     * @var RuleGroup
     */
    protected $parent = null;

    /**
     * @var Closure|null
     */
    protected $rule;

    protected $name;

    protected $router;

    /**
     *
     * RuleGroup constructor.
     * @param Route     $router 路由对象
     * @param RuleGroup $parent 上级对象
     * @param string    $name   分组名称
     * @param Closure  $rule   分组路由
     */
    public function __construct(Route $router,RuleGroup $parent = null, string $name = '',Closure $rule = null){
        $this->router = $router;
        $this->parent   = $parent;
        $this->rule     = $rule;
        $this->name     = trim($name, '/');
        if ($this->parent)
            $this->parent->addRuleItem($this);
    }

    /**
     * 解析分组路由
     * @return $this
     */
    public function parseRule(){
        if ($this->rule){
            $origin = $this->router->getGroup();
            $this->router->setGroup($this);
            $rule = $this->rule;
            $rule();
            $this->rule = null;
            $this->router->setGroup($origin);
        }
        return $this;
    }

    /**
     * 添加分组下的路由规则
     * @param  string $name   路由名称
     * @param  mixed  $route  路由地址
     * @param  string $method 请求类型
     * @return RuleItem
     */
    public function addRule(string $name, $route = null, string $method = '*'): RuleItem
    {

        $method = strtolower($method);

        // 创建路由规则实例
        $ruleItem = new RuleItem($this->router, $this, $name, $route, $method);

        $this->addRuleItem($ruleItem, $method);

        return $ruleItem;
    }

    /**
     * 注册分组下的路由规则
     * @param  Rule   $rule   路由规则
     * @param  string $method 请求类型
     * @return $this
     */
    public function addRuleItem(Rule $rule, string $method = '*')
    {
        $rule->setMethod($method);

        $this->rules[$method][] = $rule;

        return $this;
    }

    /**
     * 获取名称
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * 获取上层 组对象
     * @return RuleGroup|null
     */
    public function parent(): ?RuleGroup{
        return $this->parent;
    }

    /**
     * 监测路由
     * @param string $url           请求地址
     * @param string $method        请求类型
     * @return array|bool           正确返回数组 [0 => 路由变量[],1 => RuleItem]
     */
    public function check(string $url,string $method){
        $method = strtolower($method);
        if (!isset($this->rules[$method]))
            return false;

        $rules = $this->rules[$method];
        if ($method != '*' && !empty($this->rules['*']))
            array_push($rules,...$this->rules['*']);
        foreach ($rules as $item){
            if ($item instanceof RuleGroup){
                $result = $item->check($url,$method);
            } elseif ($item instanceof RuleItem){
                $result = $item->check($url,$method);
            }else{
                throw new \InvalidArgumentException('unknown route rule type: ' . gettype($item));
            }
            if ($result !== false){
                break;
            }
        }
        return empty($result) ? false : $result;
    }

    /**
     * 获取顶层路由分组对象
     * @return RuleGroup
     */
    public function getTop(): RuleGroup{

        $group = $this->parent;

        while ($group){
            if ($group->parent()){
                $group = $group->parent();
            }else{
                break;
            }
        }
        return $group ?? $this;
    }
}