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
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
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
            $val = self::getCache()->get($key, $default);
            self::release();
            return $val;
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
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
            $b = self::getCache()->has($key);
            self::release();
            return $b;
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }


    /**
     * 自增
     *
     * @param string $key
     * @param int    $step
     * @throws \Error
     */
    public static function inc($key, $step = 1) {
        try {
            self::getCache()->inc($key, $step);
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 自减
     *
     * @param string $key
     * @param int    $step
     * @throws \Error
     */
    public static function dec($key, $step = 1) {
        try {
            self::getCache()->dec($key, $step);
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
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
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 清空
     * @throws \Error
     */
    public static function clear() {
        try {
            self::getCache()->clear();
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 存到hash里
     *
     * @param string $name
     * @param string $key
     * @param mixed  $value
     * @throws \Error
     */
    public static function hashSet($name, $key, $value) {
        try {
            self::getCache()->hashSet($name, $key, $value);
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 从hash里取数据
     *
     * @param  string $name
     * @param  string $key
     * @param  mixed  $default
     * @throws \Error
     */
    public static function hashGet($name, $key, $default = "") {
        try {
            $val = self::getCache()->hashGet($name, $key, $default);
            self::release();
            return $val;
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 从hash删除数据
     *
     * @param string $name
     * @param string $key
     * @throws \Error
     */
    public static function hashRemove($name, $key) {
        try {
            self::getCache()->hashRemove($name, $key);
            self::release();
        } catch (\RuntimeException $e) {
            self::release();
            throw $e;
        } catch (\Error $e) {
            self::release();
            throw $e;
        }
    }

    /**
     * 如果缓存类型是 redis可以获取redis 使用完成后请记得释放
     * @return null|\Redis
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
        $context = CoContext::getContext();
        $cache = $context->get(CacheInterface::class);
        if ($cache) {
            Pool::release($cache);
            $context->set(CacheInterface::class, null);
        }
    }
}