<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/12/2 9:22
 * @Email: 30191306465@qq.com
 */

require_once ROOT_PATH . 'MangoMain.php';

/**
 * 常量定义
 */
// 框架目录
define('FRAMEWORK_PATH',__DIR__ . DIRECTORY_SEPARATOR);
// 程序目录
define('APP_PATH',ROOT_PATH . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR);
// HTTP 服务
define('SERVER_TYPE_HTTP',1);
// WEBSOCKET 服务
define('SERVER_TYPE_WEBSOCKET',2);
// 运行时目录
define('RUNTIME_PATH',ROOT_PATH . 'runtime' . DIRECTORY_SEPARATOR);
// 程序启动时PID写入文件位置
define('PID_FILE_POSITION',RUNTIME_PATH . 'app.pid');
// 配置文件
define('CONF_FILE',ROOT_PATH . 'app.php');

/**
 * 执行程序初始化
 */
\Mango\App::getInstance()->run();