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
            if ($db != null) {
                $bean->userDb($db);
            }
            return $bean;
        }
        //只有通过CacheInterface::获取才会自动切库
        if ($name == CacheInterface::class) {
            $connection = $context->get(CoContext::REDIS_NAME);
            if ($connection) {
                $name = $connection;
            }
            $bean = ResourcePool::instance()->get($name);
            if ($bean instanceof RedisCache) {
                $select = $context->get(CoContext::REDIS_SELECT);
                if ($select != null) {
                    $bean->select($select);
                }
            }
            return $bean;
        }
        $item = ResourcePool::instance()->get($name);
        return $item;
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