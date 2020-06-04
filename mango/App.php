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

/**
 * Class App
 * @package Mango
 * @mixin App
 * @property Config $config
 */
class App extends Container {
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
        'Config'    => Config::class,
    ];

    /**
     * 服务
     * @var Http
     */
    protected $service;


    /**
     * 创建App
     * App constructor.
     */
    protected function __construct(){
        $this->instance('app', $this);
        $this->instance('Mango\App', $this);
        $this->instance('Mango\Container', $this);
    }



    /**
     * 运行程序
     */
    public function run() : void{
        $this->invoke('MangoMain::initialize');

        swoole_set_process_name('Mango Manager');

        $this->startMemoryOccupySize = memory_get_usage();
        // 一键协程
        \Swoole\Runtime::enableCoroutine();
        $this->startTime = microtime(true) * 1000;
        $this->initialize();
        $this->event();
        $this->boot();
    }

    /**
     * 事件注册
     */
    protected function event(){

        $this->getServer()->bindEvent(EventRegister::Start,function (\Swoole\Server $server){
            swoole_set_process_name('Mango Master');
        });
        $this->getServer()->bindEvent(EventRegister::WorkerStart,function (\Swoole\Server $server, int $workerId){
            swoole_set_process_name("Worker {$workerId}");
            foreach ($this->bind as $k=>$item){
                $this->make($k,[]);
            }
        });
    }

    /**
     * 初始化
     */
    public function initialize(): void{
        $this->load();

        $type = Config::getInstance()->get('app.server.type');

        $this->invoke('MangoMain::main',[$type]);

        EventRegister::exec(EventRegister::ServerCreateBefore);
        switch ($type){
            case SERVER_TYPE_HTTP:
                $this->service = Http::getInstance();
                break;
        }
        EventRegister::exec(EventRegister::ServerCreateAfter,[$this->getServer()]);

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