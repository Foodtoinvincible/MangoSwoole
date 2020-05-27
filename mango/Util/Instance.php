<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/12/4 14:40
 * @Email: 30191306465@qq.com
 */

namespace Mango\Pool;


use Mango\Exception\EmptyException;

trait Instance{

    // 当前对象实列
    private static $instance = null;

    protected function __construct($config = []){

        $this->config = $config;
        // 创建指定数量连接对象
        $this->news($this->config['min_object_number']);
    }


    /**
     * 初始化
     * @param array $config
     * @throws EmptyException
     */
    public static function initialize($config = []): void{


        if (empty($config))
            throw new EmptyException('Pool config empty');
        if (!static::$instance)
            static::$instance = new static($config);

    }

    /**
     * 获取实列
     * @param array $config
     * @return AbstractPool
     * @throws EmptyException
     */
    public static function getInstance($config = []) : AbstractPool{


        if (!static::$instance){
            static::initialize($config);
        }
        return static::$instance;
    }

}