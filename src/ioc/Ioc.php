<?php
namespace rap\ioc;


use Psr\Container\ContainerInterface;
use rap\aop\Aop;
use rap\cache\Cache;
use rap\ioc\construstor\BeanConstructor;
use rap\ioc\construstor\BeanConstrustorMapper;
use rap\ioc\scope\PrototypeScope;
use rap\ioc\scope\RequestScope;
use rap\ioc\scope\SessionScope;
use rap\ioc\scope\WorkerScope;
use rap\log\Log;
use rap\session\RedisSession;
use rap\swoole\Context;

class Ioc
{

    //所有对象
    static private $instances = [];

    //所有对象
    static private $workScopeInstances = [];

    //类衍射定义
    static private $beansConfig = [];

    //初始化对象时用于存储
    static private $injectBeans = [];

    public static $beanConstructors = [];

    public static function clear()
    {
        self::$instances = [];
        self::$beansConfig = [];
        self::$injectBeans = [];
    }

    /**
     * 根据类名,别名获取对象
     *
     * 对象的生命周期为context
     *
     * @param null $nameClass
     * @return mixed|object
     */
    private static function contextGet($nameClass)
    {
        $bean = Context::get('Ioc_' . $nameClass);
        if ($bean) {
            return $bean;
        }
        $bean = self::beanCreate($nameClass);
        Context::set('Ioc_' . $nameClass, $bean);
        return $bean;
    }


