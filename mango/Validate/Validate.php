<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/5/29 0:48
 * @Email : 30191306465@qq.com
 */

namespace Mango\Validate;


use Closure;

class Validate{

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 验证失败信息
     * @var array
     */
    protected $message = [];

    /**
     * 错误信息
     * @var array
     */
    protected $error = [];

    /**
     * 批量验证
     * @var bool
     */
    protected $batch = true;

    /**
     * 验证规则别名
     * @var string[]
     */
    protected $alias = [
        '>=' => 'egt',
        '>'  => 'gt',
        '='  => 'eq',
        '<'  => 'lt',
        '<=' => 'lte'
    ];

    /**
     * 数据
     * @var array
     */
    public $data = [];

    /**
     * 正则
     * @var string[]
     */
    protected $regx = [
        // 匹配邮箱
        'email'     => '/^([a-z0-9_]+)(\.[a-z0-9_]+)*@([a-z0-9_]+)(\.[a-z0-9_]+)*(\.[a-z]{2,4})$/i',
        // 匹配手机号
        'mobile'    => '/^1[3-9]\d{9}$/',
    ];

    /**
     * 添加字段验证规则
     * @access protected
     * @param string|array $name 字段名称或者规则数组
     * @param mixed        $rule 验证规则
     * @return $this
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = $name + $this->rule;
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * 添加字段提示文本
     * @param string|array  $field 字段名称或者字段提示文本数组
     * @param mixed         $value
     * @return $this
     */
    public function message($field,$value = ''){
        if (is_array($field))
            $this->message = array_merge($this->message,$field);
        else
            $this->message[$field] = $value;
        return $this;
    }

    /**
     * 添加正则规则
     * @param string|array  $rule   规则名称或数组
     * @param mixed         $value  正则表达式
     * @return $this
     */
    public function regx($rule,$value){
        if (is_array($rule))
            $this->regx = array_merge($this->regx,$rule);
        else
            $this->regx[$rule] = $value;
        return $this;
    }

    /**
     * 是否批量
     * @param bool $is
     * @return $this
     */
    public function batch(bool $is){
        $this->batch = $is;
        return $this;
    }

    /**
     * 效验
     * @param array $data   需要数据
     * @param array $rules  效验规则 如果存在会忽略 $this->rule 规则
     * @return bool
     */
    public function check(array $data,array $rules = []):bool {

        $this->error = [];
        if (empty($rules)) {
            // 读取验证规则
            $rules = $this->rule;
        }
        foreach ($rules as $key => $rule) {
            // field => 'rule1|rule2...' field => ['rule1','rule2',...]

            // 获取数据 支持二维数组
            $value = $this->getDataValue($data, $key);

            // 字段验证
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
            } else {
                $result = $this->checkItem($key, $value, $rule, $data);
            }

            if (true !== $result) {
                // 没有返回true 则表示验证失败
                if (!empty($this->batch)) {
                    // 批量验证
                    $this->error[$key] = $result;
                }  else {
                    $this->error = $result;
                    return false;
                }
            }
        }
        return empty($this->error);
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError(){
       return $this->error;
    }

    /**
     * 验证数据
     * @param string $field 数据字段
     * @param mixed  $value 数据值
     * @param mixed  $rules 规则
     * @param array  $data  数据
     * @return bool|string
     */
    protected function checkItem(string $field,$value,$rules,array $data){

        if (is_string($rules))
            $rules = explode('|',$rules);
        foreach ($rules as $key => $rule){

            if ($rule instanceof Closure){
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            }else{
                [$type, $rule, $info] = $this->getValidateType($key, $rule);
                if ($info == 'has' || 'require' == $info || (!is_null($value) && '' !== $value)) {
                    $result = call_user_func_array([$this, $type], [$value, $rule, $data, $field]);
                } else {
                    $result = true;
                }
            }
            if ($result !== true){
                return $this->getMsg($field,$info);
            }
        }
        return true;
    }

    /**
     * 获取错误信息
     * @param $field
     * @param $rule
     * @return string
     */
    protected function getMsg($field,$rule): string{

        foreach ($this->message as $key => $value){
            $temp = explode(',',(string) $key);
            if (in_array($field,$temp) || in_array($field . '.' .$rule,$temp)){
                return (string) $value;
            }
        }
        return  "{$rule} {$field}";
    }



