<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/8 9:35
 * @Email : 30191306465@qq.com
 */

namespace Mango\Http;


use Mango\App;

/**
 * 请求对象
 * Class Request
 * @package Mango\Http
 */
class Request{

    /**
     * @var \Swoole\Http\Request
     */
    private $request;

    /**
     * 响应对象
     * @var Response
     */
    protected $response;

    /**
     * \Swoole\Http\Request->rowContent解析前的内容
     * @var mixed
     */
    protected $input;

    /**
     * 请求类型
     * @var string
     */
    protected $method;

    /**
     * 当前GET参数
     * @var array
     */
    protected $get = [];

    /**
     * 当前POST参数
     * @var array
     */
    protected $post = [];

    /**
     * 当前PUT参数
     * @var array
     */
    protected $put;

    /**
     * COOKIE数据
     * @var array
     */
    protected $cookie = [];

    /**
     * 当前FILE参数
     * @var array
     */
    protected $file = [];

    /**
     * 当前HEADER参数
     * @var array
     */
    protected $header = [];

    /**
     * 当前请求server参数
     * @var array
     */
    protected $server = [];

    /**
     * 是否合并参数了
     * @var bool
     */
    protected $mergeParam = false;

    /**
     * 请求参数POST + GET + PUT + ...
     * @var array
     */
    protected $param = [];

    /**
     * 执行方法名
     * @var string
     */
    protected $action;

    /**
     * 执行控制器
     * @var string
     */
    protected $controller;


    public function __construct(\Swoole\Http\Request $request,?Response $response){
        $this->request  = $request;
        $this->response = $response;
        $this->input    = $request->rawContent();

        $this->header = array_change_key_case($request->header);
        $this->server = array_change_key_case($request->server);

        $inputData = $this->getInputData($this->input);

        $this->get     = $request->get ?? [];
        $this->post    = $request->post ?? $inputData;
        $this->put     = $inputData;
        $this->cookie  = $request->cookie ?? [];
        $this->file    = $request->files ?? [];
        $this->method  = $this->server['request_method'];
    }

    /**
     * 读取或设置执行方法名
     * @param string $name  名称
     * @return string
     */
    public function action(string $name = ''){
        if ($name)
            $this->action = $name;
        return $this->action;
    }

    /**
     * 读取或设置控制器
     * @param string $name  名称
     * @return string
     */
    public function controller(string $name = ''){
        if ($name)
            $this->controller = $name;
        return $this->controller;
    }

    /**
     * 获取Input 数据
     * @param $content
     * @return array
     */
    protected function getInputData($content): array
    {
        $contentType = $this->contentType();
        if ($contentType == 'application/x-www-form-urlencoded') {
            parse_str($content, $data);
            return $data;
        } elseif (false !== strpos($contentType, 'json')) {
            return (array) json_decode($content, true);
        }

        return [];
    }

    /**
     * 请求内容类型
     * @return string|null
     */
    public function contentType() : ?string{
        return $this->header['content-type'] ?? null;
    }

    /**
     * 获取响应对象
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * 获取Swoole Request对象
     * @return \Swoole\Http\Request
     */
    public function getSwooleRequest(): \Swoole\Http\Request{
        return $this->request;
    }

    /**
     * 获取请求路径信息
     * @return string
     */
    public function pathInfo(): string {
        return $this->server['path_info'];
    }

    /**
     * 获取Header信息
     * @param string $name      参数名
     * @return array|mixed|null
     */
    public function header(string $name = ''){
        if ($name == '')
            return $this->header;
        return $this->input($this->header,$name);
    }

    /**
     * 获取Serve信息
     * @param string $name      参数名
     * @return array|mixed|null
     */
    public function server(string $name = ''){
        if ($name == '')
            return $this->server;
        return $this->input($this->server,$name);
    }

    /**
     * 获取POST请求参数
     * @param string $name      参数名
     * @param null   $default   默认值
     * @return array|mixed|null
     */
    public function post(string $name = '', $default = null){

        if ($name == '')
            return $this->post;
        return $this->input($this->post,$name,$default);

    }

    /**
     * 获取GET请求参数
     * @param string $name      参数名
     * @param null   $default   默认值
     * @return array|mixed|null
     */
    public function get(string $name = '', $default = null){
        if ($name == '')
            return $this->get;
        return $this->input($this->get,$name,$default);
    }

    /**
     * 获取PATCH请求参数
     * @param string $name      参数名
     * @param null   $default   默认值
     * @return array|mixed|null
     */
    public function patch(string $name = '', $default = null){
        return $this->put($name,$default);
    }

