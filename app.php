<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/8 15:32
 * @Email: 30191306465@qq.com
 */


/**
 * 应用配置
 */
return [

    'server'    => [
        'type'  => SERVER_TYPE_HTTP
    ],

    //----------------------------------------
    // 异常处理
    //----------------------------------------
    'exception' => [
        '' =>  "\\Mango\\Exception\\ExceptionHandel",
        // webSocket异常处理
        'webSocketController' => "\\Mango\\Exception\\WebSocketControllerException",
    ],


    //----------------------------------------
    // 加载配置
    //----------------------------------------
    'load' => [
        // 是否加载助手函数
        'helper' => true
    ],

    //----------------------------------------
    // Redis 缓存配置
    //----------------------------------------
    'redis' => [
        'connection' => [
            'host'      => '127.0.0.1',
            'port'      => '6379',
            'timeout'   => 5,
            'database'  => 0,
            'password'  => ''
        ],
    ],

    //----------------------------------------
    // 数据库 配置
    //----------------------------------------
    'database' => [
        // 默认数据连接标识
        'default'     => 'mysql',
        // 数据库连接信息
        'connections' => [
            'mysql' => [
                // 数据库类型
                'type'     => 'mysql',
                // 主机地址
                'hostname' => '192.168.137.1',
                // 用户名
                'username' => 'root',
                'password' => 'root',
                // 数据库名
                'database' => 'message',
                // 数据库编码默认采用utf8
                'charset'  => 'utf8mb4',
                // 数据库表前缀
                'prefix'   => '',
                // 数据库调试模式
                'debug'    => true,
                // 是否开启SQL监听（日志）
                'trigger_sql' => false,
            ]
        ],
        'max_active'    => 3,
        'max_wait_time' => 5,
    ],

    //----------------------------------------
    // 日志配置
    //----------------------------------------
    'log' => [
        // 时间记录格式
        'time_format' => 'c',
        // 是否单一文件日志
        'single'      => false,
        // 日志文件大小限制（超出会生成多个文件）
        'file_size'   => 2097152,
        // 日志存储路径
        'path'        => '',
        // 	独立记录的日志级别
        'apart_level' => [],
        // 最大日志文件数
        'max_files'   => 0,
        // JSON格式日志
        'json'        => false,
        // 记录日志级别
        'level'       => [],
        // 	允许日志写入的授权key
        'allow_key'   => [],
        // 是否关闭日志记录
        'close'       => false
    ],
    //----------------------------------------
    // 模板配置
    //----------------------------------------
    'template' => [
        // 标签开始标记
        'tpl_begin'          => '{',
        // 标签结束标记
        'tpl_end'            => '}',
        // 模板缓存路径
        'save_path'          => __DIR__.'/runtime/template_cache',
        // 标签库标签开始标记
        'taglib_begin'       => '{',
        // 标签库标签结束标记
        'taglib_end'         => '}',
        // 内容替换
        'tpl_replace_string' => [],
        // 默认过滤方法 用于普通标签输出
        'default_filter'     => 'htmlentities',
    ]
];