<?php
namespace rap\Aop;

class JoinPoint  {
    private $args;
    private $obj;
    /**
     * @var $callback \Closure
     */
    private $callback;
    /* @var $method \ReflectionMethod */
    private $method;
    public function __construct($obj,$method,$args,$callback){
        $this->args=$args;
        $this->method=$method;
        $this->obj=$obj;
        $this->callback=$callback;
    }

    /**
     * 获取方法参数
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }

    public function  setArgs($args){
        $this->args=$args;
    }

    /**
     * 获取方法签名
     * @return \ReflectionMethod
     */
    public function getMethod() {
        return $this->method;
    }



    /**
     * 获取织入后的对象
     */
    public function getObj() {
        return $this->obj;
    }

    /**
     * 执行方法
     * @param $args
     * @return mixed
     */
    public function process($args){
        $callback=$this->callback;
        return $callback($args);
    }

}