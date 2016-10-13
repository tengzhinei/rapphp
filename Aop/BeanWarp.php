<?php
namespace rap\Aop;
use rap\Ioc;
use rap\Aop;

/**
 * 为bean添加包装器
 * @package Dh\Aop
 */
class   BeanWarp  {

    private $bean;

    public function __construct($bean){
        $this->bean=$bean;
    }

    /**
     * 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name,$value) {
        // 设置数据对象属性
        $this-> $bean->$name =   $value;
    }

    /**
     * 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name) {
        return $this-> $bean->$name;
    }

    /**
     * 方法调用
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function __call($methodName,$args) {
        $beanwarp=$this->bean;
        $method =   new \ReflectionMethod($beanwarp, $methodName);
        $point=new JoinPoint($this,$beanwarp,$method,$args);
        $action=Aop::getAroundActions(get_class($beanwarp),$methodName);
        //包围操作只可以添加一个
        if($action){
            if($action['call']){
                return  $action['call']($point);
            }
            return  Ioc::get($action['class'])->$action['action']($point);
        }
        //前置操作
        $actions= Aop::getBeforeActions(get_class($beanwarp),$methodName);
        foreach ($actions as $action){
            if($action['call']){
                return  $action['call']($point);
            }else{
                Ioc::get($action['class'])->$action['action']($point);
            }
        }
        $val=$method->invokeArgs($beanwarp,$point->getArgs());
        //后置操作
        $actions= Aop::getAfterActions(get_class($beanwarp),$methodName);
        foreach ($actions as $action){
            if($action['call']){
                $val =   $action['call']($point,$val);
            }else {
                $val = Ioc::get($action['class'])->$action['action']($point, $val);
            }
        }
        return $val;
    }

    public function __callStatic($methodName,$args) {
        return $this->__call($methodName,$args);
    }


    /**
     * 获取被包装的类
     * @return
     */
    public function getWarpBean(){
        return $this->bean;
    }

}