<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/26 1:21
 * @Email : 30191306465@qq.com
 */

namespace Mango;


use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Mango\Component\Singleton;
use Mango\Exception\ClassNotFoundException;
use Mango\Exception\FuncNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * 容器
 * Class Container
 * @package Mango
 */
class Container implements ArrayAccess, IteratorAggregate, Countable{

    use Singleton;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [];

    /**
     * 容器回调
     * @var array
     */
    protected $invokeCallback = [];

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @param string|array $abstract 类、接口
     * @param mixed        $concrete 要绑定的类、闭包或者实例
     * @return $this
     */
    public function bind($abstract, $concrete = null){
        if (is_array($abstract)){
            foreach ($abstract as $key=>$val){
                $this->bind($key,$val);
            }
        }elseif ($concrete instanceof Closure){
            $this->bind[$abstract] = $concrete;
        }elseif (is_object($concrete)){
            $this->instance($abstract,$concrete);
        }else{
            $abstract = $this->getAlias($abstract);
            $this->bind[$abstract] = $concrete;

        }
        return $this;
    }

    /**
     * 绑定一个类实例到容器
     * @access public
     * @param string $abstract 类名
     * @param object $instance 类的实例
     * @return $this
     */
    public function instance(string $abstract, $instance)
    {
        $abstract = $this->getAlias($abstract);

        $this->instances[$abstract] = $instance;
        return $this;
    }

