<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 8:58
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;



use Mango\Component\Singleton;
use Mango\Facade\Route;
use Swoole\Http\Server;

/**
 * Class Http
 * @package Mango\Http
 * @mixin Server
 */
class Http{

    use Singleton;

    /**
     * @var Server
     */
    private $http;

    protected function __construct(string $host = '0.0.0.0', int $port = 8080){
        $this->http = new Server($host,$port);

        $this->initialize();
    }

    protected function initialize(){
        $this->load();

        $this->onRequest();
    }

    /**
     * 加载
     */
    protected function load(){
        if (is_file(APP_PATH . 'route.php')){
            // 加载路由
            include_once APP_PATH . 'route.php';
        }
    }

    /**
     * 启动
     */
    public function boot(){
        $this->http->start();
    }

    protected function onRequest(){

        $this->http->on('request',function (\Swoole\Http\Request $swooleRequest, \Swoole\Http\Response $swooleresponse){
            if ($swooleRequest->server['path_info'] == '/favicon.ico' || $swooleRequest->server['request_uri'] == '/favicon.ico') {
                $swooleresponse->end();
                return;
            }
            try {
                $request = new Request($swooleRequest,new Response($swooleresponse));
                [$vars,$rule] = Route::match($request->pathInfo(),$request->method());
                $dispatch = (new Dispatch($request,$rule->getRoute(),$vars));
                $dispatch->exec();
                if (!$request->response()->isOutput())
                    $request->response()->send();
            }catch (\Error $error){
                Error::error($error,$request);
            } catch (\Exception $exception){
                Error::exception($exception,$request);
            }
        });
    }

    /**
     * 使用 __call 实现调用Swoole\Http\Server对象
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->http,$name],$arguments);
    }

    /**
     * 获取 Swoole\Http\Server 对象
     * @return Server
     */
    public function getSwooleHttpServer(): Server{
        return $this->http;
    }
}