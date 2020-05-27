<?php

namespace Mango\Db;

use Mango\Component\concerns\Pool;
use Mango\Config;
use Swoole\Coroutine\Channel;
use think\db\ConnectionInterface;
use Mango\coroutine\Context;
use think\Model;

/**
 * Class Db
 * @package think\swoole\pool
 * @property Config $config
 */
class Db extends \think\DbManager
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
            if (!$this->hasPool($name)){
                $this->createPool($name,$this->getPoolConfig($name));
            }
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
     * 制造
     * @return static
     */
    public static function __make(){
        $_self = new static();
        Model::setDb($_self);
        $_self->setConfig(Config::getInstance()->get('app.database'));
        return $_self;
    }

    /**
     * 获取连接池配置信息配送
     * @param string $name
     * @return array
     */
    public function getPoolConfig(string $name){
        return [
            'max_wait_time' => Config::getInstance()->get("database.connections.{$name}.max_wait_time",30),
            'max_count'     => Config::getInstance()->get("database.connections.{$name}.max_count",3),
            'min_count'     => Config::getInstance()->get("database.connections.{$name}.min_count",1),
            'timer_ms'      => Config::getInstance()->get("database.connections.{$name}.timer_ms",60000),
            'spare_time'    => Config::getInstance()->get("database.connections.{$name}.spare_time",60000),
        ];
    }
}
