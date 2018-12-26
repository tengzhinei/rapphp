<?php
namespace rap\swoole\lock;

/**
 * User: jinghao@duohuo.net
 * Date: 18/12/3
 * Time: 下午8:23
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\swoole\Context;
use rap\swoole\pool\Pool;

/**
 * redis locker redis分布式锁
 * 建议使用 swoole 模式
 */
class RedisLocker {


    /**
     * 上锁 锁定时间最大为10s,防止出现未解锁出现死锁
     *
     * @param     $key
     * @param int $timeout 单位毫秒
     *
     * @return bool
     * @throws \Error
     */
    public static function lock($key, $timeout = 500) {
        /* @var $cache RedisCache */
        $cache = Pool::get(CacheInterface::class);
        try {
            if ($cache instanceof RedisCache) {
                $cache->open();
                $redis = $cache->redis;
                $script = "if(redis.call('setnx',  KEYS[1],ARGV[1])==1)then return redis.call('expire',KEYS[1],10) else 
            return 0 end";
                for ($i = 0; $i < $timeout / 5; $i++) {
                    $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' . Context::id()], 1);
                    if ($ok) {
                        Pool::release($cache);
                        return true;
                    }
                    if (IS_SWOOLE) {
                        \Co::sleep(0.005);
                    } else {
                        sleep(0.005);
                    }

                }
            }
            Pool::release($cache);
        } catch (\RuntimeException $e) {
            Pool::release($cache);
            throw $e;
        } catch (\Error $e) {
            Pool::release($cache);
            throw $e;
        }
        return false;
    }

    /**
     * 取消锁
     *
     * @param $key
     *
     * @return bool
     * @throws \Error
     */
    public static function unlock($key) {
        /* @var $cache RedisCache */
        $cache = Pool::get(CacheInterface::class);
        try {
            $ok = false;
            if ($cache instanceof RedisCache) {
                $cache->open();
                $redis = $cache->redis;
                $script = "local v = redis.call('get', KEYS[1]) if(v==false) then return 1 end if ( v== 
            ARGV[1]) then 
            return redis.call('del', KEYS[1]) end if(string.find (v, 'lock_')==1) then return 0 end   
            v=tonumber(v)  v=v-1 if(v==0) then return redis.call('del', KEYS[1]) else return redis.call('set',KEYS[1],v) end";
                $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' . Context::id()], 1);
                $ok = $ok ? true : false;
            }
            Pool::release($cache);
        } catch (\RuntimeException $e) {
            Pool::release($cache);
            throw $e;
        } catch (\Error $e) {
            Pool::release($cache);
            throw $e;
        }
        return $ok;
    }

    /**
     *尝试上锁
     *
     * @param $key
     *
     * @return bool
     * @throws \Error
     */
    public static function tryLock($key) {
        /* @var $cache RedisCache */
        $cache = Pool::get(CacheInterface::class);
        try {
            if ($cache instanceof RedisCache) {
                $cache->open();
                $redis = $cache->redis;
                $script = "if(redis.call('setnx',  KEYS[1],ARGV[1])==1)then return redis.call('expire',KEYS[1],10) else return 0 end";
                $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' . Context::id()], 1);
                if ($ok) {
                    Pool::release($cache);
                    return true;
                }
            }
            Pool::release($cache);

        } catch (\RuntimeException $e) {
            Pool::release($cache);
            throw $e;
        } catch (\Error $e) {
            Pool::release($cache);
            throw $e;
        }

        return false;
    }

    /**
     * 加只读锁
     *
     * @param     $key
     * @param int $timeout
     *
     * @return bool
     * @throws \Error
     */
    public static function lockRead($key, $timeout = 500) {
        $cache = Pool::get(CacheInterface::class);
        try {
            if ($cache instanceof RedisCache) {
                $cache->open();
                $redis = $cache->redis;
                //如果已上独占锁 返回 false 如果没有锁 只读锁+1 并设置过期时间
                $script = "local v = redis.call('get', KEYS[1]) if(v==false) then v=0 end if(string.find (v, 'lock_')==0) then
             return 0 end redis.call('set',KEYS[1],v+1) return return redis.call('expire',KEYS[1],10)";
                for ($i = 0; $i < $timeout / 5; $i++) {
                    $ok = $redis->eval($script, ["_RedisLocker_$key"], 1);
                    if ($ok) {
                        Pool::release($cache);
                        return true;
                    }
                    if (IS_SWOOLE) {
                        \Co::sleep(0.005);
                    } else {
                        sleep(0.005);
                    }
                }
            }
            Pool::release($cache);
        } catch (\RuntimeException $e) {
            Pool::release($cache);
            throw $e;
        } catch (\Error $e) {
            Pool::release($cache);
            throw $e;
        }
        return false;
    }

    /**
     * 尝试只读锁
     *
     * @param $key
     *
     * @return bool
     * @throws \Error
     */
    public static function tryLockRead($key) {
        $cache = Pool::get(CacheInterface::class);
        try {
            if ($cache instanceof RedisCache) {
                $cache->open();
                $redis = $cache->redis;
                //如果已上独占锁 返回 false 如果没有锁 只读锁+1 并设置过期时间
                $script = "local v = redis.call('get', KEYS[1]) if(v==false) then v=0 end if(string.find (v, 'lock_')==0) then
             return 0 end redis.call('set',KEYS[1],v+1) return return redis.call('expire',KEYS[1],10)";
                $ok = $redis->eval($script, ["_RedisLocker_$key"], 1);
                if ($ok) {
                    Pool::release($cache);
                    return true;
                }
            }
            Pool::release($cache);
        } catch (\RuntimeException $e) {
            Pool::release($cache);
            throw $e;
        } catch (\Error $e) {
            Pool::release($cache);
            throw $e;
        }
        return false;
    }

}