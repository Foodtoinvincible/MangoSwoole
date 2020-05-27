<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/27 22:54
 * @Email : 30191306465@qq.com
 */

namespace Mango;


use InvalidArgumentException;
use ReflectionException;
abstract class Manager{

    /**
     * @var App
     */
    protected $app;


    /**
     * 驱动实例
     * @var array
     */
    protected $drivers = [];

    /**
     * 驱动命名空间
     * @var null|string
     */
    protected $namespace = null;

    public function __construct(App $app){
        $this->app = $app;
    }

    /**
     * 获取驱动实例
     * @param string|null $name
     * @return mixed|object
     */
    protected function driver(string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @return mixed|object
     * @throws ReflectionException
     */
    protected function getDriver(string $name)
    {
        return $this->drivers[$name] ?? $this->createDriver($name);
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return mixed
     */
    protected function resolveType(string $name)
    {
        return $name;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name)
    {
        return $name;
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass(string $type): string
    {
        if ($this->namespace || false !== strpos($type, '\\')) {
            $class = false !== strpos($type, '\\') ? $type : $this->namespace . $type;

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

    /**
     * 获取驱动参数
     * @param $name
     * @return array
     */
    protected function resolveParams($name): array
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 创建驱动
     * @param string $name
     * @return mixed|object
     * @throws ReflectionException
     */
    protected function createDriver(string $name)
    {
        $type = $this->resolveType($name);

        $method = 'create' . $name . 'Driver';

        $params = $this->resolveParams($name);

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        $class = $this->resolveClass($type);

        return $this->app->invokeClass($class, $params);
    }

    /**
     * 移除一个驱动实例
     *
     * @param array|string|null $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name = $name ?? $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     * @return string|null
     */
    abstract public function getDefaultDriver();

    /**
     * 动态调用
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws ReflectionException
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}