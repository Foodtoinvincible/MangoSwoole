<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/9 19:23
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;


/**
 * Class Response
 * @package Mango\Http
 */
class Response {


    /**
     * 要输出数据
     * @var mixed
     */
    protected $content = '';

    /**
     * 输出header
     * @var array
     */
    protected $header = [];

    /**
     * 状态
     * @var int
     */
    protected $code = 200;

    /**
     * Cookie
     * @var array
     */
    protected $cookie = [];

    /**
     * 是否输出过
     * @var bool
     */
    protected $isOutput = false;

    /**
     * @var \Swoole\Http\Response
     */
    private $response;

    public function __construct(\Swoole\Http\Response $response){

        $this->response = $response;

    }

    /**
     * 获取SwooLe\Http\Response对象
     * @return \Swoole\Http\Response
     */
    public function getSwooleResponse(): \Swoole\Http\Response{
        return $this->response;
    }

    /**
     * 输出json
     * @param mixed     $data 需要输出的数据
     * @param int       $code 状态
     * @param int     $options json选项
     */
    public function json($data,int $code = 200, int $options = JSON_UNESCAPED_UNICODE){
        $this->header('Content-Type','application/json;charset=utf-8');
        $this->code($code);
        $this->content = json_encode($data,$options);
        $this->send();
    }

    /**
     * 输出XML
     * @param array  $data      需要输出的数据
     * @param string $rootName  xml根节点名称
     * @param int    $code      状态
     */
    public function xml(array $data,string $rootName = 'root',int $code = 200){

        $this->header('Content-Type','text/xml;charset=utf-8');
        $this->code($code);
        $xml = "<{$rootName}>";
        foreach ($data as $k=>$v){
            $xml .= "<{$k}>" . $v . "</{$k}>";
        }
        $xml .= "</{$rootName}>";
        $this->setContent($xml);
        $this->send();
    }

    /**
     * 发送HTMl数据
     * @param string $data  HTML内容
     * @param int    $code  状态
     */
    public function html(string $data,$code = 200){
        $this->header('Content-Type','text/html;charset=utf-8');
        $this->code($code);
        $this->setContent($data);
        $this->send();
    }

    /**
     * 设置输出数据
     * @return $this
     * @param string $content
     */
    public function setContent(string $content){
        $this->content = $content;
        return $this;
    }

    /**
     * 追加输出内容
     * @param string $content
     * @return $this
     */
    public function appendContent(string $content){
        $this->content .= $content;
        return $this;
    }

    /**
     * 获取将要输出的数据
     * @return mixed
     */
    public function getContent(){
        return $this->content;
    }

    /**
     * 设置输出Header
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function header(string $key,string $value){
        $this->header[$key] = $value;
        return $this;
    }

    /**
     * 设置Http状态码
     * @param int $code
     * @return $this
     */
    public function code($code = 200){
        $this->code = $code;
        return $this;
    }

    /**
     * 响应内容
     * @param bool $end 是否自动调用end()
     */
    public function send($end = true){
        foreach ($this->header as $key=>$item){
            $this->getSwooleResponse()->header($key,$item);
        }
        foreach ($this->cookie as $key=>$item){
            $this->getSwooleResponse()->cookie($key,$item['value'],$item['expire']);
        }
        $this->getSwooleResponse()->status($this->code);
        if ($end){
            $this->getSwooleResponse()->end($this->content);
        }
        $this->isOutput = true;
    }

    /**
     * 是否输出了
     * @return bool
     */
    public function isOutput(): bool {
        return $this->isOutput;
    }

    /**
     * 设置输出Cookie
     * @param string $key       cookie名称
     * @param string $value     值
     * @param int    $expire    时长
     * @return $this
     */
    public function cookie(string $key, string $value = '', int $expire = 0){
        $this->cookie[$key] = [
            'value'     => $value,
            'expire'    => $expire,
        ];
        return $this;
    }

    /**
     * LastModified
     * @param  string $time
     * @return $this
     */
    public function lastModified(string $time)
    {
        $this->header['Last-Modified'] = $time;

        return $this;
    }
}