<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/25 15:14
 * @Email : 30191306465@qq.com
 */

namespace Mango\Template;

use Exception;
use Mango\Config;

/**
 * 模板
 * Class Template
 * @package Mango\Template
 */
class Template{


    protected $config = [
        'tpl_begin'          => '{',
        'tpl_end'            => '}',
        'tpl_var_identify'   => 'array', // .语法变量识别，array|object|'', 为空时自动识别,
        'save_path'          => 'runtime',
        'cache_suffix'       => 'php', // 默认模板缓存后缀
        'tpl_deny_func_list' => 'echo,exit', // 模板引擎禁用函数
        'tpl_deny_php'       => false, // 默认模板引擎是否禁用PHP原生代码
        'strip_space'        => false, // 是否去除模板文件里面的html空格与换行
        'taglib_begin'       => '{', // 标签库标签开始标记
        'taglib_end'         => '}', // 标签库标签结束标记
        'taglib_build_in'    => 'Tag', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'tpl_replace_string' => [],
        'default_filter'     => 'htmlentities', // 默认过滤方法 用于普通标签输出
    ];


    /**
     * 架构方法
     * Template constructor.
     */
    public function __construct(){

        $conf = Config::getInstance()->get('template');
        if (!empty($conf)){
            $this->config = array_merge($this->config,$conf);
        }
        $this->config['taglib_begin_origin'] = $this->config['taglib_begin'];
        $this->config['taglib_end_origin']   = $this->config['taglib_end'];
    }

    /**
     * 获取实例
     * @return static
     */
    public static function getInstance(): self{
        return new static();
    }

    /**
     * 变量数据
     * @var array
     */
    protected $data = [];


    /**
     * 已解析过的函数值
     * @var array
     */
    protected $varFunctionList = [];

    /**
     * 已解析过的变量值
     * @var array
     */
    protected $varParseList = [];


    /**
     * 模板引用记录
     * @var array
     */
    protected $includeFile = [];

    /**
     * 模板引擎配置项
     * @access public
     * @param  array|string $config
     * @return array|void|mixed
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } elseif (isset($this->config[$config])) {
            return $this->config[$config];
        }
    }

    /**
     * 模板变量赋值
     * @access public
     * @param  mixed $name
     * @param  mixed $value
     * @return void
     */
    public function assign($name, $value = ''): void{
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 模板渲染输出
     * @param string $template
     * @param array  $vars
     * @param array  $config
     * @return false|string
     * @throws Exception
     */
    public function fetch(string $template,array $vars = [],array $config = []){
        if (!empty($vars)) {
            $this->data = array_merge($this->data,$vars);
        }

        if (!empty($config)) {
            $this->config($config);
        }
        $filename = $this->getFilename($template);
        $cacheFilePath = $this->getSavePath().'/'.$filename;
        // 检查是否存在缓存
        if ($this->isCache($cacheFilePath)){
            $cacheMd5 = $this->getCacheFileMd5($cacheFilePath);
            if ($cacheMd5 == md5_file($template)){
                return $this->getEcho($cacheFilePath);
            }
        }
        $content = $this->read($template);
        $path = $this->compiler($content,$filename,md5_file($template));
        return $this->getEcho($path);
    }

    /**
     * 获取输出内容
     * @param string $path
     * @return false|string
     */
    protected function getEcho(string $path){
        ob_start();
        ob_implicit_flush(false);
        ob_clean();
        // 将所有变量定义
        foreach ($this->data as $k=>$v){
            $$k = $v;
        }
        include $path;
        return ob_get_clean();
    }

    /**
     * 判断缓存是否存在
     * @param string $path
     * @return bool
     */
    protected function isCache(string $path): bool{
        clearstatcache();
        return is_file($path);
    }

    /**
     * 读取文件
     * @param string $path
     * @return string
     */
    protected function read(string $path): string{
        return file_get_contents($path);
    }

    /**
     * 获取缓存文件缓存的md5_file值
     * @param string $path
     * @return string
     */
    protected function getCacheFileMd5(string $path): string{
        $f = @fopen($path,'r');
        $data = fgets($f);
        if (empty($data)) return '';
        if (preg_match('/\/\*([^*]+)/',$data,$matches)){
            return $matches[1];
        }
        return '';
    }

    /**
     * 获取文件名
     * @param string $template
     * @return string
     */
    protected function getFilename(string $template): string{
        return md5($template).'.'.$this->config['cache_suffix'];
    }

    /**
     * 获取文件保存路径
     * @return string
     */
    protected function getSavePath(): string{
        return $this->config['save_path'];
    }

    /**
     * 编译模板文件内容
     * @access private
     * @param  string    $content 模板内容
     * @param  string    $filename 缓存文件名
     * @param  string    $tplMd5    模板文件的md5值
     * @return string
     * @throws Exception
     */
    private function compiler(string &$content,string $filename,string $tplMd5):string
    {

        // 模板解析
        $this->parse($content);

        if ($this->config['strip_space']) {
            /* 去除html空格与换行 */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $content = preg_replace($find, $replace, $content);
        }
        // 优化生成的php代码
        $content = preg_replace('/\?>\s*<\?php\s(?!echo\b|\bend)/s', '', $content);

        // 模板过滤输出
        $replace = $this->config['tpl_replace_string'];
        $content = str_replace(array_keys($replace), array_values($replace), $content);

        // 添加安全代码
        $content = '<?php /*' . $tplMd5 . '*/ ?>' . "\n" . $content;
        $this->includeFile = [];
        return $this->save($content,$filename);
    }

    /**
     * 保存文件
     * @param string $content
     * @param string $filename
     * @return string
     */
    private function save(string &$content,string $filename): string{

        if (!is_dir($this->getSavePath())){
            mkdir($this->getSavePath(),0755,true);
        }
        $path = $this->getSavePath().'/'.$filename;
        file_put_contents($path,$content);
        return $path;
    }

    /**
     * 解析模板文件缓存
     * @param string $content
     * @throws Exception
     */
    private function parse(string &$content): void{

        // 内容为空不解析
        if (empty($content)) {
            return;
        }

        $this->parsePhp($content);

        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $tagLibs = explode(',', $this->config['taglib_build_in']);

        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }
        $this->parseTag($content);
    }

