<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/8 15:24
 * @Email: 30191306465@qq.com
 */

namespace Mango;


use Mango\Db\Db;
use Mango\Http\Http;
use Mango\Websocket\Server as WebsocketServer;

/**
 * Class App
 * @package Mango
 * @mixin App
 * @property Config $config
 */
class App extends Container {


    protected function __construct(){
        $this->instance('app', $this);
        $this->instance('Mango\Container', $this);
    }

    /**
     * 程序开始运行时间(ms)
     * @var int
     */
    private $startTime = 0;


    /**
     * 开始内存占用
     * @var int
     */
    private $startMemoryOccupySize = 0;

    /**
     * 是否为调试模式
     * @var bool
     */
    private static $isDebug = true;

    /**
     * 绑定闭包
     * @var string[]
     */
    protected $bind = [
        'Db'        => Db::class,
        'Config'    => Config::class
    ];

    /**
     * 服务
     * @var Http
     */
    protected $service;


    /**
     * 运行程序
     */
    public function run() : void{
        $this->startMemoryOccupySize = memory_get_usage();
        // 一键协程
        \Swoole\Runtime::enableCoroutine();
        $this->startTime = microtime(true) * 1000;
        $this->initialize();
        $this->boot();
    }

    /**
     * 初始化
     */
    public function initialize(): void{
        // 注册异常
//        Error::register();
        // 加载文件
        $this->load();

        switch (Config::getInstance()->get('app.server.type')){
            case SERVER_TYPE_HTTP:
                $this->service = Http::getInstance();
                break;
            case SERVER_TYPE_WEBSOCKET:
                $this->service = WebsocketServer::getInstance();
                break;
        }
        foreach ($this->bind as $k=>$item){
            $this->make($k,[]);
        }
    }

    /**
     * 程序启动
     */
    protected function boot(): void{
        $this->service->boot();
    }

    protected function load(): void{
        Config::getInstance()->load(CONF_FILE);
    }

    /**
     * 获取服务
     * @return Http
     */
    public function getServer(){
        return $this->service;
    }

    /**
     * 是否为调试模式
     * @return bool
     */
    public static function isDebug(): bool{
        return self::$isDebug;
    }


    /**
     * 获取程序运行开始时间
     * @return int
     */
    public function getBeginTime(): int{
        return $this->startTime;
    }

    /**
     * 获取 run 方法执行前内存占用大小
     * @return int byte
     */
    public function getRunBeforeMemoryOccupySize(): int{
        return $this->startMemoryOccupySize;
    }
}