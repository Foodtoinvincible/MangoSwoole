<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time: 2019/11/11 14:42
 * @Email: 30191306465@qq.com
 */

namespace Mango\Pool;



abstract class AbstractContext{

    /**
     * 状态
     * @var bool
     */
    protected $status = false;

    /**
     * 绑定协程id
     * @var null
     */
    protected $coroutineId = null;


    /**
     * 是否销毁
     * @var bool
     */
    protected $isWhether = false;

    /**
     * 上次使用时间
     * @var null
     */
    protected $lastUseTime = 0;


    /**
     * 连接对象
     */
    protected $conn = null;

    /**
     * Context constructor.
     * @param array $config
     */
     public function __construct(array $config){

        $this->conn = $this->create($config);
        $this->lastUseTime = time();
     }

     abstract public function create(array $conf);

    /**
     * 标记销毁
     * @return void
     */
     public function whether(): void{
         $this->isWhether = true;
     }

    /**
     * 是否销毁
     * @return bool
     */
     public function isWhether() :bool{
         return $this->isWhether;
     }

    /**
     * 占用连接
     * @param int $coroutineId 协程id
     * @return mixed
     */
    public function get($coroutineId = 0){
        $this->status = true;

        if (!empty($coroutineId))
            $this->bindCoroutine($coroutineId);

        return $this->conn;
    }

    public function bindCoroutine(int $cid){
        $this->coroutineId = $cid;
    }
    /**
     * 获取绑定的协程id
     * @return null|int
     */
    public function getBindCoroutineId(){
        return $this->coroutineId;
    }

    /**
     * 设置状态
     * @param bool $status
     */
    public function setStatus(bool $status = true): void{
        $this->status = $status;
    }


    /**
     * 获取状态
     * @return bool
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * 回收对象，不是释放
     */
    public function recover(): void{
        $this->status = false;
        $this->coroutineId = null;
        $this->lastUseTime = time();
        $this->rollback();
    }

    /**
     * 覆写该方法，完成回收对象时处理
     */
    abstract protected function rollback();

    /**
     * 释放连接
     */
    public function close(){
        $this->status = null;
        $this->coroutineId = null;
        $this->lastUseTime = null;
        $this->release();
        $this->conn = null;
    }

    /**
     * 覆写该方法 完成释放对象处理
     */
    abstract protected function release();

    public function getLastUseTime(){
        return $this->lastUseTime;
    }
}