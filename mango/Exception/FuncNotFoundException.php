<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/26 16:34
 * @Email : 30191306465@qq.com
 */

namespace Mango\Exception;

/**
 * 函数不存在
 * Class FuncNotFoundException
 * @package Mango\Exception
 */
class FuncNotFoundException extends \RuntimeException{

    protected $func;

    public function __construct(string $message, string $func = '', \Throwable $previous = null)
    {
        $this->message = $message;
        $this->func   = $func;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取方法名
     * @access public
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }
}