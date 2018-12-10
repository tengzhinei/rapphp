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
    private $argNames;

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

    private $original_Class;

    public function __construct($obj, $method, $argNames, $args, $original_Class, $callback) {
        $this->args = $args;
        $this->argNames = $argNames;
        if(is_string($method)){
            $this->method =  new \ReflectionMethod(get_class($obj), $method);;
        }else{
            $this->method=$method;
        }
        $this->obj = $obj;
        $this->original_Class = $original_Class;
        $this->callback = $callback;
    }



    /**
     * 获取方法参数
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }


    public function getOriginalClass() {
        return $this->original_Class;
    }

    public function setArgs($args) {
        $this->args = $args;
    }

    public function getArgMap(){
        return array_combine($this->argNames,$this->args);
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