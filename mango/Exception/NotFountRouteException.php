<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/10 8:29
 * @Email : 30191306465@qq.com
 */

namespace Mango\Exception;


use Mango\Http\Response;
use Throwable;

class NotFountRouteException extends \RuntimeException {

    /**
     * NotFountRouteException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}