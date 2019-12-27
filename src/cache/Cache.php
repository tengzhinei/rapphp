<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 上午9:53
 */

namespace rap\cache;

use rap\swoole\pool\Pool;
use rap\swoole\CoContext;
use rap\swoole\pool\PoolAble;


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
            return Pool::get($name);
        }
        return Pool::get(CacheInterface::class);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire -1 永不过期 0默认配置
     *
     * @throws \Error
     */
    public static function set($key, $value, $expire = 0) {
        try {
            self::getCache()->set($key, $value, $expire);
        } finally {
            self::release();
        }
    }


    /**
     * 获取数据
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     * @throws \Error
     */
    public static function get($key, $default = "") {
        try {
            return self::getCache()->get($key, $default);
        } finally {
            self::release();
        }
    }

    /**
     * 是否包含
     *
     * @param string $key
     *
     * @return bool
     * @throws \Error
     */
    public static function has($key) {
        try {
            return self::getCache()->has($key);
        } finally {
            self::release();
        }
    }


    /**
     * 自增
     *
     * @param string $key
     * @param int    $step
     *
     * @throws \Error
     */
    public static function inc($key, $step = 1) {
        try {
            return self::getCache()->inc($key, $step);
        } finally {
            self::release();
        }
    }

    /**
     * 自减
     *
     * @param string $key
     * @param int    $step
     *
     * @throws \Error
     */
    public static function dec($key, $step = 1) {
        try {
            return self::getCache()->dec($key, $step);
        } finally {
            self::release();
        }
    }

    /**
     * 删除对应的key的缓存
     *
     * @param string $key
     *
     * @throws \Error
     */
    public static function remove($key) {
        try {
            self::getCache()->remove($key);
        } finally {
            self::release();
        }
    }

    /**
     * 清空
     * @throws \Error
     */
    public static function clear() {
        try {
            self::getCache()->clear();
        } finally {
            self::release();
        }
    }

    /**
     * 存到hash里
     *
     * @param string $name
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Error
     */
    public static function hashSet($name, $key, $value) {
        try {
            self::getCache()->hashSet($name, $key, $value);
        } finally {
            self::release();
        }
    }

    /**
     * 从hash里取数据
     *
     * @param  string $name
     * @param  string $key
     * @param  mixed  $default
     *
     * @throws \Error
     */
    public static function hashGet($name, $key, $default = "") {
        try {
            return self::getCache()->hashGet($name, $key, $default);
        } finally {
            self::release();
        }
    }

    /**
     * 从hash删除数据
     *
     * @param string $name
     * @param string $key
     *
     * @throws \Error
     */
    public static function hashRemove($name, $key) {
        try {
            self::getCache()->hashRemove($name, $key);
        } finally {
            self::release();
        }
    }

    /**
     * 如果缓存类型是 redis可以获取redis 使用完成后请记得释放
     * @return null|\Redis
     * @throws
     */
    public static function redis() {
        $redisCache = self::getCache();
        if ($redisCache instanceof RedisCache) {
            $redisCache->open();
            return $redisCache->redis;
        }
        return null;
    }

    /**
     * 连接池回收
     */
    public static function release() {
        $cache = self::getCache();
        if ($cache&&$cache instanceof PoolAble){
            Pool::release($cache);
        }
    }
}