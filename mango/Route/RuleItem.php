<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 10:18
 * @Email : 30191306465@qq.com
 */

namespace Mango\Route;


use Mango\Http\Request;

class RuleItem{

    /**
     * 到
     * @var string
     */
    private $to;

    /**
     * 规则
     * @var string
     */
    private $rule;

    /**
     * RuleItem constructor.
     * @param string $rule 路由规则
     * @param string $to 执行至那里
     */
    public function __construct(string $rule,string $to){
        $this->rule = $this->parseRule($rule);
        $this->to = $to;
    }


    /**
     * 解析路由规则
     * @param string $rule 路由规则
     * @return string
     */
    protected function parseRule(string $rule): string{

        $rules = explode('/',$rule);

        foreach ($rules as &$item){
            if (strpos($item,':') === 0){
                $item = '<' . substr($item,1) . '>';
            }else if(preg_match('/\[(\w+)\]/',$item,$matches)){
                $item = '[' . $matches[1] . ']';
            }
        }
        return implode('/',$rules);
    }


    /**
     * 检测路由（含路由匹配）
     * @access public
     * @param  Request      $request  请求对象
     * @param  string       $url      访问地址
     * @return Dispatch|bool
     */
    public function check(Request $request,string $url){
        $result = $this->checkRule($request,$url);
        if ($result !== false){
            return new Dispatch($request,$this->getTo(),$result);
        }
        return false;
    }


    /**
     * 检测路由
     * @param Request $request
     * @param string  $url
     * @return array|bool
     */
    protected function checkRule(Request $request,string $url) {
        return $this->match($request,$url,$this->rule);
    }

    protected function match(Request $request,string $url, string $rule){
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
     * @return string
     */
    public function getTo(): string{

        return $this->to;
    }

}