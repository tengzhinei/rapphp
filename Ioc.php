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
        if(self::$config[$who]&&self::$config[$who][$name]){
            $def=self::$config[$who][$name];
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
            $config= self::$beanInClazzConfig[$who];
            if($config){
                if($config[$name]){
                    $name=$config[$name];
                }
            }
        }
        //判断是否有实例
       if(isset(self::$instances[$name])&&self::$instances[$name]){
           return self::$instances[$name];
       }
       //判断是否有配置
       if(isset(self::$beansConfig[$name])&&self::$beansConfig[$name]){
           //构造对象
           $bean=new self::$beansConfig[$name]();
       }else{
           //没有配置直接初始化需要的类(接口不可以)
           $bean=new $who();
       }
       self::prepareBean($bean);
        $bean=self::warpBean($bean);
        self::$instances[$name]=$bean;
        return self::$instances[$name];
    }

    /**
     * 初始化对象
     * @param $bean
     */
    private static function prepareBean($bean){
        $class  =   new \ReflectionClass(get_class($bean));
        if($class->hasMethod('_initialize')) {
            $bean->_initialize();
            if(self::$injectBeans[0]===$bean){
                for ($i=count(self::$injectBeans)-1;$i>-1;$i--){
                    $class  =   new \ReflectionClass(get_class(self::$injectBeans[$i]));
                    if($class->hasMethod('_prepared')) {
                        self::$injectBeans[$i]->_prepared();
                    }
                }
                self::$injectBeans=array();
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
        self::$beansConfig[$nameOrClazz]=$toClazz;
    }

    /**
     * 设置配置
     * @param $bean
     * @param $array
     */
    public static function setConfig($bean,$array){
        self::$config[$bean]=$array;

    }

    /**
     * 设置类中的衍射
     * @param $bean
     * @param $name
     * @param $toName
     */
    public static function bindSpecial($bean, $name, $toName){
        if(!self::$beanInClazzConfig[$bean]){
            self::$beanInClazzConfig[$bean]=array();
        }
        self::$beanInClazzConfig[$bean][$name]=$toName;
    }

    /**
     * 设置对象
     * @param $name
     * @param $bean
     */
    public  static  function instance($name,$bean){
        self::$instances[$name]=$bean;
    }


//    /**
//     * 触发方法 会自动传入 如果方法有依赖会自动传入 因为aop的原因注入的对象类型可能会有变化 所以这个失效
//     * @param $bean
//     * @param $methodName
//     * @param array $parseArgs
//     */
//    public static function invokeWithDepend($bean,$methodName,$parseArgs=array()){
//        if(method_exists($bean,$methodName)){
//            $method =   new \ReflectionMethod($bean, $methodName);
//            $params =  $method->getParameters();
//            $args=array();
//            foreach ($params as $param){
//                //用于初始化的方法支持注入参数
//                if($param->getName()=='config'&&$methodName=='_initialize'){
//                    $args[]=self::$config[get_class($bean)];
//                }else if($parseArgs[$param->getName()]){
//                    $args[]=$parseArgs[$param->getName()];
//                } else{
//                    $dependClass=$param->getClass();
//                    $dependClassName=$dependClass->getName();
//                    $depend=Ioc::get(get_class($bean),$dependClassName);
//                    $args[]=$depend;
//                }
//            }
//            $method->invokeArgs($bean,$args);
//        }
//    }

}