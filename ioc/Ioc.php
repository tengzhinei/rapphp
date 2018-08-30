<?php
namespace rap\ioc;


use rap\aop\Aop;

class Ioc  {

    //所有对象
    static private $instances;
    //类衍射定义
    static private $beansConfig=[];

    //初始化对象时用于存储
    static private $injectBeans=[];

    public static function clear(){
        self::$instances=[];
        self::$beansConfig=[];
        self::$injectBeans=[];
    }
    /**
     * 根据类名,别名获取对象
     * @param string $who 类名或别名
     * @param  string $name 自名称
     * @return mixed
     */
    public static function get($who,$name=null){
        //与类名无关的
        if(!$name){
            $name=$who;
        }

        //判断是否有实例
        if(isset(static::$instances[$name])&&static::$instances[$name]){
            return static::$instances[$name];
        }

        $closure=null;

       //判断是否有配置
        if(isset(static::$beansConfig[$name])&&static::$beansConfig[$name]){
            //构造对象
            /* @var $beanDefine BeanDefine  */
            $beanDefine = static::$beansConfig[$name];
            $closure=$beanDefine->closure;
            $bean=Aop::warpBean($beanDefine->ClassName);
        }else{
            //没有配置直接初始化需要的类(接口不可以)
            $bean=Aop::warpBean($who);
        }
        //必须先赋值
        static::$instances[$name]=$bean;
        //再初始化
        static::prepareBean($bean);
        //初始化回调
        if($closure){
            $closure($bean);
        }
        return static::$instances[$name];
    }

    /**
     * 初始化对象
     * @param $bean
     */
    private static function prepareBean($bean){
        $class  =   new \ReflectionClass(get_class($bean));
        if($class->hasMethod('_initialize')) {
            self::invokeWithIocParams($bean,"_initialize");
            static::$injectBeans[]=$bean;
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
     * 绑定对象
     * @param $nameOrClazz string
     * @param $toClazz
     * @param $closure
     */
    public static function bind($nameOrClazz,$toClazz,\Closure $closure=null){
        unset(static::$instances[$nameOrClazz]);
        static::$beansConfig[$nameOrClazz]= new BeanDefine($toClazz,$closure);
    }

    /**
     * 调用方法 并绑定对象
     * @param $obj mixed 对象
     * @param $method string 方法名
     * @return mixed
     */
    public static function invokeWithIocParams($obj, $method)
    {
        $method =   new \ReflectionMethod(get_class($obj), $method);
        $args=[];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            foreach ($params as $param) {
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    $bean= Ioc::get($className);
                    if(!$bean){
                        $args[] = method_exists($className, 'instance') ? $className::instance() : new $className();
                    }else{
                        $args[]=$bean;
                    }
                }else{
                    $args[]=null;
                }
            }
        }
        $val= $method->invokeArgs($obj,$args);
        return $val;
    }




}