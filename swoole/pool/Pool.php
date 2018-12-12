<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/2
 * Time: 下午4:36
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;


use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\db\Connection;
use rap\swoole\CoContext;

class Pool {



    public static function getDbConnection() {
        return self::get(Connection::class);
    }

    public static function getRedis() {
        return self::get(CacheInterface::class);
    }

    /**
     *
     * @param $name
     *
     * @return mixed
     */
    public static function get($name) {
        $context = CoContext::getContext();
        if ($name == Connection::class) {
            $connection = $context->get(CoContext::CONNECTION_NAME);
            if ($connection) {
                $name = $connection;
            }
            /* @var $bean Connection */
            $bean = ResourcePool::instance()->get($name);
            $db = $context->get(CoContext::CONNECTION_scheme);
            $bean->userDb($db);
        }
        if ($name == CacheInterface::class) {
            $connection = $context->get(CoContext::REDIS_NAME);
            if ($connection) {
                $name = $connection;
            }
            $select = $context->get(CoContext::REDIS_SELECT);
            if ($connection instanceof RedisCache) {
                $connection->select($select);
            }
        }
        return ResourcePool::instance()->get($name);
    }

    public static function release(PoolAble $bean) {
        ResourcePool::instance()->release($bean);
    }


    public static function lock(PoolAble $bean) {
        ResourcePool::instance()->lock($bean);
    }

    public static function unLock(PoolAble $bean) {
        ResourcePool::instance()->unLock($bean);
    }



}