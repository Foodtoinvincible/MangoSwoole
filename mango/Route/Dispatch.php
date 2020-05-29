<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 9:57
 * @Email : 30191306465@qq.com
 */

namespace Mango\Route;



use Mango\Config;
use Mango\Exception\ClassNotFoundException;
use Mango\Exception\FuncNotFoundException;
use Mango\Exception\MethodException;
use Mango\Exception\ParamException;
use Mango\Http\Controller;
use Mango\Http\Request;
use Mango\Http\Response;
use \ReflectionClass;
use \Throwable;
class Dispatch{

    /**
     * 访问方法
     * @var string
     */
    protected $action = 'index';

    /**
     * 访问控制器
     * @var string
     */
    protected $controller = 'Index';

    /**
     * 控制器实例
     * @var Controller
     */
    protected $controllerInstance;

    /**
     * 控制器命名空间
     * @var string
     */
    protected $namespace = "\\App\\HttpController\\";

    /**
     * 请求对象
     * @var Request
     */
    private $request;

    /**
     * 构造调度
     * Dispatch constructor.
     * @param Request $request  请求对象
     * @param string  $url      执行url
     * @param array   $vars     参数
     */
    public function __construct(Request $request,string $url,array $vars){
        $urls = explode('/',$url);
        $this->namespace = Config::getInstance()->get('app.server.controller');
        if (count($urls) > 1) {
            $this->action = array_pop($urls);
        }
        if (count($urls) > 0) {
            $this->controller = implode("\\",$urls);
        }
        $this->request = $request;
    }

    /**
     * 获取控制器完整命名空间
     * @return string
     */
    public function getFullControllerNamespace(){
        return $this->namespace . $this->controller;
    }

    /**
     * 判断控制器是否存在
     */
    protected function isController(){
        if (!class_exists($this->getFullControllerNamespace())){
            throw new ClassNotFoundException("Controller Not Found: " . $this->getFullControllerNamespace(),404);
        }
    }

    /**
     * 执行
     * @throws Throwable
     */
    public function exec(){
        $this->newController()->callHook()->callAction();
    }

    /**
     * 获取的请求对象
     * @return Request
     */
    protected function request() : Request{
        return $this->request;
    }

    /**
     * 调用当前控制器 __Hook 方法
     * @return $this
     * @throws Throwable
     */
    protected function callHook(){
        try {
            call_user_func_array([$this->getControllerInstance(),'__hook'],[$this->action(),$this->request(),$this->request()->response()]);
        }catch (Throwable $throwable){
            throw $throwable;
        }
        return $this;
    }

    /**
     * 调用控制器方法。开始处理请求
     * @param string $action
     * @throws Throwable
     */
    protected function callAction(string $action = ''){
        $action = $action ?: $this->action();
        try {
            $params = $this->getMethodParams($this->getFullControllerNamespace(),$action);
            call_user_func_array([$this->getControllerInstance(),$action],$params);
        }catch (Throwable $throwable){
            throw $throwable;
        }
    }

    /**
     * 实例化控制器
     * @return $this
     * @throws Throwable
     */
    protected function newController(){
        $this->isController();
        try {
            $this->controllerInstance = call_user_func_array([$this->getFullControllerNamespace(),'__make'],[$this->request(),$this->request()->response()]);
        } catch (Throwable $e) {
            throw $e;
        }
        return $this;
    }

    /**
     * 获取控制器实例
     * @return Controller
     */
    public function getControllerInstance(): Controller{
        return $this->controllerInstance;
    }

    /**
     * 获取访问方法
     * @return string
     */
    public function action():string{
        return $this->action;
    }

    /**
     * 获取访问控制器
     * @return string
     */
    public function controller(): string{
        return $this->controller;
    }

    /**
     * 获得类的方法参数
     * @param ReflectionClass|string      $class
     * @param string $methodsName
     * @return array
     * @throws \ReflectionException
     */
    protected function getMethodParams($class, $methodsName = '__construct') {

        $paramArr = []; // 记录参数，和参数类型

        if (!($class instanceof ReflectionClass))
            $class = new ReflectionClass($class);

        // 判断该类是否有指定的方法
        if ($class->hasMethod($methodsName)) {
            // 获得方法
            $method = $class->getMethod($methodsName);

            // 获得方法参数数组
            $params = $method->getParameters();

            if (count($params) > 0) {

                // 判断参数类型
                foreach ($params as $key => $param) {

                    if ($paramClass = $param->getClass()) {
                        switch ($paramClassName = $paramClass->getName()){
                            case Request::class:
                                $paramArr[] = $this->request();
                                break;
                            case Response::class:
                                $paramArr[] = $this->request()->response();
                                break;
                            default:
                                // 获得参数类型
                                $args = $this->getMethodParams($paramClassName);
                                $paramArr[] = (new ReflectionClass($paramClass->getName()))->newInstanceArgs($args);
                                break;
                        }
                        continue;
                    }

                    $paramName = $param->getName();
                    if ($has = $this->request()->hasParam($paramName)){
                        $data = $this->request()->param($paramName);
                        if($param->hasType()){
                            switch (true){
                                case $param->getType()->getName() == 'string':
                                    $data = (string) $data;
                                    break;
                                case $param->getType()->getName() == 'double':
                                    $data = (double) $data;
                                    break;
                                case $param->getType()->getName() == 'float':
                                    $data = (float) $data;
                                    break;
                                case $param->getType()->getName() == 'int':
                                    $data = (int) $data;
                                    break;
                                case $param->getType()->getName() == 'bool':
                                    $data = (bool) $data;
                                    break;
//                            case $param->getType()->getName() == 'array':
//                                $data = $this->parseParam($data);
//                                if (is_null($data)){
//                                    throw new ParamException("{$paramName} method parameter [{$key}] An array is expected, but the passed parameter values cannot be parsed as an array");
//                                }
//                                break;
                            }
                        }
                        $paramArr[] = $data;
                    }else{
                        if ($param->isDefaultValueAvailable()){
                            $paramArr[] = $param->getDefaultValue();
                        }else{
                            // 参数不存在
                            throw new ParamException("Parameter not exist: {$paramName}");
                        }
                    }
                }
            }
        }

        return $paramArr;
    }
}