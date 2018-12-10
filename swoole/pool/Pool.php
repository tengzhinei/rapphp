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
use rap\db\Connection;

class Pool {

    public static function getDbConnection() {
        return self::get(Connection::class);
    }

    public static function getRedis() {
        return self::get(CacheInterface::class);
    }

    public static function get($class) {
        return ResourcePool::instance()->get($class);
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