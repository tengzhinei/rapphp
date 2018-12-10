<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 上午9:53
 */

namespace rap\cache;

use rap\ioc\Ioc;
use rap\swoole\pool\Pool;
use rap\swoole\pool\ResourcePool;
use rap\swoole\CoContext;


class Cache {

    /**
     * @var CacheInterface
     */
    private $cache;


    /**
     * Cache constructor.
     *
     * @param CacheInterface $cache
     */
    private function __construct(CacheInterface $cache) {
        $this->cache = $cache;
    }

    /**
     * 根据name获取
     *
     * @param string $name 根据名字获取缓存
     *
     * @return CacheInterface
     */
    public static function getCache($name = '') {
        if ($name) {
            return Ioc::get($name);
        }
        return Pool::get(CacheInterface::class);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire -1 永不过期 0默认配置
     */
    public static function set($key, $value, $expire = 0) {
        self::getCache()->set($key, $value, $expire);
        self::release();
    }


    /**
     * 获取数据
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = "") {
        $val = self::getCache()->get($key, $default);
        self::release();
        return $val;
    }

    /**
     * 是否包含
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has($key) {
        $b = self::getCache()->has($key);
        self::release();
        return $b;
    }


    /**
     * 自增
     *
     * @param string $key
     * @param int    $step
     */
    public static function inc($key, $step = 1) {
        self::getCache()->inc($key, $step);
        self::release();
    }

    /**
     * 自减
     *
     * @param string $key
     * @param int    $step
     */
    public static function dec($key, $step = 1) {
        self::getCache()->dec($key, $step);
        self::release();
    }

    /**
     * 删除对应的key的缓存
     *
     * @param string $key
     */
    public static function remove($key) {
        self::getCache()->remove($key);
        self::release();
    }

    /**
     * 清空
     */
    public static function clear() {
        self::getCache()->clear();
        self::release();
    }

    /**
     * 存到hash里
     *
     * @param string $name
     * @param string $key
     * @param mixed  $value
     */
    public static function hashSet($name, $key, $value) {
        self::getCache()->hashSet($name, $key, $value);
        self::release();
    }

    /**
     * 从hash里取数据
     *
     * @param  string $name
     * @param  string $key
     * @param  mixed  $default
     */
    public static function hashGet($name, $key, $default = "") {
        $val = self::getCache()->hashGet($name, $key, $default);
        self::release();
        return $val;
    }

    /**
     * 从hash删除数据
     *
     * @param string $name
     * @param string $key
     */
    public static function hashRemove($name, $key) {
        self::getCache()->hashRemove($name, $key);
        self::release();
    }

    /**
     * 如果缓存类型是 redis可以获取redis 使用完成后请记得释放
     * @return null|\Redis
     */
    public static function redis() {
        $redisCache = self::getCache();
        if ($redisCache instanceof RedisCache) {
            $redisCache->ping();
            return $redisCache->redis;
        }
        return null;
    }

    /**
     * 连接池回收
     */
    public static function release() {
        $context = CoContext::getContext();
        $cache = $context->get(CacheInterface::class);
        if ($cache) {
            Pool::release($cache);
            $context->set(CacheInterface::class,null);
        }
    }
}