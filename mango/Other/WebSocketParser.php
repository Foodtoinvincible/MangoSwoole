<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/6 10:01
 * @Email: 30191306465@qq.com
 */

namespace Mango\Other;


use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;
use EasySwoole\Socket\Client\WebSocket;

/**
 * Class WebSocketParser
 *
 * 此类是自定义的 websocket 消息解析器
 * 此处使用的设计是使用 json string 作为消息格式
 * 当客户端消息到达服务端时，会调用 decode 方法进行消息解析
 * 会将 websocket 消息 转成具体的 Class -> Action 调用 并且将参数注入
 *
 * @package App\WebSocket
 */
class WebSocketParser implements ParserInterface {


    /**
     * decode
     * @param  string         $raw    客户端原始消息
     * @param  WebSocket      $client WebSocket Client 对象
     * @return Caller         Socket  调用对象
     */
    public function decode($raw, $client): ?Caller{


        // 解析 客户端原始消息
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            ServerManager::getInstance()->getSwooleServer()->push($client->getFd(),json_encode([
                'msg' => 'NON-JSON DATA！',
                'code' => -1
            ]));
            return null;
        }

        /**
         * 控制器是否存在
         */
        if (empty($data['controller'])){
            // 不存在赋值默认
            $data['controller'] = 'Index';
        }
        /**
         * 拿到控制器与执行方法
         */
        $controller = explode('/',$data['controller']);
        $action = count($controller) >= 2 ? $controller[count($controller)-1] : 'index';
        if (count($controller) >= 2) unset($controller[count($controller)-1]);
        $controller = implode('\\',$controller);


        // new 调用者对象
        $caller =  new Caller();
        /**
         * 设置被调用的类 这里会将ws消息中的 class 参数解析为具体想访问的控制器
         * 如果更喜欢 event 方式 可以自定义 event 和具体的类的 map 即可
         * 注 目前 easyswoole 3.0.4 版本及以下 不支持直接传递 class string 可以通过这种方式
         */
        $class = '\\App\\WebSockController\\'. ucfirst($controller ?? 'Index');
        $caller->setControllerClass($class);


        // 设置被调用的方法
        $caller->setAction($action);
        // 检查是否存在args
        if (!empty($data['args'])) {
            $args = is_array($data['args']) ? $data['args'] : ['args' => $data['args']];
        }
        // 设置被调用的Args
        $caller->setArgs($args ?? []);
        return $caller;
    }

    /**
     * 返回响应给客户端的信息
     * 这里应当只做统一的encode操作 具体的状态等应当由 Controller处理
     * @param Response $response
     * @param WebSocket $client
     * @return string|null
     */
    public function encode(Response $response, $client): ?string{
        $data = json_decode($client->getData(),true);
        $returnData = $response->getMessage();
        if (is_array($returnData) && empty($returnData['message_id']) && !empty($data['message_id'])){
            $returnData['message_id'] = $data['message_id'];
        }
        if (empty($returnData['code']))
            $returnData['code'] = 0;
        if (empty($returnData['msg']))
            $returnData['msg'] = 'ok';
        return json_encode($returnData);
    }
}