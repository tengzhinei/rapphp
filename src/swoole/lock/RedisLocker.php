<?php
namespace rap\swoole\lock;

/**
 * User: jinghao@duohuo.net
 * Date: 18/12/3
 * Time: 下午8:23
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
use rap\cache\Cache;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\swoole\Context;
use rap\swoole\pool\Pool;
use rap\web\Application;

/**
 * redis locker redis分布式锁
 * 建议使用 swoole 模式
 */
class RedisLocker {


    /**
     * 加独占锁 锁定时间最大为10s,防止出现未解锁出现死锁
     * 注意如果 redis 满了,会永远拿不到锁
     *
     * @param string $key   锁的名称
     * @param int    $timeout 单位毫秒
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
                $lock_name= self::lockName();
                for ($i = 0; $i < $timeout / 5; $i++) {
                    $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' .$lock_name], 1);
                    if ($ok) {
                        Log::debug('RedisLock success:加锁成功 ', ['key' => $key]);
                        return true;
                    }
                    if (IS_SWOOLE) {
                        \Co::sleep(0.005);
                    } else {
                        //非swoole 环境值尝试加一次
                       return false;
                    }
                }
            }
        } finally {
            Pool::release($cache);
        }
        Log::debug('RedisLock fail:加锁失败 ', ['key' => $key]);
        return false;
    }

    /**
     * 取消独占锁或只读锁
     *
     * @param string $key   锁的名称
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
                $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' . self::lockName()], 1);
                $ok = $ok ? true : false;
            }
        } finally {
            Pool::release($cache);
        }
        if ($ok) {
            Log::info('RedisLock unlock success: 释放锁成功', ['key' => $key]);
        } else {
            Log::info('RedisLock unlock fail: 释放锁失败', ['key' => $key]);
        }
        return $ok;
    }

    /**
     * 尝试加独占锁
     * 尝试加锁只会尝试一遍,加锁不成功,会直接返回
     *
     * @param string $key   锁的名称
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
                $ok = $redis->eval($script, ["_RedisLocker_$key", 'lock_' . self::lockName()], 1);
                if ($ok) {
                    Log::debug('RedisLock tryLock success: 尝试加锁成功 ', ['key' => $key]);
                    return true;
                }
            }
        } finally {
            Pool::release($cache);
        }
        Log::debug('RedisLock tryLock fail: 尝试加锁失败 :', ['key' => $key]);
        return false;
    }

    /**
     * 加只读锁
     * 多个只读锁可以并发获取,被获取了只读锁的锁,不能再获取独占锁,需要等待只读结算
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
             return 0 end redis.call('set',KEYS[1],v+1)  return redis.call('expire',KEYS[1],10)";
                for ($i = 0; $i < $timeout / 5; $i++) {
                    $ok = $redis->eval($script, ["_RedisLocker_$key"], 1);
                    if ($ok) {
                        Log::debug('RedisLock lockRead success:  添加只读锁成功 ', ['key' => $key]);
                        return true;
                    }
                    if (IS_SWOOLE) {
                        \Co::sleep(0.005);
                    } else {
                        sleep(0.005);
                    }
                }
            }
        } finally {
            Pool::release($cache);
        }
        Log::debug('RedisLock lockRead fail: 添加只读锁失败 ', ['key' => $key]);
        return false;
    }

    /**
     * 尝试只读锁
     * 尝试加锁只会尝试一遍,加锁不成功,会直接返回
     * 多个只读锁可以并发获取,被获取了只读锁的锁,不能再获取独占锁,需要等待只读结算
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
             return 0 end redis.call('set',KEYS[1],v+1)  return redis.call('expire',KEYS[1],10)";
                $ok = $redis->eval($script, ["_RedisLocker_$key"], 1);
                if ($ok) {
                    Log::debug('RedisLock tryLockRead success: 尝试添加只读锁成功 ', ['key' => $key]);
                    return true;
                }
            }
        } finally {
            Pool::release($cache);
        }
        Log::debug('RedisLock tryLockRead fail: 尝试添加只读锁失败 ', ['key' => $key]);
        return false;
    }


    private static function lockName(){
        if(!IS_SWOOLE){
            return Context::id();
        }
        /* @var $app Application  */
        $app = Ioc::get(Application::class);
        return md5(serialize(swoole_get_local_ip()).$app->worker_id.Context::id());
    }
}
