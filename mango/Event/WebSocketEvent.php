<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/1 14:16
 * @Email: 30191306465@qq.com
 */

namespace Mango\Event;


use Mango\Cache\Redis\RedisPool;
use Mango\Config;
use Mango\Other\WebSocketParser;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Socket\Dispatcher;

class WebSocketEvent{


    /**
     * @var Dispatcher
     */
    protected static $dispatch;

    /**
     * 注册事件并执行
     * @param EventRegister $register
     * @throws \EasySwoole\Socket\Exception\Exception
     */
    public final static function listener(EventRegister $register){


        // 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $conf->setParser(new WebSocketParser());
        /**
         * 一定要注册一个异常处理
         * 否则控制器出现异常程序退出
         */
        $conf->setOnExceptionHandler([Config::getInstance()->get('exception.webSocketController'),'handel']);
        // 创建 Dispatcher 对象 并注入 config 对象
        self::$dispatch = new Dispatcher($conf);

        $websocket = new static();
        /**
         * 自定义握手
         */
        $register->set($register::onHandShake,function (\swoole_http_request $request, \swoole_http_response $response) use($websocket){
            $websocket->onHandShake($request,$response);
        });

        /**
         * 注册消息监听事件
         */
        $register->set($register::onMessage,function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($websocket){
            $websocket->onMessage($server,$frame);
        });

        /**
         * 注册关闭事件监听
         */
        $register->set($register::onClose,function (\swoole_websocket_server $server, int $fd,int $reactorId) use ($websocket){
            $websocket->onClose($server,$fd,$reactorId);
        });

    }

    /**
     * 握手事件
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     * @return bool
     */
    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
    {
        /** 此处自定义握手规则 返回 false 时中止握手 */
        if (!$this->customHandShake($request, $response)) {
            $response->end();
            return false;
        }

        /** 此处是  RFC规范中的WebSocket握手验证过程 必须执行 否则无法正确握手 */
        if ($this->secWebsocketAccept($request, $response)) {
            $response->end();
            // 自定义握手支持onOpen事件
            $this->onOpen(ServerManager::getInstance()->getSwooleServer(),$request->fd);
            return true;
        }

        $response->end();
        return false;
    }

    /**
     * 连接打开时触发事件
     * @param \swoole_websocket_server $server
     * @param int $fd
     */
    protected function onOpen(\swoole_websocket_server $server,int $fd){
        $server->push($fd,$fd);
    }

    /**
     * 自定义握手事件
     *
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     * @return bool
     */
    protected function customHandShake(\swoole_http_request $request, \swoole_http_response $response): bool{

        if (empty($request->get['token']) || $request->get['token'] != '123456')
            return false;

        return true;
    }

    /**
     * RFC规范中的WebSocket握手验证过程
     * 以下内容必须强制使用
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     * @return bool
     */
    protected function secWebsocketAccept(\swoole_http_request $request, \swoole_http_response $response): bool
    {

        if (!isset($request->header['sec-websocket-key'])) {
            return false;
        }
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        ){
            return false;
        }

        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $headers = array(
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        );

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        // 发送验证后的header
        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        // 接受握手 还需要101状态码以切换状态
        $response->status(101);
        return true;
    }


    /**
     * 消息事件处理
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * @throws \Exception
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame){

        self::$dispatch->dispatch($server,$frame->data, $frame);
    }

    /**
     * 关闭事件处理
     * @param \swoole_websocket_server $server 服务对象
     * @param int $fd 连接的文件描述符
     * @param int $reactorId 来自那个reactor线程，主动关闭时为负数（-1）
     * @throws \Mango\Exception\EmptyException
     */
    public function onClose(\swoole_websocket_server $server, int $fd,int $reactorId){
        RedisPool::getInstance()->get()->sRem('room',$fd);
    }
}