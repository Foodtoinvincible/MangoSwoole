<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/24 20:23
 * @Email : 30191306465@qq.com
 */

namespace Mango;

use Mango\Exception\ErrorException;
use Mango\Exception\Handler;
use Mango\Exception\ThrowableError;

/**
 * 错误
 * Class Error
 * @package Mango
 */
class Error{

    /**
     * 注册异常处理
     * @access public
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'error']);
        set_exception_handler([__CLASS__, 'exception']);
        register_shutdown_function([__CLASS__, 'shutdown']);
    }

    /**
     * 异常处理
     * @param \Exception $e
     */
    public static function exception($e)
    {
        if (!$e instanceof \Exception) {
            $e = new ThrowableError($e);
        }
        self::getExceptionHandler()->report($e);
    }

    /**
     * 错误 处理
     * @access public
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @throws ErrorException
     */
    public static function error($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        if (error_reporting() & $errno) {
            // 将错误信息托管至 Mango\Exception\ErrorException
            throw $exception;
        }

        self::getExceptionHandler()->report($exception);
    }

    /**
     * 程序结束 处理
     * @access public
     */
    public static function shutdown()
    {
        // 错误是否致命
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            // 将错误信息托管至think\ErrorException
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);
            self::exception($exception);
        }

        // 写入日志
        // TODO 记录日志
    }

    /**
     * 确定错误类型是否致命
     * @access protected
     * @param  int $type
     * @return bool
     */
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }


    /**
     * 获取异常处理实例
     * @return Handler
     */
    public static function getExceptionHandler(){
        return Handler::getInstance();
    }
}
