<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 9:57
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;



use Mango\Config;
use Mango\Exception\ClassNotFoundException;
use Mango\Exception\ParamException;
use \ReflectionClass;
use \Throwable;
class Dispatch{

    /**
     * 访问方法
     * @var string
     */
    public $action = 'index';

    /**
     * 访问控制器
     * @var string
     */
    public $controller = 'Index';

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
        if (count($urls) > 1) {
            $this->action = array_pop($urls);
        }
        if (count($urls) > 0) {
            $this->controller = implode("\\",$urls);
        }
        $this->namespace = Config::getInstance()->get('app.server.controller');
        $this->request = $request;
        $this->controller = $this->namespace . $this->controller;

    }

    /**
     * 判断控制器是否存在
     */
    protected function isController(){
        if (!class_exists($this->controller)){
            throw new ClassNotFoundException("Controller Not Found: " . $this->controller,404);
        }
    }

    /**
     * 执行调度
     * @throws Throwable
     */
    public function exec(){
        $this->request->action($this->action);
        $this->request->controller($this->controller);
        $this->controller()->hook();
        if ($this->middleware() === false){
            $this->action($this->action);
        }
    }

    /**
     * 执行控制器中间件
     * @return bool
     * @throws \ReflectionException
     */
    public function middleware(){
        $data = $this->getProperty('middleware');
        if (empty($data)){
            return false;
        }

        if (!is_array($data)){
            $data = [$data];
        }
        foreach ($data as $item){
            [$middleware,$action,$argv] = $this->parseMiddleware($item);
            if (in_array(strtolower($this->action),$action) || in_array('*',$action)) break;
        }

        if (!class_exists($middleware)){
            throw new ClassNotFoundException('Middleware not exits: ' . $middleware);
        }
        $object = new $middleware();
        call_user_func([$object,'handler'],$this->request,function (Request $request){
             return $this->action($this->action);
        },...$argv);
        return true;
    }

    protected function arrayValToLower(array $arr){
        $res = [];
        foreach ($arr as $val){
            $res[] = strtolower($val);
        }
        return $res;
    }

    /**
     * 解析中间件
     * @param $item
     * @return array
     */
    protected function parseMiddleware($item):array {
        if (is_array($item)){
            $middleware = $item['middleware'];
            $action = $item['action'] ?? [];
            $argv = $item['argv'] ?? [];
        }else if ($item instanceof \Closure){
            [$middleware,$action,$argv] = $item($this->request());
        }else{
            $middleware = $item;
            $action = ['*'];
            $argv = [];
        }
        if (is_string($action))
            $action = explode(',',$action);

        return [$middleware,$this->arrayValToLower($action),$argv];
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
    protected function hook(){
        try {
            call_user_func_array([$this->getControllerInstance(),'__hook'],[$this->action,$this->request(),$this->request()->response()]);
        }catch (Throwable $throwable){
            throw $throwable;
        }
        return $this;
    }

    /**
     *  调用控制器方法。开始处理请求
     * @param string $action
     * @return mixed|null
     * @throws Throwable
     */
    protected function action(string $action){
        try {
            $params = $this->getMethodParams($this->controller,$action);
            return call_user_func_array([$this->getControllerInstance(),$action],$params);
        }catch (Throwable $throwable){
            throw $throwable;
        }
    }

    /**
     * 实例化控制器
     * @return $this
     * @throws Throwable
     */
    protected function controller(){
        $this->isController();
        try {
            $this->controllerInstance = call_user_func_array([$this->controller,'__make'],[$this->request(),$this->request()->response()]);
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
     * 获得类的方法参数
     * @param ReflectionClass|string      $class
     * @param string $methodsName
     * @return array
     * @throws \ReflectionException
     */
    public function getMethodParams($class, $methodsName = '__construct') {

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

    /**
     * 获取控制器属性
     * @param string $name
     * @return mixed|null
     * @throws \ReflectionException
     */
    protected function getProperty(string $name){

        // 通过反射获取对象信息
        $ref = new ReflectionClass($this->getControllerInstance());
        if ($ref->hasProperty($name)){
            $refProperty = $ref->getProperty($name);
            if (!$refProperty->isPublic())
                $refProperty->setAccessible(true);
            return $refProperty->getValue($this->getControllerInstance());
        }
        return null;

    }
}