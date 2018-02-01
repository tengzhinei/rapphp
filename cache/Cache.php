<?php
namespace rap\cache;
use rap\exception\SystemException;
use rap\ioc\Ioc;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 上午9:53
 */
class Cache{

    /**
     * @var CacheInterface
     */
     private $cache;


    /**
     * Cache constructor.
     * @param CacheInterface $cache
     */
    private function __construct(CacheInterface $cache){
        $this->cache = $cache;
    }

    /**
     * 根据name获取
     * @param string $name 根据名字获取缓存
     * @return CacheInterface
     * @throws SystemException
     */
    public static function getCache($name=''){
        if($name){
            return Ioc::get($name);
        }
        return Ioc::get(CacheInterface::class);
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param $expire -1 永不过期 0默认配置
     */
    public static function set($key,$value,$expire=0){
        self::getCache()->set($key,$value,$expire);
    }

    /**
     * 获取数据
     * @param $key
     * @return mixed
     */
    public  static function get($key,$default=""){
       return self::getCache()->get($key,$default);
    }

    /**
     * 是否包含
     * @param $key
     * @return bool
     */
    public static  function has($key){
        return self::getCache()->has($key);
    }


    /**
     * 自增
     * @param $key
     * @param int $step
     */
    public static  function inc($key, $step = 1){
        self::getCache()->inc($key,$step);
    }

    /**
     * 自减
     * @param $key
     * @param int $step
     */
    public  static function dec($key, $step = 1){
        self::getCache()->dec($key,$step);
    }

    /**
     * 删除对应的key的缓存
     * @param $key
     */
    public static function remove($key){
        self::getCache()->remove($key);
    }

    /**
     * 清空
     */
    public static  function clear(){
        self::getCache()->clear();
    }

    /**
     * 存到hashmap里
     * @param $name
     * @param $key
     * @param $value
     */
    public static function hashSet($name, $key, $value){
        self::getCache()->hashSet($name,$key,$value);
    }

    /**
     * 从hashmap里取数据
     * @param $name
     * @param $key
     * @param string $default
     */
    public static function hashGet($name, $key,$default=""){
        self::getCache()->hashGet($name,$key,$default);
    }

    /**
     * 从hashmap删除数据
     * @param $name
     * @param $key
     */
    public static function hashRemove($name, $key){
        self::getCache()->hashRemove($name,$key);
    }
}