<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2020/4/9 16:27
 * @Email: 30191306465@qq.com
 */

namespace Mango\Component\process;

use Mango\Timer;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;
use Swoole\Coroutine\Scheduler;

abstract class AbstractProcess
{
    /**
     * @var Process
     */
    private $swooleProcess;

    /**
     * @var Config
     */
    private $config;

    protected $desc = '';

    /**
     * 创建一个进程
     * AbstractProcess constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->swooleProcess = new Process([$this,'__start'],$this->config->isRedirectStdinStdout(),$this->config->getPipeType(),$this->config->isEnableCoroutine());
    }

    public function exit(){
        Process::kill($this->getPid());
        $this->swooleProcess->close(0);
        $this->swooleProcess = null;
    }

    public function start(){
        return $this->swooleProcess->start();
    }

    /**
     * 获取进程
     * @return Process
     */
    public function getProcess():Process
    {
        return $this->swooleProcess;
    }

    /**
     * 添加定时器
     * @param $ms
     * @param callable $call
     * @return int|null
     */
    public function addTick($ms,callable $call):?int
    {
        return Timer::getInstance()->loop(
            $ms,$call
        );
    }

    /**
     * 清理定时器
     * @param int $timerId
     * @return int|null
     */
    public function clearTick(int $timerId):?int
    {
        return Timer::getInstance()->clear($timerId);
    }

    /**
     * 延迟执行定时器
     * @param $ms
     * @param callable $call
     * @return int|null
     */
    public function delay($ms,callable $call):?int
    {
        return Timer::getInstance()->after($ms,$call);
    }

    /*
     * 服务启动后才能获得到pid
     */
    public function getPid():?int
    {
        if(isset($this->swooleProcess->pid)){
            return $this->swooleProcess->pid;
        }else{
            return null;
        }
    }

    /**
     * 内部方法 启动进程
     * @param Process $process
     * @throws \Throwable
     */
    public function __start(Process $process)
    {
        /*
         * swoole自定义进程协程与非协程的兼容
         * 开一个协程，让进程推出的时候，执行清理reactor
         */
        Coroutine::create(function (){

        });
        if(PHP_OS != 'Darwin' && !empty($this->getProcessName())){
            $process->name($this->getProcessName());
        }
        swoole_event_add($this->swooleProcess->pipe, function(){
            try{
                $this->onPipeReadable($this->swooleProcess);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        });
        Process::signal(SIGTERM,function ()use($process){
            swoole_event_del($process->pipe);
            /*
             * 清除全部定时器
             */
            \Swoole\Timer::clearAll();
            Process::signal(SIGTERM, null);
            Event::exit();
        });
        register_shutdown_function(function () {
            $schedule = new Scheduler();
            $schedule->add(function (){
                try{
                    $this->onShutDown();
                }catch (\Throwable $throwable){
                    $this->onException($throwable);
                }
                \Swoole\Timer::clearAll();
            });
            $schedule->start();
        });
        try{
            $this->initialize();
            $this->run($this->config->getArg());
        }
        catch (\Throwable $throwable){
            $this->onException($throwable);
        }
    }

    /**
     * 获取参数
     * @return mixed
     */
    public function getArg()
    {
        return $this->config->getArg();
    }

    /**
     * 获取进程名称
     * @return mixed
     */
    public function getProcessName()
    {
        return $this->config->getProcessName();
    }

    /**
     * 获取配置
     * @return Config
     */
    protected function getConfig():Config
    {
        return $this->config;
    }

    /**
     * 当该进程出现异常的时候，会执行该回调
     * @param \Throwable $throwable
     * @param array|mixed ...$args
     * @throws \Throwable
     */
    protected function onException(\Throwable $throwable,...$args){
        throw $throwable;
    }

    /**
     * 当进程启动后，会执行的回调
     * @param $arg
     */
    protected abstract function run($arg);

    /**
     * 在run()方法前执行的方法
     */
    protected function initialize(){
    }

    /**
     * 当该进程出现异常的时候，会执行该回调
     */
    protected function onShutDown()
    {

    }

    /**
     * 该回调可选
     * 当有主进程对子进程发送消息的时候，会触发的回调，触发后，务必使用
     * $process->read()来读取消息
     * @param Process $process
     */
    protected function onPipeReadable(Process $process)
    {
        /*
         * 由于Swoole底层使用了epoll的LT模式，因此swoole_event_add添加的事件监听，
         * 在事件发生后回调函数中必须调用read方法读取socket中的数据，否则底层会持续触发事件回调。
         */
        $process->read();
    }
}