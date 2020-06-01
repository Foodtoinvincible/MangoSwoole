<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/24 17:24
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;


use Mango\Exception\MethodException;

abstract class Controller{

    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    /**
     * 中间件
     * @var array
     */
    protected $middleware = [];

    protected $disable = [
        '__hook','__construct','__set','__get','__make',
        '__clone','__call','__debugInfo','__invoke','__destruct',
        '__serialize','__isset','__sleep','__toString','__unset',
        '__wakeup','__invoke'
    ];

    public function __construct(Request $request,Response $response){
        $this->response = $response;
        $this->request = $request;
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize(){}

    /**
     * 创建控制器
     * @param Request  $request
     * @param Response $response
     * @return static
     */
    public static function __make(Request $request,Response $response){
        return new static($request,$response);
    }

    /**
     * 获取响应对象
     * @return Response
     */
    protected function response(): Response{
        return $this->response;
    }

    /**
     * 获取请求对象
     * @return Request
     */
    protected function request(): Request{
        return $this->request;
    }

    /**
     * 钩子
     * @param string   $action
     * @param Request  $request
     * @param Response $response
     */
    public function __hook(string $action,Request $request,Response $response) {
        if (in_array(strtolower($action),$this->disable)){
            throw new MethodException("method {$action}: Illegal call");
        }
        // TODO ...

    }

    private function middleware(){

    }
}