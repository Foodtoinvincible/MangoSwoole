<?php

namespace Mango\Component\concerns;

use Mango\Timer;
use RuntimeException;
use Mango\coroutine\Context;
use Swoole\Coroutine\Channel;

/**
 * 连接池
 * Trait Pool
 * @package Mango\Component\concerns
 */
trait Pool{



    /** @var Channel[] */
    protected $pools = [];

    /**
     * 连接池对象数量
     * @var array
     */
    protected $connectionCount = [];

    /**
     * 最小数量
     * @var array
     */
    protected $minCount = [];

    /**
     * 最大数量
     * @var array
     */
    protected $maxCount = [];

    /**
     * 连接对象最大空闲时间 (s)
     * @var array
     */
    protected $spareTime = [];

    /**
     * 定时器间隔时长 (msg)
     * @var array
     */
    protected $timerMs = [];

    /**
     * 最大等待时间
     * @var array
     */
    protected $maxWaitTime = [];

    /**
     * 定时器id
     * @var array
     */
    protected $timeId = [];

    /**
     * 获取连接池
     * @param string $name   标识
     * @return Channel|null
     */
    protected function getPool(string $name):?Channel
    {
        if (empty($this->pools[$name])) {
            return $this->createPool($name);
        }
        return $this->pools[$name];
    }

    /**
     * 判断某个池是否存在
     * @param string $name
     * @return bool
     */
    protected function hasPool(string $name): bool {
        return isset($this->pools[$name]);
    }

    /**
     * 创建连接池
     * @param string $name 标识
     * @return Channel|null
     */
    public function createPool(string $name): ?Channel{

        if ($this->hasPool($name)) return null;
        $conf = $this->getPoolConfig($name);
        $this->pools[$name] = new Channel($conf['max_count']);
        // 最大等待时间
        $this->maxWaitTime[$name]   = $conf['max_wait_time'];
        // 最大数量
        $this->maxCount[$name]      = $conf['max_count'];
        // 最小数量
        $this->minCount[$name]      = $conf['min_count'];
        // 定时器时长
        $this->timerMs[$name]       = $conf['timer_ms'];
        // 对象空闲时间
        $this->spareTime[$name]     = $conf['spare_time'];

        $this->connectionCount[$name] = 0;
        // 启动定时器
        $this->poolTimer($name);
        return $this->pools[$name];
    }

    /**
     * 连接池定时器
     * @param string $name
     */
    private function poolTimer(string $name): void{
        if ($this->timeId[$name])
            Timer::getInstance()->clear($this->timeId[$name]);

        $this->timeId[$name] = Timer::getInstance()->loop($this->timerMs[$name],function () use ($name){
            $pool = $this->getPool($name);
            // 请求过多。暂不回收
            if ($pool->length() < intval($this->minCount[$name] * 0.5)) {
                return;
            }
            $list = [];
            while (true) {
                if (!$pool->isEmpty()) {
                    $obj = $pool->pop(0.001);
                    $lastUseTime = $obj['last_use_time'];
                    if ($this->connectionCount[$name] > $this->minCount[$name] && (time() - $lastUseTime > $this->spareTime[$name])) {
                        // 回收
                        $this->connectionCount[$name]--;
                        $this->poolMemberDestroy($obj['connection'],$name);
                        unset($obj);
                    } else {
                        array_push($list, $obj);
                    }
                } else {
                    break;
                }
            }
            // 返还
            foreach ($list as $item) {
                $this->pools[$name]->push($item);
            }
            unset($list);
        });
    }

    /**
     * 获取池中连接对象
     * @param string $name
     * @return mixed
     */
    protected function getPoolConnection(string $name)
    {
        $pool = $this->getPool($name);

        if ($this->connectionCount[$name] < $this->maxCount[$name]) {
            $array = [
                'last_use_time' => time(),
                'connection'    => $this->createPoolConnection($name)
            ];
            $connection = $array['connection'];
            $this->connectionCount[$name]++;
        } else {
            $array = $pool->pop($this->maxWaitTime[$name]);
            if ($array === false) {
                throw new RuntimeException(sprintf(
                    'Borrow the connection timeout in %.2f(s), connections in pool: %d, all connections: %d',
                    $this->maxWaitTime[$name],
                    $pool->length(),
                    $this->connectionCount[$name] ?? 0
                ));
            }
            $connection = $array['connection'];
        }
        // 自动回收
        defer(function () use ($connection,$pool){
            if (method_exists($connection,'rollback')){
                $connection->rollback();
            }
            Context::remove();
            if (!$pool->isFull()){
                $pool->push([
                    'last_use_time' => time(),
                    'connection'    => $connection
                ]);
            }
        });

        return $connection;
    }

    /**
     * 池对象销毁回调
     * @param mixed $connection
     * @param string $name
     */
    protected function poolMemberDestroy($connection,string $name){}


    /**
     * 创建连接池连接
     * @param string $name  标识
     * @return mixed
     */
    abstract protected function createPoolConnection(string $name);


    /**
     * 连接池配置
     * @param $name
     * @return array
     *             [
     *               max_wait_time => 获取对象最大等待时间 (s),
     *               max_count     => 池中最大连接数
     *               min_count     => 池中最小连接数
     *               timer_ms      => 定时器周期时长 (ms)
     *               spare_time    => 对象最大空闲时间 (s)
     *              ]
     */
    abstract protected function getPoolConfig(string $name): array;

    /**
     * 销毁连接池
     */
    public function __destruct()
    {
        foreach ($this->pools as $pool) {
            while (true) {
                if ($pool->isEmpty()) {
                    break;
                }
                $handler = $pool->pop(0.001);
                unset($handler);
            }
            $pool->close();
        }
        foreach ($this->timeId as $id)
            Timer::getInstance()->clear($id);
    }
}