    /**
     * 获取PUT请求参数
     * @param string $name      参数名
     * @param null   $default   默认值
     * @return array|mixed|null
     */
    public function put(string $name = '', $default = null){
        if ($name == '')
            return $this->put;
        return $this->input($this->put,$name,$default);
    }

    /**
     * 获取DELETE请求参数
     * @param string $name      参数名
     * @param null   $default   默认值
     * @return array|mixed|null
     */
    public function delete(string $name = '', $default = null){
        return $this->put($name,$default);
    }

    /**
     * 获取当前请求的参数
     * @param  string|array $name 变量名
     * @param  mixed        $default 默认值
     * @param  mixed       $filter 过滤
     * @return mixed
     */
    public function param($name = '', $default = null,$filter = null)
    {
        if (empty($this->mergeParam)) {
            $method = $this->method();

            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post();
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put();
                    break;
                default:
                    $vars = [];
            }

            // 当前请求参数和URL地址中的参数合并
            $this->param = array_merge($this->param, $this->get(), $vars);

            $this->mergeParam = true;
        }
        return $this->input($this->param, $name, $default,$filter);
    }

    /**
     * 判断参数是否存在
     * @param $name
     * @return bool
     */
    public function hasParam($name){
        return $this->param($name) === null ? false : true;
    }

    /**
     * 获取变量
     * @param  array        $data 数据源
     * @param  string|false $name 字段名
     * @param  mixed        $default 默认值
     * @param  mixed        $filter 过滤
     * @return mixed
     */
    public function input(array $data = [], $name = '', $default = null,$filter = null){

        if ($name === false)
            return $data;

        if ($name != ''){
            $data = $this->getData($data,$name,$default);
            if (is_null($data))
                return $default;
            if (is_object($data))
                return $data;
        }
        if ($filter)
            $data = call_user_func($filter,$data);
        return $data;
    }


    public function file(){
        return $this->file;
    }

    /**
     * 获取Cookie信息
     * @param string $name      参数名
     * @return array|mixed|null
     */
    public function cookie(string $name = ''){
        if ($name == '')
            return $this->cookie;
        return $this->getData($this->cookie,$name);
    }

    /**
     * 获取请求类型
     * @return string
     */
    public function method(): string{
        return strtoupper($this->method);
    }

    /**
     * 获取响应对象
     * @return Response|null
     */
    public function response(): ?Response{
        return $this->response;
    }

    /**
     * 设置Cookie
     * 如果出现同样的key，该方法会先将之前的进行过期处理
     * 如果想使用的Swoole 多个同样key 请调用 $this->response()->getSwooleCookie()->cookie()
     * @param string $key       cookie名称
     * @param string $value     值
     * @param int    $expire    cookie 的有效期
     */
    public function setCookie(string $key, string $value = '', int $expire = 0){
        if (isset($this->cookie[$key])){
            // 清理已存在的key
            $this->response()->cookie($key,'',time() - 86400);
        }

        $this->response()->cookie($key,$value,$expire);
        $this->cookie[$key] = $value;
    }

    /**
     * 清理Cookie
     */
    public function clearCookie(){

        foreach ($this->cookie as $key=>$value){
            $this->response()->cookie($key,'',time()-86400);
        }
        $this->cookie = [];
    }

    /**
     * 获取数据
     * @access public
     * @param  array  $data 数据源
     * @param  string $name 字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null){
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * 是否为GET请求
     * @access public
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() == 'GET';
    }

    /**
     * 是否为POST请求
     * @access public
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() == 'POST';
    }

    /**
     * 是否为PUT请求
     * @access public
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @access public
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @access public
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @access public
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @access public
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * 获取请求时间
     * @return int|null
     */
    public function time(){
        $info = $this->getClientInfo();
        if (!$info)
            return null;
        return $info['last_time'];
    }

    /**
     * 请求IP
     * @return array|mixed|null
     */
    public function ip(){
        if ($this->header('x-real-ip'))
            return $this->header('x-real-ip');
        return $this->server('remote_addr');
    }

    /**
     * 获取客户端信息
     * @return array|bool
     */
    public function getClientInfo(){
        return App::getInstance()->getServer()->getClientInfo($this->getSwooleRequest()->fd);
    }

    /**
     * 获取域名
     * @return string
     */
    public function domain(): string{
        return 'http://'.$this->header['host'];
    }
}