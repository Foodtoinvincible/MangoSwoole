<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/24 2:34
 * @Email : 30191306465@qq.com
 */

namespace Mango\Exception;


/**
 * 类不存在
 * Class ClassNotFoundException
 * @package Mango\Exception
 */
class ClassNotFoundException extends RuntimeException{

    protected $class;

    public function __construct(string $message, string $class = '', \Throwable $previous = null)
    {
        $this->message = $message;
        $this->class   = $class;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}