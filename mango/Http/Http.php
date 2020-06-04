<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 8:58
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;



use Mango\Component\Singleton;
use Mango\Config;
use Mango\EventRegister;
use Mango\Facade\Route;
use Mango\Socket\ServerInterface;
use Swoole\Http\Server;

/**
 * Class Http
 * @package Mango\Http
 */
class Http implements ServerInterface {

    use Singleton;

    /**
     * @var Server
     */
    private $http;


    /**
     * 异常处理类
     * @var string
     */
    protected $error;

    protected function __construct(string $host = '0.0.0.0', int $port = 8080){
        $this->http = new Server($host,$port);

        $this->initialize();
        $this->event();
    }

    protected function initialize(){
        $this->load();
    }

    /**
     * 加载
     */
    protected function load(){
        if (is_file(APP_PATH . 'route.php')){
            // 加载路由
            include_once APP_PATH . 'route.php';
        }
        $this->error = Config::getInstance()->get('app.http.exception');
        if (empty($this->error))
            $this->error = Error::class;
    }

    /**
     * 启动
     */
    public function boot(){
        $this->http->start();
    }

    /**
     * 事件注册
     */
    protected function event(){

        $event = EventRegister::get(EventRegister::Request,function (\Swoole\Http\Request $swooleRequest, \Swoole\Http\Response $swooleresponse){
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
                call_user_func([$this->error,'error'],$error,$request);
            } catch (\Exception $exception){
                call_user_func([$this->error,'exception'],$exception,$request);
            }
        });

        // 请求事件
        $this->bindEvent(EventRegister::Request,$event);
    }

    /**
     * 获取 Swoole 对象
     * @return Server
     */
    public function getSwooleServer(): Server{
        return $this->http;
    }

    /**
     * 绑定事件
     * @param string   $name
     * @param callable $callback
     * @return mixed|void
     */
    public function bindEvent(string $name, callable $callback){
        $this->http->on($name,$callback);
    }
}