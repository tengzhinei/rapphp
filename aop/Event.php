<?php
namespace rap\aop;

use rap\ioc\Ioc;


class Event {




    /**
     * 所有事件
     * @var array
     */
    private static $events = [];

    /**
     * 添加事件
     *
     * @param $name
     * @param $class
     * @param $action
     */
    public static function add($name, $class, $action=null) {
        if (!$action) {
            $action = "on" . ucfirst($name);
        }
        $info = array('name' => $name, 'class' => $class, "action" => $action);
        if (!key_exists($name, static::$events)) {
            static::$events[ $name ] = array();
        }
        static::$events[ $name ][] = $info;
    }

    /**
     * 触发事件
     *
     * @param $name
     */
    public static function trigger($name) {
        $args=func_get_args();
        array_shift($args);
        if (array_key_exists($name, static::$events)) {
            $infos = static::$events[ $name ];
            if ($infos) {
                foreach ($infos as $info) {
                    $clazz=$info[ 'class' ];
                    if($clazz instanceof \Closure){
                        call_user_func_array($clazz, $args);
                    }else{
                        $module = Ioc::get($info[ 'class' ]);
                        $method = new \ReflectionMethod(get_class($module), $info['action']);
                        $method->invokeArgs($module, $args);
                    }

                }
            }
        }
    }


}