    /**
     * 检查PHP语法
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     * @throws \Exception
     */
    private function parsePhp(&$content)
    {
        // 短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);

        // PHP语法检查
        if ($this->config['tpl_deny_php'] && false !== strpos($content, '<?php')) {
            throw new Exception('not allow php tag');
        }
    }

    /**
     * TagLib库解析
     * @access public
     * @param  string   $tagLib 要解析的标签库
     * @param  string   $content 要解析的模板内容
     * @param  boolean  $hide 是否隐藏标签库前缀
     * @return void
     */
    public function parseTagLib($tagLib, &$content, $hide = false)
    {
        if (false !== strpos($tagLib, '\\')) {
            // 支持指定标签库的命名空间
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = '\\Mango\\Template\\taglib\\' . ucwords($tagLib);
        }

        $tLib = new $className($this);

        $tLib->parseTag($content, $hide ? '' : $tagLib);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseTag(string &$content)
    {
        $regex = $this->getRegex('tag');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str  = stripslashes($match[1]);
                $flag = substr($str, 0, 1);

                switch ($flag) {
                    case '$':
                        // 解析模板变量 格式 {$varName}
                        // 是否带有?号
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]=?)/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = $array[0];

                            $this->parseVar($name);

                            $str = trim(substr($str, $pos + 1));
                            $this->parseVar($str);
                            $first = substr($str, 0, 1);

                            if (strpos($name, ')')) {
                                // $name为对象或是自动识别，或者含有函数
                                if (isset($array[1])) {
                                    $this->parseVar($array[2]);
                                    $name .= $array[1] . $array[2];
                                }

                                switch ($first) {
                                    case '?':
                                        $this->parseVarFunction($name);
                                        $str = '<?php echo (' . $name . ') ? ' . $name . ' : ' . substr($str, 1) . '; ?>';
                                        break;
                                    case '=':
                                        $str = '<?php if(' . $name . ') echo ' . substr($str, 1) . '; ?>';
                                        break;
                                    default:
                                        $str = '<?php echo ' . $name . '?' . $str . '; ?>';
                                }
                            } else {
                                if (isset($array[1])) {
                                    $this->parseVar($array[2]);
                                    $express = $name . $array[1] . $array[2];
                                } else {
                                    $express = false;
                                }

                                if (in_array($first, ['?', '=', ':'])) {
                                    $str = trim(substr($str, 1));
                                    if ('$' == substr($str, 0, 1)) {
                                        $str = $this->parseVarFunction($str);
                                    }
                                }

                                // $name为数组
                                switch ($first) {
                                    case '?':
                                        // {$varname??'xxx'} $varname有定义则输出$varname,否则输出xxx
                                        $str = '<?php echo ' . ($express ?: 'isset(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    case '=':
                                        // {$varname?='xxx'} $varname为真时才输出xxx
                                        $str = '<?php if(' . ($express ?: '!empty(' . $name . ')') . ') echo ' . $str . '; ?>';
                                        break;
                                    case ':':
                                        // {$varname?:'xxx'} $varname为真时输出$varname,否则输出xxx
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            // {$varname ? 'a' : 'b'} $varname为真时输出a,否则输出b
                                            $array = explode(':', $str, 2);

                                            $array[0] = '$' == substr(trim($array[0]), 0, 1) ? $this->parseVarFunction($array[0]) : $array[0];
                                            $array[1] = '$' == substr(trim($array[1]), 0, 1) ? $this->parseVarFunction($array[1]) : $array[1];

                                            $str = implode(' : ', $array);
                                        }
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $str . '; ?>';
                                }
                            }
                        } else {
                            $this->parseVar($str);
                            $this->parseVarFunction($str);
                            $str = '<?php echo ' . $str . '; ?>';
                        }
                        break;
                    case ':':
                        // 输出某个函数的结果
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        // 执行某个函数
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+':
                        // 输出计算
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/':
                        // 注释标签
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        // 未识别的标签直接返回
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                        break;
                }

                $content = str_replace($match[0], $str, $content);
            }

            unset($matches);
        }
    }

    /**
     * 对模板中使用了函数的变量进行解析
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string    $varStr     变量字符串
     * @param  bool      $autoescape 自动转义
     * @return mixed|string
     */
    public function parseVarFunction(&$varStr, $autoescape = true)
    {
        if (!$autoescape && false === strpos($varStr, '|')) {
            return $varStr;
        } elseif ($autoescape && !preg_match('/\|(\s)?raw(\||\s)?/i', $varStr)) {
            $varStr .= '|' . $this->config['default_filter'];
        }


        $_key = md5($varStr);

        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($this->varFunctionList[$_key])) {
            $varStr = $this->varFunctionList[$_key];
        } else {
            $varArray = explode('|', $varStr);

            // 取得变量名称
            $name = trim(array_shift($varArray));

            // 对变量使用函数
            $length = count($varArray);

            // 取得模板禁止使用函数列表
            $template_deny_funs = explode(',', $this->config['tpl_deny_func_list']);

            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);

                // 模板函数过滤
                $fun = trim($args[0]);
                if (in_array($fun, $template_deny_funs)) {
                    continue;
                }

                switch (strtolower($fun)) {
                    case 'raw':
                        break;
                    case 'date':
                        $name = 'date(' . $args[1] . ',!is_numeric(' . $name . ')? strtotime(' . $name . ') : ' . $name . ')';
                        break;
                    case 'first':
                        $name = 'current(' . $name . ')';
                        break;
                    case 'last':
                        $name = 'end(' . $name . ')';
                        break;
                    case 'upper':
                        $name = 'strtoupper(' . $name . ')';
                        break;
                    case 'lower':
                        $name = 'strtolower(' . $name . ')';
                        break;
                    case 'format':
                        $name = 'sprintf(' . $args[1] . ',' . $name . ')';
                        break;
                    case 'default': // 特殊模板函数
                        if (false === strpos($name, '(')) {
                            $name = '(isset(' . $name . ') && (' . $name . ' !== \'\')?' . $name . ':' . $args[1] . ')';
                        } else {
                            $name = '(' . $name . ' ?: ' . $args[1] . ')';
                        }
                        break;
                    default: // 通用模板函数
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name    = "$fun($args[1])";
                            } else {
                                $name = "$fun($name,$args[1])";
                            }
                        } else {
                            if (!empty($args[0])) {
                                $name = "$fun($name)";
                            }
                        }
                }
            }

            $this->varFunctionList[$_key]   = $name;
            $varStr                         = $name;
        }
        return $varStr;
    }


    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr 变量数据
     * @return void
     */
    public function parseVar(&$varStr)
    {
        $varStr = trim($varStr);

        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:.][0-9a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {

            while ($matches[0]) {
                $match = array_pop($matches[0]);

                //如果已经解析过该变量字串，则直接返回变量值
                if (isset($this->varParseList[$match[0]])) {
                    $parseStr = $this->varParseList[$match[0]];
                } else {
                    if (strpos($match[0], '.')) {
                        $vars  = explode('.', $match[0]);
                        $first = array_shift($vars);

                        switch ($this->config['tpl_var_identify']) {
                            case 'array': // 识别为数组
                                $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                break;
                            case 'obj': // 识别为对象
                                $parseStr = $first . '->' . implode('->', $vars);
                                break;
                            default: // 自动判断数组或对象
                                $parseStr = '(is_array(' . $first . ')?' . $first . '[\'' . implode('\'][\'', $vars) . '\']:' . $first . '->' . implode('->', $vars) . ')';
                        }
                    } else {
                        $parseStr = str_replace(':', '->', $match[0]);
                    }

                    $this->varParseList[$match[0]] = $parseStr;
                }

                $varStr = substr_replace($varStr, $parseStr, $match[1], strlen($match[0]));
            }
            unset($matches);
        }
    }

    /**
     * 按标签生成正则
     * @access private
     * @param  string $tagName 标签名
     * @return string
     */
    private function getRegex($tagName){
        $regex = '';
        if ('tag' == $tagName) {
            $begin = $this->config['tpl_begin'];
            $end = $this->config['tpl_end'];

            if (strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1) {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>[^' . $end . ']*))' . $end;
            } else {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>(?:(?!' . $end . ').)*))' . $end;
            }
        }
        return '/' . $regex . '/is';
    }
}