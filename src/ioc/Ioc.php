<?php
namespace rap\ioc;


use Psr\Container\ContainerInterface;
use rap\aop\Aop;
use rap\cache\Cache;
use rap\ioc\scope\PrototypeScope;
use rap\ioc\scope\RequestScope;
use rap\ioc\scope\SessionScope;
use rap\ioc\scope\WorkerScope;
use rap\session\RedisSession;
use rap\swoole\Context;

/**
 * Ioc 容器管理
 * @author: 藤之内
 */
class Ioc {

    /**
     * 所有静态对象
     * @var array
     */
    static private $instances = [];

    /**
     * 所有存在 worker的对象
     * @var array
     */
    static private $workScopeInstances = [];

    /**
     * 类衍射定义
     * @var array
     */
    static private $beansConfig = [];

    /**
     * 初始化对象时用于存储
     * @var array
     */
    static private $injectBeans = [];

    /**
     * 所有对象构造器
     * @var array
     */
    public static $preparers = [];


    /**
     * 根据类名,别名获取对象
     * 对象的作用域为context
     *
     * @param null $nameClass
     *
     * @return mixed|object
     */
    private static function getByContext($nameClass) {
        $bean = Context::get('Ioc_' . $nameClass);
        if ($bean) {
            return $bean;
        }
        $bean = self::beanCreate($nameClass);
        Context::set('Ioc_' . $nameClass, $bean);
        return $bean;
    }


    /**
     * 根据类名,别名获取对象
     * 对象的作用域为 session
     *
     * @param $clazzOrName
     *
     * @return mixed|object
     */
    private static function getBySession($clazzOrName) {
        $request = request();
        $bean = Context::get('Ioc_' . $clazzOrName);
        if ($bean) {
            return $bean;
        }
        if (!$request) {
            $bean = Ioc::beanCreate($clazzOrName);
        } else {
            $session_id = $request->session()->sessionId();
            $cache_key = 'scope_session_' . $clazzOrName . $session_id;
            $cache = Cache::getCache(RedisSession::REDIS_CACHE_NAME);
            $bean = $cache->get($cache_key, null);
            if ($bean) {
                $cache->expire($cache_key, 60 * 30);
            } else {
                $bean = Ioc::beanCreate($clazzOrName);
                $cache->set($cache_key, $bean, 60 * 30);
            }
        }
        Context::set('Ioc_' . $clazzOrName, $bean);
        return $bean;
    }

    /**
     * 根据类名,别名获取对象
     * 自动判定对象的作用域
     *
     * @param  string $nameClass 自名称
     *
     * @return mixed
     */
    public static function get($nameClass = null) {
        if (is_subclass_of($nameClass, SessionScope::class)) {
            return self::getBySession($nameClass);
        }
        if (is_subclass_of($nameClass, PrototypeScope::class)) {
            return self::beanCreate($nameClass);
        }
        if (is_subclass_of($nameClass, RequestScope::class)) {
            return self::getByContext($nameClass);
        }

        if (is_subclass_of($nameClass, WorkerScope::class)) {
            if (isset(static::$workScopeInstances[ $nameClass ]) && static::$workScopeInstances[ $nameClass ]) {
                return static::$workScopeInstances[ $nameClass ];
            }
        }
        //判断是否有实例
        if (isset(static::$instances[ $nameClass ]) && static::$instances[ $nameClass ]) {
            return static::$instances[ $nameClass ];
        }
        return self::beanCreate($nameClass);
    }

    /**
     * workerScope作用域的释放
     */
    public static function workerScopeClear() {
        static::$workScopeInstances = [];
    }


    /**
     * 方便获取累的原始类
     * 主要给 aop 使用的
     *
     * @param null $nameClass
     *
     * @return null|string
     */
    public static function getRealClass($nameClass = null) {
        if (isset(static::$beansConfig[ $nameClass ]) && static::$beansConfig[ $nameClass ]) {
            //构造对象
            /* @var $beanDefine BeanDefine */
            $beanDefine = static::$beansConfig[ $nameClass ];
            return $beanDefine->ClassName;
        }
        return $nameClass;
    }

    /**
     * 构建对象
     *
     * @param string $nameClass 类名或名称
     * @param bool   $instance 是否保存到 instances
     *
     * @return object|Container
     */
    public static function beanCreate($nameClass, $instance = true) {
        if ($nameClass == ContainerInterface::class && !static::$beansConfig[ $nameClass ]) {
            $container = new Container();
            static::$instances[ $nameClass ] = new Container();
            return $container;
        }
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
            if ($bean instanceof RequestScope) {
                //备份一份用于注入
                static::$instances[ $nameClass ] = $bean;
            } else if ($bean instanceof WorkerScope) {
                static::$workScopeInstances[ $nameClass ] = $bean;
            } else {
                static::$instances[ $nameClass ] = $bean;
            }
        }
        static::prepareBean($bean);
        if ($closure) {
            $closure($bean);
        }
        /* @var $preparer BeanPrepare  */
        $preparer =  static::$preparers[get_class($bean)];
        if ($preparer) {
            $preparer->prepare($bean);
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
        $constructor = $class->getConstructor();
        if ($constructor) {
            $constructor->setAccessible(true);
            $args = self::methodsParams($constructor);
            $constructor->invokeArgs($bean, $args);
            self::beanPrepared($bean);
        }
        //兼容老版本
        if ($class->hasMethod('_initialize')) {
            self::invokeWithIocParams($bean, "_initialize");
            self::beanPrepared($bean);
        }

    }


    /**
     * 对象被完整初始化
     * @param $bean
     */
    private static function beanPrepared($bean) {
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
        if (!($method instanceof \ReflectionMethod)) {
            $method = new \ReflectionMethod(get_class($obj), $method);
        }
        $args = self::methodsParams($method);
        $val = $method->invokeArgs($obj, $args);
        return $val;
    }


    /**
     * 从 ios 容器中获取方法的参数
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    public static function methodsParams(\ReflectionMethod $method) {
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
        return $args;
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
     * @param $name
     *
     * @return mixed
     */
    public static function getInstance($name) {
        return static::$instances[ $name ];
    }


    /**
     * 检查对象是否存在
     * @param $nameClass
     *
     * @return bool
     */
    public static function has($nameClass) {
        if (static::$instances[ $nameClass ]) {
            return true;
        }
        if (static::$workScopeInstances[ $nameClass ]) {
            return true;
        }
        if (static::$beansConfig[ $nameClass ]) {
            return true;
        }
        try {
            $class = new \ReflectionClass($nameClass);
            if ($class->isInterface() || $class->isAbstract() || $class->isTrait()) {
                return false;
            }
            return true;
        } catch (\Throwable $throwable) {
            return false;
        } catch (\Error $throwable) {
            return false;
        }
    }


    /**
     * 注册类初始化
     * @param $providerClazz
     */
    public static function register($providerClazz) {
        if (is_array($providerClazz)) {
            foreach ($providerClazz as $item) {
                static::register($item);
            }
        } else {
            $providerPrepare = Ioc::get($providerClazz);
            if (is_subclass_of($providerClazz, BeanPrepare::class)) {
                /* @var $providerClazz BeanPrepare  */
                $clazz=$providerClazz::register();
                if (is_array($clazz)) {
                    foreach ($clazz as $key) {
                        static::$preparers[ $key ] = $providerPrepare;
                    }
                } else if (is_string($clazz)) {
                    static::$preparers[ $clazz ] = $providerPrepare;
                }
            }

            if ($providerPrepare instanceof BeanPrepare) {
                $clazz = $providerPrepare->register();

            }

        }


    }


}