<?php
namespace rap\ioc;


use rap\aop\Aop;
use rap\db\Connection;
use rap\swoole\pool\ResourcePool;
use rap\swoole\pool\PoolAble;
use rap\swoole\pool\PoolTrait;

class Ioc {

    //所有对象
    static private $instances;
    //类衍射定义
    static private $beansConfig = [];

    //初始化对象时用于存储
    static private $injectBeans = [];


    public static function clear() {
        self::$instances = [];
        self::$beansConfig = [];
        self::$injectBeans = [];
    }

    /**
     * 根据类名,别名获取对象
     *
     * @param  string $nameClass 自名称
     *
     * @return mixed
     */
    public static function get($nameClass = null) {
        //判断是否有实例
        if (isset(static::$instances[ $nameClass ]) && static::$instances[ $nameClass ]) {
            return static::$instances[ $nameClass ];
        }
        return self::beanCreate($nameClass);
    }

    public static function getRealClass($nameClass = null) {
        if (isset(static::$beansConfig[ $nameClass ]) && static::$beansConfig[ $nameClass ]) {
            //构造对象
            /* @var $beanDefine BeanDefine */
            $beanDefine = static::$beansConfig[ $nameClass ];
            return $beanDefine->ClassName;
        }
        return $nameClass;
    }


    public static function beanCreate($nameClass, $instance = true) {
        $closure = null;
        $beanClassName = $nameClass;
        //判断是否有配置
        if (isset(static::$beansConfig[ $nameClass ]) && static::$beansConfig[ $nameClass ]) {
            //构造对象
            /* @var $beanDefine BeanDefine */
            $beanDefine = static::$beansConfig[ $nameClass ];
            $closure = $beanDefine->closure;
            $beanClassName = $beanDefine->ClassName;
        }
        $bean = Aop::warpBean($beanClassName, $nameClass);
        //连接池类型的不需要在容器托管
        if ($instance) {
            static::$instances[ $nameClass ] = $bean;
        }
        static::prepareBean($bean);
        if ($closure) {
            $closure($bean);
        }
        return $bean;

    }

    /**
     * 初始化对象
     *
     * @param $bean
     */
    private static function prepareBean($bean) {
        $class = new \ReflectionClass(get_class($bean));
        if ($class->hasMethod('_initialize')) {
            self::invokeWithIocParams($bean, "_initialize");
            static::$injectBeans[] = $bean;
            if (static::$injectBeans[ 0 ] === $bean) {
                for ($i = count(static::$injectBeans) - 1; $i > -1; $i--) {
                    $class = new \ReflectionClass(get_class(static::$injectBeans[ $i ]));
                    if ($class->hasMethod('_prepared')) {
                        static::$injectBeans[ $i ]->_prepared();
                    }
                }
                static::$injectBeans = array();
            }
        }
    }


    /**
     * 绑定对象
     *
     * @param $nameOrClazz string
     * @param $toClazz
     * @param $closure
     */
    public static function bind($nameOrClazz, $toClazz, \Closure $closure = null) {
        unset(static::$instances[ $nameOrClazz ]);
        static::$beansConfig[ $nameOrClazz ] = new BeanDefine($toClazz, $closure);
    }

    /**
     * 调用方法 并绑定对象
     *
     * @param $obj    mixed 对象
     * @param $method string 方法名
     *
     * @return mixed
     */
    public static function invokeWithIocParams($obj, $method) {
        $method = new \ReflectionMethod(get_class($obj), $method);
        $args = [];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            foreach ($params as $param) {
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    $bean = Ioc::get($className);
                    if (!$bean) {
                        $args[] = method_exists($className, 'instance') ? $className::instance() : new $className();
                    } else {
                        $args[] = $bean;
                    }
                } else {
                    $args[] = null;
                }
            }
        }
        $val = $method->invokeArgs($obj, $args);
        return $val;
    }

    /**
     * 设置单例
     *
     * @param $name
     * @param $bean
     */
    public static function instance($name, $bean) {
        static::$instances[ $name ] = $bean;
    }

    /**
     * 获取实例 如果实例不存在不会去构建
     * @param $name
     */
    public static function getInstance($name){
        return static::$instances[ $name ];
    }

}