<?php
namespace rap\Aop;
use rap\Ioc;


class   EventHook  {

    private static $wares=[];

    /**
     * 添加事件
     * @param $name
     * @param $class
     * @param $action
     */
    public static function add($name, $class, $action) {
        if(!$action){
            $action="on".ucfirst($name);
        }
        $info=array('name'=>$name,'class'=>$class,"action"=>$action);
        if(!key_exists($name,static::$wares)){
            static::$wares[$name] = array();
        }
        static::$wares[$name][]=$info;
    }

    /**
     * 触发事件
     * @param $name
     * @param $args
     */
    public static function trigger($name,$args) {
        if(array_key_exists($name,static::$wares)){
            $infos=static::$wares[$name];
            if($infos){
                foreach ($infos as $info){
                    $module=Ioc::get($info['class']);
                    if($info['action']){
                        static::doAction($module,$info['action'],$args);
                    }
                }
            }
        }
    }

    /**
     * 执行任务
     * @param $module
     * @param $action
     * @param $args
     * @return mixed
     */
    private static function doAction($module,$action,$args){
        return  $module->$action($args);
    }

}