<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 10:18
 * @Email : 30191306465@qq.com
 */

namespace Mango\Route;


use Closure;
class RuleItem extends Rule {

    /**
     * 路由名称
     * @var string
     */
    private $name;

    /**
     * @var Route
     */
    protected $router;
    /**
     * @var RuleGroup
     */
    protected $group;

    /**
     * 路由地址
     * @var string|Closure
     */
    protected $route;

    /**
     * 架构函数
     * @access public
     * @param  Route             $router 路由实例
     * @param  RuleGroup         $parent 上级对象
     * @param  string            $name 路由标识
     * @param  string|\Closure   $route 路由地址
     * @param  string            $method 请求类型
     */
    public function __construct(Route $router,RuleGroup $parent,string $name = null, $route = null,string $method = '*'){
        $this->name = $name;
        $this->router = $router;
        $this->group = $parent;
        $this->route = $this->parseRoute($route);
        $this->setMethod($method);
    }


    /**
     * 解析路由地址
     * @param string|Closure $route 路由地址
     * @return string|Closure
     */
    protected function parseRoute($route){

        if (!is_string($route)) return $route;

        $routes = explode('/',$route);

        foreach ($routes as &$item){
            if (strpos($item,':') === 0){
                $item = '<' . substr($item,1) . '>';
            }else if(preg_match('/\[(\w+)\]/',$item,$matches)){
                $item = '[' . $matches[1] . ']';
            }
        }
        return implode('/',$routes);
    }

    /**
     * 判断请求类型是否存在
     * @param string $method
     * @return bool
     */
    public function hasMethod(string $method): bool {
        $array = explode('|',$this->getOptions('method'));
        return in_array(strtolower($method),$array) || in_array('*',$array);
    }


    /**
     * 检测路由（含路由匹配）
     * @param string $url       请求地址
     * @param string $method    请求类型
     * @return array|bool       正确返回数组 [0 => 路由变量[],1 => RuleItem]
     */
    public function check(string $url,string $method){

        if ($this->hasMethod($method)){
            $result = $this->checkRule($url);
            if ($result !== false){
                return [$result,$this];
            }
        }


        return false;
    }


    /**
     * 检测路由
     * @param string  $url
     * @return array|bool
     */
    protected function checkRule(string $url) {
        return $this->match($url,$this->getFullName());
    }

    /**
     * 路由匹配
     * @param string $url
     * @param string $rule
     * @return array|bool
     */
    protected function match(string $url, string $rule){
        $vars = [];
        $urls = explode('/',$url);
        $rules = explode('/',$rule);

        foreach ($rules as $k=>$item){

            if (strpos($item,'<') !== false){
                if (!isset($urls[$k]))
                    return false;
                $vars[substr($item,1,-1)] = $urls[$k];
                unset($rules[$k]);
                unset($urls[$k]);
            }elseif (strpos($item,'[') !== false) {
                if (isset($urls[$k])){
                    $vars[substr($item,1,-1)] = $urls[$k];
                    unset($rules[$k]);
                    unset($urls[$k]);
                }
                continue;
            }else{
                if (!isset($urls[$k])) return false;
                if (strcasecmp($item,$urls[$k]) !== 0){
                    return false;
                }
                unset($rules[$k]);
                unset($urls[$k]);
            }
        }

        foreach ($rules as $k=>$item){
            if (strpos($item,'[') !== false)
                unset($rules[$k]);
        }
        if (count($urls) != count($rules)){
            return false;
        }

        return $vars;
    }

    /**
     * 获取完整的路由名称
     * @return string
     */
    public function getFullName(): string{
        $name = [];
        $group = $this->group;
        do{
            if($group->getName())
                $name[] = $group->getName();
        }while($group = $group->parent());
        $name = implode('/',array_reverse($name)). '/' . $this->name;
        return trim($name,'/');
    }

    /**
     * 获取路由地址
     * @return Closure|string
     */
    public function getRoute(){
        // 检查前置
        return $this->route;
    }
}