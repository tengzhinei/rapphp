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
        if(!$action)$action="on".ucfirst($name);
        $info=array('name'=>$name,'class'=>$class,"action"=>$action);
        if(!self::$wares[$name])self::$wares[$name] = array();
        self::$wares[$name][]=$info;
    }

    /**
     * 触发事件
     * @param $name
     * @param $args
     */
    public static function trigger($name,$args) {
        echo $name;
        if(array_key_exists($name,self::$wares)){
            $infos=self::$wares[$name];
            if($infos){
                foreach ($infos as $info){
                    $module=Ioc::get($info['class']);
                    if($info['action']){
                        self::doAction($module,$info['action'],$args);
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