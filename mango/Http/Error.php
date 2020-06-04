<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/24 21:22
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;


use Mango\App;
use Mango\Template\Template;

/**
 * HTTP服务器中 错误处理类
 * Class Error
 * @package Mango\Http
 */
class Error{


    /**
     * 错误处理
     * @param \Error  $error
     * @param Request $request
     */
    public static function error(\Error $error,Request $request){
        self::output($error,$request);
    }

    /**
     * 异常处理
     * @param \Exception $exception
     * @param Request    $request
     */
    public static function exception(\Exception $exception,Request $request){
        self::output($exception,$request);
    }


    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @param \Throwable $throwable
     * @return array
     */
    protected static function getSourceCode(\Throwable $throwable)
    {
        // 读取前9行和后9行
        $line  = $throwable->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($throwable->getFile());
            $source   = [
                'first'     => $first,
                'source'    => array_slice($contents, $first - 1, 19),
                'err_line'  => $line
            ];
        } catch (\Throwable $e) {
            $source = [];
        }

        return $source;
    }

    /**
     * 处理源码
     * @param array $source
     * @return array
     */
    protected static function handlerSourceCode(array $source){

        $code = implode("\n@@@handlerSourceCode@@@",$source['source']);

        foreach (self::getCodeHighlightRegx() as $k => $v){
            $code = preg_replace($v['regx'],$v['str'],$code);
        }
        $source['source'] =  explode("\n@@@handlerSourceCode@@@",$code);
        return $source;
    }

    /**
     * 获取代码高亮正则
     * @return array
     */
    protected static function getCodeHighlightRegx(){
        $regx = [
            // 匹配字符串
            'string'    => [
                'regx'  => ['/("(.*?)")/mis','/(\'(.*?)\')/U'],
                'str'   => '<span class="string">${1}</span>',
            ],
            // 匹配注释
            'note'      => [
                'regx'  => ['/(\/\*\*.*?\*\/)/mis','/(\/\/[^\n|\r\n]*)/mis','/(#[^\n|\r\n]*)/'],
                'str'   => '<span class="note">${1}</span>',
            ],
            // 匹配关键字
            'keywords'  => [
                'regx'  => [
                    '/\b(public|static|protected|private|final|new|function|case|throw|if|self|else|elseif|switch|default|while|do|try|catch|class\s|extends|abstract|interface)\b/',
                ],
                'str'   => '<span class="keywords">${1}</span>'
            ],
            // 匹配正确的变量，非正确匹配 /(\$[\w]+)/
            'variable'  => [
                'regx'  => '/(\$[a-zA-z_]+[\w]*)/',
                'str'   => '<span class="variable">${1}</span>'
            ],
            'function'  => [
                'regx'  => '/\b(\w+)\b(\()/',
                'str'   => '<span class="function">${1}</span>${2}'
            ],
//           // 匹配大括号
            'braces'    => [
                'regx'  => '/(\(|\[|\{|\}|\]|\)|\->)/',
                'str'   => '<span class="braces">${1}</span>'
            ],
        ];
        return $regx;
    }

    /**
     * 异常输出
     * @param \Throwable $throwable
     * @param Request    $request
     */
    protected static function output(\Throwable $throwable, Request $request){
        $view = Template::getInstance();
        try {
            $vars = [
                'exception_name'    => str_replace("\\",'/',get_class($throwable)),
                'code'              => $throwable->getCode(),
                'msg'               => $throwable->getMessage(),
                'line'              => $throwable->getLine(),
                'file'              => $throwable->getFile(),
                'trace'             => $throwable->getTrace(),
                'code_list'         => self::handlerSourceCode(self::getSourceCode($throwable)),
                'vars'              => [
                    'POST'              => $request->post(),
                    'PUT'               => $request->put(),
                    'GET'               => $request->get(),
                    'Header'            => $request->header(),
                    'Cookie'            => $request->cookie(),
                    'Defined'           => get_defined_constants(true)['user']
                ]
            ];
            $html = $view->fetch(__DIR__.'/tpl/exception.html',$vars);
            $request->response()->html($html,500);
        }catch (\Throwable $throwable){}
    }
}