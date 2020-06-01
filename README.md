
Mango Swoole
====

MangoSwoole 是一款基于Swoole Server 开发的常驻内存型的PHP框架


组件
* HTTP 服务器
* Websocket 服务器
* 模板渲染
* 协程ORM(ThinkPHP-ORM)
* 协程数据库连接池
* 协程Redis连接池
* 自定义进程
* 定时器
* 协程通用链接池
* 路由
* 验证器
* 中间件

### 中间件
目前暂时只支持HTTP控制器中间件
+ HTTP控制器中间件使用
```php
<?php
/**
 * 定义中间件
 */
class Middleware extends \Mango\Http\Middleware\Middleware{

    public function handler(\Mango\Http\Request $request,\Closure $next){
        parent::handler($request,$next); 
    }
}
class A extends \Mango\Http\Controller{
    protected $middleware = [
        Middleware::class,...
    ];
};
class B extends \Mango\Http\Controller{

    protected function initialize(){
        $this->middleware = function(Mango\Http\Request $request){
            $middleware = Middleware::class;
            // 执行那些方法可以调用这个中间件
            // 获取执行控制器与方法名可用 $request->controller()、$request->action() 获取
            $action = ['*']; // or 'a,b,c,d'
            // 参数 
            $argv = [];
            return [$middleware,$action,$argv];
        };
    }
}
class C extends \Mango\Http\Controller{
    protected $middleware = [
        [
            'action'        => 'a,b,e',
            'middleware'    => Middleware::class,
            'argv'          => ['a','b']
        ],
        /**
         * 相当于
         * [
         *    'middleware'    => Middleware::class,
         *    'action'        => ['*'],
         *    'argv'          => []
         *  ]
         */
        Middleware::class,
        // 也可以使用 Function 返回格式和 class B一样
    ];
}
// 以下都是合法的
// $middleware = string[] or string
// $middleware = function[] or function
// $middleware = array[array|function|string]
?>
```

### 开发中
