<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/13 10:36
 * @Email: 30191306465@qq.com
 */

namespace Mango;


use Mango\Component\Singleton;
use Mango\Exception\FileException;

/**
 * 全局配置类
 * Class Config
 * @package Mango
 */
class Config {

    use Singleton;

    protected $data = [];

    private function __construct(){}


    public function get($name = '',$default = null){


        $config = $this->data;
        if (empty($name))
            return $config;

        if (strpos($name,'.') === false)
            return $config[$name] ?? $default;

        $name = explode('.',$name);

        foreach ($name as $val){
            if (isset($config[$val])){
                $config = $config[$val];
            }else{
                return $default;
            }
        }
        return $config;
    }


    public function set(string $name,$val){
        $this->data[$name] = $val;
    }

    public function clear(){
        $this->data = [];
    }

    public function load(string $filePath){
        if (is_file($filePath)) {
            $confData = require_once $filePath;
            if (is_array($confData) && !empty($confData)) {
                $basename = strtolower(basename($filePath, '.php'));
                $this->set($basename,$confData);
            }
        }else{
            throw new FileException("file not exist: {$filePath}");
        }
    }
    public static function __make(){
        return self::getInstance();
    }
}