    private static function getBySession($clazzOrName)
    {
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
     *
     * @param  string $nameClass 自名称
     *
     * @return mixed
     */
    public static function get($nameClass = null)
    {
        if (is_subclass_of($nameClass, SessionScope::class)) {
            return self::getBySession($nameClass);
        }
        if (is_subclass_of($nameClass, PrototypeScope::class)) {
            return self::beanCreate($nameClass);
        }
        if (is_subclass_of($nameClass, RequestScope::class)) {
            return self::contextGet($nameClass);
        }

        if (is_subclass_of($nameClass, WorkerScope::class)) {
            if (isset(static::$workScopeInstances[$nameClass]) && static::$workScopeInstances[$nameClass]) {
                return static::$workScopeInstances[$nameClass];
            }
        }
        //判断是否有实例
        if (isset(static::$instances[$nameClass]) && static::$instances[$nameClass]) {
            return static::$instances[$nameClass];
        }
        return self::beanCreate($nameClass);
    }

    /**
     * workerScope作用域的释放
     */
    public static function workerScopeClear()
    {
        static::$workScopeInstances = [];
    }

    public static function getRealClass($nameClass = null)
    {
        if (isset(static::$beansConfig[$nameClass]) && static::$beansConfig[$nameClass]) {
            //构造对象
            /* @var $beanDefine BeanDefine */
            $beanDefine = static::$beansConfig[$nameClass];
            return $beanDefine->ClassName;
        }
        return $nameClass;
    }


    public static function beanCreate($nameClass, $instance = true)
    {
        if ($nameClass == ContainerInterface::class && !static::$beansConfig[$nameClass]) {
            $container = new Container();
            static::$instances[$nameClass] = new Container();
            return $container;
        }
        $closure = null;
        $beanClassName = $nameClass;
        //判断是否有配置
        if (isset(static::$beansConfig[$nameClass]) && static::$beansConfig[$nameClass]) {
            //构造对象
            /* @var $beanDefine BeanDefine */
            $beanDefine = static::$beansConfig[$nameClass];
            $closure = $beanDefine->closure;
            $beanClassName = $beanDefine->ClassName;
        }
        $bean = Aop::warpBean($beanClassName, $nameClass);
        if(!$bean){
            return null;
        }
        //连接池类型的不需要在容器托管
        if ($instance) {
            if ($bean instanceof RequestScope) {
                //备份一份用于注入
                static::$instances[$nameClass] = $bean;
            } else if ($bean instanceof WorkerScope) {
                static::$workScopeInstances[$nameClass] = $bean;
            } else {
                static::$instances[$nameClass] = $bean;
            }
        }
        static::constructorBean($bean);
        if ($closure) {
            $closure($bean);
        }

        return $bean;

    }

    public static function constructorParams($bean, \ReflectionMethod $method)
    {
        $preparer = static::getConstructor(get_class($bean));
        $constructorParams = [];
        if ($preparer) {
            $constructorParams = $preparer->constructorParams();
        }
        $args = [];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            foreach ($params as $param) {
                /* @var $param /ReflectionParameter */
                $name = $param->getName();
                $class = $param->getClass();
                if (key_exists($name, $constructorParams)) {
                    $args[] = $constructorParams[$name];
                } else if ($class) {
                    $className = $class->getName();
                    $bean = Ioc::get($className);
                    if (!$bean) {
                        $args[] = method_exists($className, 'instance') ? $className::instance() : null;
                    } else {
                        $args[] = $bean;
                    }
                } else if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }

            }
        }
        return $args;
    }

    /**
     * 初始化对象
     *
     * @param $bean
     */
    private static function constructorBean($bean)
    {
        $class = new \ReflectionClass(get_class($bean));
        $constructor = $class->getConstructor();
        if ($constructor) {
            $constructor->setAccessible(true);
            $args = self::constructorParams($bean, $constructor);
            $constructor->invokeArgs($bean, $args);

        }
        //兼容老版本
        if ($class->hasMethod('_initialize')) {
            self::invokeWithIocParams($bean, "_initialize");
        }
        self::afterConstructor($bean);
    }


    /**
     * 构造完成
     * @param $bean
     */
    private static function afterConstructor($bean)
    {
        static::$injectBeans[] = $bean;
        if (static::$injectBeans[0] === $bean) {
            for ($i = count(static::$injectBeans) - 1; $i > -1; $i--) {
                $class = new \ReflectionClass(get_class(static::$injectBeans[$i]));
                $preparer = static::getConstructor(get_class($bean));
                if ($preparer) {
                    $preparer->afterConstructor($bean);
                }
                if ($class->hasMethod('_prepared')) {
                    static::$injectBeans[$i]->_prepared();
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
    public static function bind($nameOrClazz, $toClazz, \Closure $closure = null)
    {
        unset(static::$instances[$nameOrClazz]);
        static::$beansConfig[$nameOrClazz] = new BeanDefine($toClazz, $closure);
    }

    /**
     * 调用方法 并绑定对象
     *
     * @param $obj    mixed 对象
     * @param $method string 方法名
     *
     * @return mixed
     */
    public static function invokeWithIocParams($obj, $method)
    {
        if (!($method instanceof \ReflectionMethod)) {
            $method = new \ReflectionMethod(get_class($obj), $method);
        }
        $args = self::methodsParams($method);
        $val = $method->invokeArgs($obj, $args);
        return $val;
    }

    public static function methodsParams(\ReflectionMethod $method)
    {
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
    public static function instance($name, $bean)
    {
        static::$instances[$name] = $bean;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public static function getInstance($name)
    {
        return static::$instances[$name];
    }


    public static function has($nameClass)
    {
        if (static::$instances[$nameClass]) {
            return true;
        }
        if (static::$beansConfig[$nameClass]) {
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


    public static function register($providerClazz)
    {
        if (is_array($providerClazz)) {
            foreach ($providerClazz as $item) {
                static::register($item);
            }
        } else {
            if(is_string($providerClazz)){
                $providerPrepare = Ioc::get($providerClazz);
            }else{
                $providerPrepare=$providerClazz;
            }
            if ($providerPrepare instanceof BeanConstrustorMapper) {
                $providerPrepare->register();
            } else
                if ($providerPrepare instanceof BeanConstructor) {
                    Log::error(json_encode($providerPrepare));
                    $clazz = $providerPrepare->constructorClass();
                    if (is_array($clazz)) {
                        foreach ($clazz as $key) {
                            static::$beanConstructors[$key] = $providerPrepare;
                        }
                    } else if (is_string($clazz)) {

                        static::$beanConstructors[$clazz] = $providerPrepare;
                    }
                }

        }


    }

    /**
     * 获取类构造类
     * @param $clazz
     * @return BeanConstructor
     */
    public static function getConstructor($clazz)
    {

        return static::$beanConstructors[$clazz];
    }
}