    /**
     * 获取当前验证类型及规则
     * @access public
     * @param mixed $key    键
     * @param mixed $rule   规则
     * @return array
     */
    protected function getValidateType($key, $rule): array
    {
        // 判断验证类型
        if (!is_numeric($key)) {
            if (isset($this->alias[$key])) {
                // 判断别名
                $key = $this->alias[$key];
            }
            // 验证类型,规则,信息
            return [$key, $rule, $key];
        }

        if (strpos($rule, ':')) {
            [$type, $rule] = explode(':', $rule, 2);
            if (isset($this->alias[$type])) {
                // 判断别名
                $type = $this->alias[$type];
            }
            $info = $type;
        } elseif (method_exists($this, $rule)) {
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }

    /**
     * 验证
     * @param mixed $value  字段值
     * @param string $rule  验证规则
     * @param array  $data  数据
     * @param string $field 字段
     * @return bool
     */
    public function is($value, string $rule, array $data = [],string $field = ''){

        switch ($rule){
            case 'require':
                // 必须
                $result = !empty($value) || '0' == $value;
                break;
            case 'has':
                // 存在
                $result = $this->getDataValue($data,$field) !== null;
                break;
            case 'accepted':
                // 接受
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime($value);
                break;
            case 'activeUrl':
                // 是否为有效的网址
                $result = checkdnsrr($value);
                break;
            case 'boolean':
            case 'bool':
                // 是否为布尔值
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'number':
                $result = ctype_digit((string) $value);
                break;
            case 'alphaNum':
                $result = ctype_alnum($value);
                break;
            case 'array':
                // 是否为数组
                $result = is_array($value);
                break;
            case 'int':
                // 整型
                $result = is_int($value);
                break;
            case 'float':
                // 浮点
                $result = is_float($value);
                break;
            default:
                if ($rule instanceof Closure){
                    // 闭包
                    $result = $rule($value,$rule,$data,$field);
                }else if(method_exists($this,$rule)){
                    // 方法
                    $result = call_user_func_array([$this,$rule],[$value,$rule,$data,$field]);
                }else if (isset($this->regx[$rule])){
                    // 正则
                    $result = preg_match($this->regx[$rule],$value);
                }else{
                    // 找不到...
                    throw new \InvalidArgumentException("validate rule not exits: {$rule}");
                }
                break;
        }
        return $result;
    }

    /**
     * 大于等于
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public function egt($value,$rule,array $data = []): bool{
        return $value >= $this->getDataValue($data,$rule);
    }

    /**
     * 大于
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public function gt($value,$rule,array $data = []): bool{
        return $value > $this->getDataValue($data,$rule);
    }

    /**
     * 大于
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public function eq($value,$rule,array $data = []): bool{
        return $value == $this->getDataValue($data,$rule);
    }

    /**
     * 小于
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public function lt($value,$rule,array $data = []): bool{
        return $value < $this->getDataValue($data,$rule);
    }
    /**
     * 小于等于
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public function lte($value,$rule,array $data = []): bool{
        return $value <= $this->getDataValue($data,$rule);
    }


    /**
     * 验证是否在范围内
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public function in($value, $rule): bool
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证是否不在某个范围
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public function notIn($value, $rule): bool
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }
    /**
     * 数据长度验证
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public function length($value,$rule): bool{
        if (is_array($value)){
            $length = count($value);
        }else{
            $length = mb_strlen((string) $value);
        }
        if (is_string($rule) && strpos($rule,',')){
            [$min,$max] = explode(',',$rule);
            return $length >= $min && $length <= $max;
        }
        return $value == $length;
    }

    /**
     * 验证邮箱格式
     * @param string $value 字段值
     * @return bool
     */
    public function email($value): bool {
        return preg_match($this->getRegx('email'),$value);
    }

    /**
     * 验证手机号
     * @param string $value 验证手机号
     * @return bool
     */
    public function mobile($value): bool{
        return preg_match($this->getRegx('mobile'),$value);
    }

    /**
     * 验证IP地址
     * @param string $value    字段值
     * @return bool
     */
    public function ip($value): bool {
        return (bool) filter_var($value,FILTER_VALIDATE_IP);
    }

    /**
     * 验证IPv4地址
     * @param string $value    字段值
     * @return bool
     */
    public function ipv4($value): bool {
        return (bool) filter_var($value,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
    }
    /**
     * 验证为IPv6地址
     * @param string $value    字段值
     * @return bool
     */
    public function ipv6($value): bool {
        return (bool) filter_var($value,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6);
    }

    /**
     * between验证数据
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public function between($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        [$min, $max] = $rule;

        return $value >= $min && $value <= $max;
    }

    /**
     * 获取正则
     * @param string $name
     * @return string
     */
    public function getRegx(string $name){
        return $this->regx[$name];
    }
    /**
     * 获取数据值
     * @param array  $data 数据
     * @param string $key  数据标识 支持二维
     * @return mixed
     */
    protected function getDataValue(array $data, $key)
    {
        if (is_numeric($key)) {
            $value = $key;
        } elseif (is_string($key) && strpos($key, '.')) {
            // 支持多维数组验证
            foreach (explode('.', $key) as $key) {
                if (!isset($data[$key])) {
                    $value = null;
                    break;
                }
                $value = $data = $data[$key];
            }
        } else {
            $value = $data[$key] ?? null;
        }

        return $value;
    }
}