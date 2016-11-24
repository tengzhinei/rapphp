<?php
namespace rap;

use rap\Aop\BeanWarp;

class Ioc  {
    //配置
    static private $config;
    //所有对象
    static private $instances;
    //类衍射定义
    static private $beansConfig;
    //类中衍射
    static private $beanInClazzConfig;
    //初始化对象时用于存储
    static private $injectBeans;

    /**
     * 返回配置信息
     * @param $who
     * @param $name
     * @param $def
     * @return mixed
     */
    public static function config($who,$name,$def=null){
        if(static::$config[$who]&&static::$config[$who][$name]){
            $def=static::$config[$who][$name];
        }
        return $def;
    }

    /**
     * 根据类名,别名获取对象
     * @param $who
     * @param null $name
     * @return mixed
     */
    public static function get($who,$name=null){
        //与类名无关的
        if(!$name){
            $name=$who;
        }else{
            //判断类中是否有衍射
            $config= static::$beanInClazzConfig[$who];
            if($config&&$config[$name]){
                $name=$config[$name];
            }
        }
        //判断是否有实例
       if(isset(static::$instances[$name])&&static::$instances[$name]){
           return static::$instances[$name];
       }
       //判断是否有配置
       if(isset(static::$beansConfig[$name])&&static::$beansConfig[$name]){
           //构造对象
           $bean=new static::$beansConfig[$name]();
       }else{
           //没有配置直接初始化需要的类(接口不可以)
           $bean=new $who();
       }
        //必须先赋值
        static::$instances[$name]=$bean;
        //再初始化
        static::prepareBean($bean);
        //再包装
        $bean=static::warpBean($bean);
        return static::$instances[$name];
    }

    /**
     * 初始化对象
     * @param $bean
     */
    private static function prepareBean($bean){
        $class  =   new \ReflectionClass(get_class($bean));
        if($class->hasMethod('_initialize')) {
            $bean->_initialize();
            if(static::$injectBeans[0]===$bean){
                for ($i=count(static::$injectBeans)-1;$i>-1;$i--){
                    $class  =   new \ReflectionClass(get_class(static::$injectBeans[$i]));
                    if($class->hasMethod('_prepared')) {
                        static::$injectBeans[$i]->_prepared();
                    }
                }
                static::$injectBeans=array();
            }
        }
    }

    /**
     * 包装bean
     * @param $bean
     * @return BeanWarp
     */
    private static function warpBean($bean){
       if(Aop::needWarp($bean)){
           $bean=new BeanWarp($bean);
       }
       return $bean;
    }
    /**
     * 绑定对象
     * @param $nameOrClazz
     * @param $toClazz
     */
    public static function bind($nameOrClazz,$toClazz){
        static::$beansConfig[$nameOrClazz]=$toClazz;
    }

    /**
     * 设置配置
     * @param $bean
     * @param $array
     */
    public static function setConfig($bean,$array){
        static::$config[$bean]=$array;

    }

    /**
     * 设置类中的衍射
     * @param $bean
     * @param $name
     * @param $toName
     */
    public static function bindSpecial($bean, $name, $toName){
        if(!static::$beanInClazzConfig[$bean]){
            static::$beanInClazzConfig[$bean]=array();
        }
        static::$beanInClazzConfig[$bean][$name]=$toName;
    }

    /**
     * 设置对象
     * @param $name
     * @param $bean
     */
    public  static  function instance($name,$bean){
        static::$instances[$name]=$bean;
    }



}