<?php
namespace rap\Aop;

class JoinPoint  {
    private $args;
    private $target;
    private $obj;
    /* @var $method \ReflectionMethod */
    private $method;
    public function __construct($obj,$target,$method,$args){
        $this->args=$args;
        $this->target=$target;
        $this->method=$method;
        $this->obj=$obj;
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
     * 获取被织入的对象
     * @return mixed
     */
    public function getTarget() {
        return $this->target;
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
        return $this->method->invokeArgs($this->target,$args);
    }

}