<?php
namespace rap\aop;

/**
 * 拦截点
 */
class JoinPoint {

    /**
     * 参数
     * @var mixed
     */
    private $args;

    /**
     * 被拦截的对象
     * @var object
     */
    private $obj;

    /**
     * 回调方法
     *
     * @var  \Closure
     */
    private $callback;

    /**
     * 被拦截的方法
     * @var \ReflectionMethod
     */
    private $method;

    public function __construct($obj, $method, $args, $callback) {
        $this->args = $args;
        $this->method = $method;
        $this->obj = $obj;
        $this->callback = $callback;
    }

    /**
     * 获取方法参数
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }

    public function setArgs($args) {
        $this->args = $args;
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
     *
     * @param $args
     *
     * @return mixed
     */
    public function process($args) {
        $callback = $this->callback;
        return $callback($args);
    }

}