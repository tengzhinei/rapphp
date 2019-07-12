<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/27
 * Time: 下午8:00
 */

namespace rap\db;


use rap\swoole\pool\Pool;
use rap\swoole\pool\ResourcePool;

class DB {


    /**
     * 插入
     *
     * @param string $table 表
     * @param array  $data  数据
     * @param string  $connection_name  名称
     * @return Insert|string
     */
    public static function insert($table, $data = null, $connection_name = '') {
        if ($data !== null) {
            return Insert::insert($table, $data, $connection_name);
        } else {
            return Insert::table($table, $connection_name);
        }
    }

    /**
     * 删除
     *
     * @param string     $table 表
     * @param array      $where 条件
     * @param string $connection_name
     *
     * @return null|Delete
     */
    public static function delete($table, $where = null, $connection_name = '') {
        if ($where) {
            Delete::delete($table, $where, $connection_name);
        } else {
            return Delete::table($table, $connection_name);
        }
        return null;
    }


    /**
     * 更新
     *
     * @param string $table 表
     * @param array  $data  数据
     * @param array  $where
     * @param string $connection_name
     *
     * @return null|Update
     */
    public static function update($table, $data = null, $where = null, $connection_name = '') {
        if ($data) {
            Update::update($table, $data, $where, $connection_name);
            return null;
        } else {
            return Update::table($table, $connection_name);
        }
    }

    /**
     * 查询
     *
     * @param string $table 表
     * @param string $connection_name 连接名
     * @return Select
     */
    public static function select($table,$connection_name='') {
        return Select::table($table,$connection_name);
    }

    /**
     * 事务中运行
     *
     * @param \Closure $closure
     *
     * @return mixed
     * @throws \Error
     */
    public static function runInTrans(\Closure $closure) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        $pool = ResourcePool::instance();
        //加锁保证事物内使用的是同一连接
        $pool->lock($connection);
        try {
            $value = $connection->runInTrans($closure);
            return $value;
        } finally{
            //释放锁
            $pool->unLock($connection);
            //释放连接
            $pool->release($connection);
        }
    }

    /**
     * 执行sql语句
     *
     * @param       $sql
     * @param array $bind
     *
     * @throws \Error
     */
    public static function execute($sql, $bind = []) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        try {
            $connection->execute($sql, $bind);
        } finally{
            Pool::release($connection);
        }

    }

    /**
     * 使用sql查询
     *
     * @param string $sql   sql
     * @param array  $bind  数据绑定
     * @param bool   $cache 是否使用缓存
     *
     * @return array
     * @throws \Error
     */
    public static function query($sql, $bind = [], $cache = false) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        try {
            $items = $connection->query($sql, $bind, $cache);
            return $items;
        }finally{
            Pool::release($connection);
        }
    }
}