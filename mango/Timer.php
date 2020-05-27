<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2020/4/9 16:35
 * @Email: 30191306465@qq.com
 */

namespace Mango;


use Mango\Component\Singleton;
use Swoole\Timer as SWTimer;

/**
 * 定时器
 * Class Timer
 * @package Mango
 */
class Timer
{
    protected $timerMap = [];

    use Singleton;


    public function loop(int $ms, callable $callback, $name = null, ...$params): int
    {
        $id = SWTimer::tick($ms, $callback, ...$params);
        if ($name !== null) {
            $this->timerMap[md5($name)] = $id;
        }
        return $id;
    }

    public function clear($timerIdOrName): bool
    {
        $tid = null;
        if(is_numeric($timerIdOrName)){
            $tid = $timerIdOrName;
        }else if(isset($this->timerMap[md5($timerIdOrName)])){
            $tid = $this->timerMap[md5($timerIdOrName)];
            unset($this->timerMap[md5($timerIdOrName)]);
        }
        if($tid && SWTimer::info($tid)){
            SWTimer::clear($tid);
            return true;
        }
        return false;
    }

    public function clearAll(): bool
    {
        $this->timerMap = [];
        SWTimer::clearAll();
        return true;
    }

    public function after(int $ms, callable $callback, ...$params): int
    {
        return SWTimer::after($ms, $callback, ...$params);
    }

    public function list():array
    {
        return SWTimer::list();
    }
}
