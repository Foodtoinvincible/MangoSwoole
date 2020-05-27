<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/24 20:23
 * @Email : 30191306465@qq.com
 */

namespace Mango\Exception;

use Mango\App;
use Mango\Component\Singleton;

/**
 * 异常处理
 * Class Handler
 * @package Mango\Exception
 */
class Handler{

    use Singleton;

    /**
     * 报告或记录异常
     * @param \Throwable $exception
     */
    public function report(\Throwable $exception)
    {
        // 收集异常数据
        if (App::isDebug()) {
            $data = [
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code'    => $this->getCode($exception),
            ];
            $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
        } else {
            $data = [
                'code'    => $this->getCode($exception),
                'message' => $this->getMessage($exception),
            ];
            $log = "[{$data['code']}]{$data['message']}";
        }
        $log .= "\r\n" . $exception->getTraceAsString();

    }


    /**
     * 获取错误编码
     * ErrorException则使用错误级别作为错误编码
     * @param \Throwable $exception
     * @return int
     */
    protected function getCode(\Throwable $exception)
    {
        $code = $exception->getCode();

        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }

        return $code;
    }

    /**
     * 获取错误信息
     * @param \Throwable $exception
     * @return string
     */
    protected function getMessage(\Throwable $exception)
    {
        return $exception->getMessage();

    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @param \Throwable $exception
     * @return array
     */
    protected function getSourceCode(\Throwable $exception)
    {
        // 读取前9行和后9行
        $line  = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($exception->getFile());
            $source   = [
                'first'  => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (\Throwable $e) {
            $source = [];
        }

        return $source;
    }

}