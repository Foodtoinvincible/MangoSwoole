<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/12/4 13:46
 * @Email: 30191306465@qq.com
 */

namespace Mango\Pool;


use Mango\Exception\EmptyException;
use Swoole\Timer;

abstract class AbstractPool{


    /**
     * 池对象列表
     * @var array
     */
    protected $pool = [];

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 定时器id
     * @var null
     */
    protected $timerId = null;

    /**
     * 每次等待50毫秒
     * @var int
     */
    protected $eachWaitTime = 50;


    /**
     * 创建对象 只创建一个时会返回创建的对象
     * @param int $number
     * @return mixed|null
     */
    protected function news($number = 1){


        while ($number > 0){
            $this->pool[] = $this->create($this->config['connection']);
            if ($number == 1){
                return $this->pool[count($this->pool)-1];
            }
            --$number;
        }
        return null;
    }

    /**
     * 覆写该方法。实现创建池中对象逻辑
     * @param array $conf
     * @return mixed
     */
    abstract protected function create(array $conf):AbstractContext;

    /**
     * 获取对象
     * 每个协程只允许出现一个连接对象
     * 允许多个协程共用一个连接对象
     * 共用连接时，只需传递一样的协程id即可
     * @param int $cid 协程id
     * @return mixed|null
     * @throws EmptyException
     */
    public function get($cid = null){

        $conn = $this->cidGet($cid);
        if (!empty($conn)) return $conn;

        $waitFrequency = ceil($this->config['max_wait_time'] / $this->eachWaitTime);
        while ($waitFrequency > 0){

            $conn = $this->pop();

            if ($conn != null){
                return $conn;
            }
            \co::sleep($this->eachWaitTime / 1000);
            --$waitFrequency;
        }
        throw new EmptyException('Pool empty');
    }


    /**
     * 从池中取出一个空闲对象
     * @return mixed
     */
    protected function pop(){


        /**
         * 如果池内有空对象则分配给当前协程
         * @var $val AbstractContext
         */
        foreach ($this->pool as &$val){

            // 未被使用并且未标记为销毁
            if (!$val->getStatus() && !$val->isWhether()){
                // 注册一个回收方法
                defer(function () use (&$val){
                    $val->recover();
                });
                // 绑定协程id 并返回 PDO 对象
                return $val->get(\Swoole\Coroutine::getuid());
            }
        }
        /**
         * 如果池中无空闲对象并且数量没超过最大限制则创建一个
         */
        if (count($this->pool) < $this->config['max_object_number']){
            // 创建一个PDO对象
            $object = $this->news();
            // 注册一个回收方法
            defer(function () use (&$object){
                $object->recover();
            });
            return $object->get(\Swoole\Coroutine::getuid());
        }
        return null;
    }


    /**
     * 根据协程id获取对象，cid不存在，则拿去当前协程id
     * @param int $cid 协程id
     * @return mixed|null
     */
    protected function cidGet($cid = null){

        $cid = $cid ?? \Swoole\Coroutine::getuid();

        foreach ($this->pool as $item){
            // 如果池对象中包含已绑定的当前协程id 并且是在使用 则返回这个连接
            if ($item->getBindCoroutineId() === $cid && $item->getStatus() && !$item->isWhether()){
                return $item->get();
            }
        }
        return null;
    }

    /**
     * 使用定时器定期释放创建对象
     */
    public function regularCheckTimer(): void{

        $this->timerId = Timer::tick($this->config['interval_check_time'],function (){


            if ($this->config['min_object_number'] > count($this->pool)){
                $this->news($this->config['min_object_number'] - count($this->pool));
            }
            /**
             * @var $item AbstractContext
             */
            foreach ($this->pool as $k=>$item){

                // 标记为销毁并且已无任何协程使用才会销毁
                if ($item->isWhether() && !$item->getStatus()){
                    $item->close();
                    unset($this->pool[$k]);
                }
                // 无协程使用并且距离上次使用时间 时间大于存活时长 标记销毁
                if (!$item->getStatus() && time()-$item->getLastUseTime() > $this->config['max_idle_time']){
                    $item->whether();
                }
            }
            $this->pool = array_merge($this->pool,[]);
        });


    }


    public function close() : void{
        self::$instance = null;
        $this->config = null;
        $this->eachWaitTime = null;
        $this->destruct();
    }

    public function __destruct(){
        $this->destruct();
    }

    protected function destruct(): void{
        if ($this->timerId != null)
            Timer::clear($this->timerId);
        foreach ($this->pool as $item)
            $item->close();
        $this->pool = null;
    }
}