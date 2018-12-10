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
use rap\swoole\pool\Pool;
use rap\swoole\CoContext;

/**
 * redis locker 支持分布式
 */
class RedisLocker {


    /**
     * @param     $key
     * @param int $timeout 单位毫秒
     *
     * @return bool
     */
    public static function lock($key, $timeout = 50) {
        /* @var $cache RedisCache */
        $cache = Pool::get(CacheInterface::class);
        if ($cache instanceof RedisCache) {
            $redis = $cache->redis;
            $script = "if redis.call('setnx', ARGV[1],ARGV[2])  then return redis.call('expire', ARGV[1], ARGV[3]) else return 0 end";
            for ($i = 0; $i < $timeout / 5; $i++) {
                $ok = $redis->eval($script,["_RedisLocker_$key", \Co::getuid(),100]);
                if ($ok) {
                    Pool::release($cache);
                    return true;
                }
                \Co::sleep(0.005);
            }
        }
        Pool::release($cache);
        return false;
    }

    public static function unlock($key) {
        /* @var $cache RedisCache */
        $cache = Pool::get(CacheInterface::class);
        if ($cache instanceof RedisCache) {
            $redis = $cache->redis;
            $script = "if redis.call('get', ARGV[1]) == ARGV[2] then return redis.call('del', ARGV[1]) else return 0 end";
            $redis->eval($script, [$key, \Co::getuid()]);
        }
        Pool::release($cache);
        return false;
    }

    public static function trylock(){

    }


}