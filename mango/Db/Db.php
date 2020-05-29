<?php

namespace Mango\Db;

use Mango\Component\concerns\Pool;
use Mango\Config;
use think\db\ConnectionInterface;
use Mango\coroutine\Context;
use think\DbManager;
use think\Model;
use \ReflectionClass;
/**
 * Class Db
 * @package Mango\Db
 */
class Db extends DbManager
{
    use Pool;


    /**
     * 创建数据库连接实例
     * @access protected
     * @param string|null $name  连接标识
     * @param bool        $force 强制重新连接
     * @return ConnectionInterface
     */
    protected function instance(string $name = null, bool $force = false): ConnectionInterface
    {
        if (empty($name)) {
            $name = $this->getConfig('default', 'mysql');
        }

        if ($force) {
            return $this->createConnection($name);
        }
        return Context::rememberData("db.connection.{$name}", function () use ($name) {
            return $this->getPoolConnection($name);
        });
    }


    /**
     * 创建连接对象
     * @param string $name
     * @return ConnectionInterface
     */
    protected function createPoolConnection(string $name)
    {
        return $this->createConnection($name);
    }

    /**
     * 连接配置信息
     * @param string $name
     * @return array
     */
    protected function getConnectionConfig(string $name): array
    {
        $config = parent::getConnectionConfig($name);
        //打开断线重连
        $config['break_reconnect'] = true;
        return $config;
    }
    /**
     * 池对象销毁回调
     * @param mixed $connection
     * @param string $name
     */
    protected function poolMemberDestroy($connection, string $name){
        try {

            // 销毁前执行一次回滚
            $connection->rollback();

            // $connection = null 是无法销毁的
            // 由于 $connection 这个对象在构造时
            // 同时又将自身 传递给了一个Builder 类进行构建 Builder对象
            // 然后将构造出的 Builder对象 赋值给自身的builder属性
            // 这样就造成了你有我,我有你的局面.两者销毁一个是无法达到完全销毁的
            // 需要先销毁一个,再销毁另一个

            // 获取这个连接的builder属性
            $builder = $connection->getBuilder();
            // 通过反射获取对象信息
            $ref = new ReflectionClass($builder);
            // 找出 connection 属性
            $refProperty = $ref->getProperty('connection');
            // 设置可访问并赋值null
            $refProperty->setAccessible(true);
            $refProperty->setValue($builder,null);
            // 销毁连接对象
            $connection = null;
        }catch (\Throwable $throwable){
            var_dump($throwable->getMessage());
        }
    }

    /**
     * 制造
     * @return static
     */
    public static function __make(){
        $instance = new static();
        Model::setDb($instance);
        $instance->setConfig(Config::getInstance()->get('app.database'));
        return $instance;
    }

    /**
     * 获取连接池配置信息配送
     * @param string $name
     * @return array
     */
    protected function getPoolConfig(string $name):array {
        return [
            'max_wait_time' => Config::getInstance()->get("app.database.connections.{$name}.max_wait_time",30),
            'max_count'     => Config::getInstance()->get("app.database.connections.{$name}.max_count",3),
            'min_count'     => Config::getInstance()->get("app.database.connections.{$name}.min_count",1),
            'timer_ms'      => Config::getInstance()->get("app.database.connections.{$name}.timer_ms",60000),
            'spare_time'    => Config::getInstance()->get("app.database.connections.{$name}.spare_time",60000),
        ];
    }
}