    /**
     * 注册一个容器对象回调
     * @param string|Closure $abstract
     * @param Closure|null   $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null): void
    {
        if ($abstract instanceof Closure) {
            $this->invokeCallback['*'][] = $abstract;
            return;
        }
        $abstract = $this->getAlias($abstract);
        $this->invokeCallback[$abstract][] = $callback;
    }

    /**
     * 创建类的实例 已经存在则直接获取
     * @access public
     * @param string $abstract    类名或者标识
     * @param array  $vars        变量
     * @param bool   $newInstance 是否每次创建新的实例
     * @return mixed|object
     * @throws ReflectionException
     */
    public function make(string $abstract, array $vars = [], bool $newInstance = false)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && !$newInstance) {
            return $this->instances[$abstract];
        }
        if (isset($this->bind[$abstract]) && $this->bind[$abstract] instanceof Closure) {
            // 如果绑定的是一个函数
            $object = $this->invokeFunc($this->bind[$abstract], $vars);
        } else {
            // 是类，便它实例化
            $object = $this->invokeClass($abstract, $vars);
        }
        // 重新建立这个对象
        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 根据别名获取真实类名
     * @param  string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];

            if (is_string($bind)) {
                return $this->getAlias($bind);
            }
        }

        return $abstract;
    }

    /**
     * 判断容器中是否存在类及标识
     * @access public
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * 判断容器中是否存在类及标识
     * @param string $name 类名或者标识
     * @return bool
     */
    public function has($name): bool
    {
        return $this->bound($name);
    }

    /**
     * 判断容器中是否存在对象实例
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function exists(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->instances[$abstract]);
    }

    /**
     * 获取容器中的对象实例 不存在则创建
     * @param string     $abstract    类名或者标识
     * @param array|true $vars        变量
     * @param bool       $newInstance 是否每次创建新的实例
     * @return mixed|object
     * @throws ReflectionException
     */
    public static function pull(string $abstract, array $vars = [], bool $newInstance = false)
    {
        return static::getInstance()->make($abstract, $vars, $newInstance);
    }

    /**
     * 获取容器中的对象实例
     * @param string $abstract 类名或者标识
     * @return mixed|object
     * @throws ReflectionException
     */
    public function get($abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }
        throw new ClassNotFoundException('class not exists: ' . $abstract, $abstract);
    }

    /**
     * 删除容器中的对象实例
     * @param string $name 类名或者标识
     * @return void
     */
    public function delete($name)
    {
        $name = $this->getAlias($name);
        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
    }

    /**
     * 绑定参数
     * @access protected
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array                      $vars    参数
     * @return array
     * @throws ReflectionException
     */
    protected function bindParams(ReflectionFunctionAbstract $reflect, array $vars = []): array
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();
        $args   = [];

        foreach ($params as $param) {
            $name      = $param->getName();
            $lowerName = $name;
            $class     = $param->getClass();

            if ($class) {
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif (0 == $type && isset($vars[$lowerName])) {
                $args[] = $vars[$lowerName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }

    /**
     * 获取对象类型的参数值
     * @param string $className 类名
     * @param array  $vars  参数
     * @return mixed|object
     * @throws ReflectionException
     */
    protected function getObjectParam(string $className,array &$vars){

        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className){
            $result = $value;
            array_shift($vars);
        }else{
            $result = $this->make($className);
        }
        return $result;
    }

    /**
     * 执行函数或者闭包方法 支持参数调用
     * @param string|Closure $func 函数或者闭包
     * @param array          $vars 参数
     * @return mixed
     * @throws ReflectionException
     */
    public function invokeFunc($func,array $vars = []){

        try {
            // 使用反射获取这个方法的信息
            $reflect = new ReflectionFunction($func);
        } catch (ReflectionException $e) {
            throw new FuncNotFoundException("function not exists: {$func}()", $func, $e);
        }
        $args = $this->bindParams($reflect, $vars);
        // 执行这个函数
        return $func(...$args);

    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param mixed $method     方法
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     * @throws ReflectionException
     */
    public function invokeMethod($method, array $vars = [], bool $accessible = false)
    {
        if (is_array($method)) {
            [$class, $method] = $method;

            $class = is_object($class) ? $class : $this->invokeClass($class);
        } else {
            // 静态方法
            [$class, $method] = explode('::', $method);
        }

        try {
            $reflect = new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new FuncNotFoundException('method not exists: ' . $class . '::' . $method . '()', "{$class}::{$method}", $e);
        }

        $args = $this->bindParams($reflect, $vars);

        if ($accessible) {
            $reflect->setAccessible($accessible);
        }

        return $reflect->invokeArgs(is_object($class) ? $class : null, $args);
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param object $instance 对象实例
     * @param mixed  $reflect  反射类
     * @param array  $vars     参数
     * @return mixed
     * @throws ReflectionException
     */
    public function invokeReflectMethod($instance, $reflect, array $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

    /**
     * 调用反射执行callable 支持参数绑定
     * @param mixed $callable
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     * @throws ReflectionException
     */
    public function invoke($callable, array $vars = [], bool $accessible = false)
    {
        if ($callable instanceof Closure) {
            return $this->invokeFunc($callable, $vars);
        } elseif (is_string($callable) && false === strpos($callable, '::')) {
            return $this->invokeFunc($callable, $vars);
        } else {
            return $this->invokeMethod($callable, $vars, $accessible);
        }
    }

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @param string $class 类名
     * @param array  $vars  变量
     * @return mixed|object
     * @throws ReflectionException
     */
    public function invokeClass(string $class,array $vars = []){
        try {
            // 获取类的信息
            $reflect = new ReflectionClass($class);
        }catch (ReflectionException $e){
            throw new ClassNotFoundException("class not exists: {$class}",$class,$e->getPrevious());
        }

        // 判断make
        if ($reflect->hasMethod('__make')){
            $method = $reflect->getMethod('__make');
            if ($method->isPublic() && $method->isStatic()){
                $args = $this->bindParams($method,$vars);
                return $method->invokeArgs(null,$args);
            }
        }
        // make不存在 使用 __construct
        $construct = $reflect->getConstructor();

        $args = $construct ? $this->bindParams($construct,$vars) : [];
        $object = $reflect->newInstanceArgs($args);
        // 对象创建后执行回调
        $this->invokeAfter($class,$object);
        return $object;
    }

    /**
     * 执行invokeClass回调
     * @param string $class  对象类名
     * @param object $object 容器对象实例
     * @return void
     */
    protected function invokeAfter(string $class, $object): void
    {
        if (isset($this->invokeCallback['*'])) {
            foreach ($this->invokeCallback['*'] as $callback) {
                $callback($object, $this);
            }
        }

        if (isset($this->invokeCallback[$class])) {
            foreach ($this->invokeCallback[$class] as $callback) {
                $callback($object, $this);
            }
        }
    }

    /**
     * 创建工厂对象实例
     * @param string $name      工厂类名
     * @param string $namespace 默认命名空间
     * @param mixed  ...$args
     * @return mixed|object
     * @throws ReflectionException
     */
    public static function factory(string $name, string $namespace = '', ...$args)
    {
        $class = false !== strpos($name, '\\') ? $name : $namespace . ucwords($name);

        return Container::getInstance()->invokeClass($class, $args);
    }

    public function __set($name, $value)
    {
        $this->bind($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name): bool
    {
        return $this->exists($name);
    }

    public function __unset($name)
    {
        $this->delete($name);
    }

    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    //Countable
    public function count()
    {
        return count($this->instances);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->instances);
    }
}