<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/26 22:41
 * @Email : 30191306465@qq.com
 */

namespace Mango\Websocket;


use Mango\Component\Singleton;
use Mango\Event;
use Mango\Exception\EventException;
use Mango\Http\Request;
use Mango\Http\Response;

class Server{

    use Singleton;

    /**
     * @var \Swoole\WebSocket\Server
     */
    private $socket;

    protected function __construct(string $host = '0.0.0.0',int $port = 9501){
        $this->socket = new \Swoole\WebSocket\Server($host,$port);

        $this->initialize();
    }

    protected function initialize(){

        $this->onHandshake();
        $this->onOpen();
        $this->onMessage();
        $this->onClose();
    }

    public function boot(){
        $this->socket->start();
    }

    /**
     * 握手事件
     */
    protected function onHandshake(){
        if (Event::has('onHandshake')){
            $this->socket->on('handshake',function (\Swoole\Http\Request $swooleRequest, \Swoole\Http\Response $swooleresponse){
                $request = new Request($swooleRequest,new Response($swooleresponse));
                $callback = Event::get('onHandshake');
                try {
                    return call_user_func($callback,$request,$request->response());
                }catch (\Error $error){
                    var_dump($error->getMessage());
                }
                catch (\Exception $exception){
                    var_dump($exception->getMessage());
                }
                if (!$request->response()->isOutput())
                    $request->response()->send();
                return false;
            });

        }
    }
    protected function onOpen(){
        if (!Event::has('onHandshake')){
            if (Event::has('onOpen')){

                $this->socket->on('open',function (\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request){
                    $callback = Event::get('onOpen');
                    try {
                        call_user_func($callback,$request);
                    }catch (\Error $error){
                        var_dump($error->getMessage());
                    }
                    catch (\Exception $exception){
                        var_dump($exception->getMessage());
                    }
                });
            }


        }
    }
    protected function onMessage(){
        if (!Event::has('onMessage')){
            throw new EventException('Websocket: onMessage event not exits');
        }
        $this->socket->on('message',function (\Swoole\WebSocket\Server $server,\Swoole\WebSocket\Frame $frame){
            $callback = Event::get('onMessage');
            try {
                call_user_func($callback,$server,$frame);
            }catch (\Error $error){
                var_dump($error->getMessage());
            }
            catch (\Exception $exception){
                var_dump($exception->getMessage());
            }
        });
    }

    protected function onClose(){
        if (Event::has('onClose')){
            $this->socket->on('close',function (\Swoole\WebSocket\Server $server,int $fd,int $reactorId){
                $callback = Event::get('onClose');
                try {
                    call_user_func($callback,$server,$fd,$reactorId);
                }catch (\Error $error){
                    var_dump($error->getMessage());
                }
                catch (\Exception $exception){
                    var_dump($exception->getMessage());
                }
            });
        }
    }
}