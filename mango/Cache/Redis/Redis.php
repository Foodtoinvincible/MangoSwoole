<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/27 23:44
 * @Email : 30191306465@qq.com
 */

namespace Mango\Cache\Redis;


use Mango\Component\concerns\Pool;
use Mango\Component\Singleton;
use Mango\Config;
use Mango\coroutine\Context;

/**
 * Redis 连接池
 * Class Redis
 * @package Mango\Cache\Redis
 * @mixin \Redis
 */
class Redis{

    use Pool;

    use Singleton;

    /**
     * 创建连接实例，如果已存在则返回连接实例
     * @param string $name
     * @return mixed|null
     */
    protected function instance(string $name = 'default')
    {
        return Context::rememberData("redis.connection.{$name}", function () use ($name) {
            return $this->getPoolConnection($name);
        });
    }

    /**
     * 销毁连接
     * @param Redis  $connection
     * @param string $name
     */
    protected function poolMemberDestroy($connection, string $name){
        $connection->close();
        $connection = null;
    }

    /**
     * 创建redis
     * @param string $name
     * @return \Redis
     */
    protected function createPoolConnection(string $name){

        $redis = new \Redis();
        $conf = Config::getInstance()->get('app.redis.connection');
        if (!$conf)
            throw new \InvalidArgumentException('Redis Config Connection not exits');
        $redis->connect($conf['host'],$conf['port'] ?? 6379,$conf['timeout'] ?? 5);
        if (!empty($conf['password']))
            $redis->auth($conf['password']);
        $redis->select($conf['select'] ?? 0);
        return $redis;
    }

    /**
     * 获取连接池配置
     * @param string $name
     * @return array
     */
    public function getPoolConfig(string $name){
        return [
            'max_wait_time' => Config::getInstance()->get("app.redis.max_wait_time",30),
            'max_count'     => Config::getInstance()->get("app.redis.max_count",3),
            'min_count'     => Config::getInstance()->get("app.redis.min_count",1),
            'timer_ms'      => Config::getInstance()->get("app.redis.timer_ms",60000),
            'spare_time'    => Config::getInstance()->get("app.redis.spare_time",60000),
        ];
    }

    /**
     * 返回Redis句柄
     * @return \Redis
     */
    public function handler(): \Redis{
        return $this->instance();
    }

    /**
     * 快速调用 Redis 的方法
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        $this->handler()->$method(...$arguments);
    }
}