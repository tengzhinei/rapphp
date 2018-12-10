<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/27
 * Time: 下午8:00
 */

namespace rap\db;


use rap\ioc\Ioc;
use rap\swoole\pool\Pool;
use rap\swoole\pool\ResourcePool;

class DB {


    /**
     * 插入
     *
     * @param string          $table 表
     * @param array           $data  数据
     *
     * @return Insert|string
     */
    public static function insert($table, $data = null) {
        if ($data !== null) {
            return Insert::insert($table, $data);
        } else {
            return Insert::table($table);
        }
    }

    /**
     * 删除
     *
     * @param string          $table 表
     * @param array           $where 条件
     *
     * @return null|Delete
     */
    public static function delete($table, $where = null) {
        if ($where) {
            Delete::delete($table, $where);
        } else {
            return Delete::table($table);
        }
        return null;
    }


    /**
     * 更新
     *
     * @param string     $table 表
     * @param array      $data  数据
     * @param array      $where
     *
     * @return null|Update
     */
    public static function update($table, $data = null, $where = null) {
        if ($data) {
            Update::update($table, $data, $where);
            return null;
        } else {
            return Update::table($table);
        }
    }

    /**
     * 查询
     *
     * @param string     $table 表
     *
     * @return Select
     */
    public static function select($table) {
        return Select::table($table);
    }

    /**
     * 事务中运行
     *
     * @param \Closure $closure
     *
     * @return mixed
     */
    public static function runInTrans(\Closure $closure) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        $pool=ResourcePool::instance();
        //加锁保证事物内使用的是同一连接
        $pool->lock($connection);
        $value= $connection->runInTrans($closure);
        //释放锁
        $pool->unLock($connection);
        //释放连接
        $pool->release($connection);
        return $value;
    }

    /**
     * 执行sql语句
     *
     * @param string $sql  sql
     * @param array  $bind 数据绑定
     */
    public static function execute($sql, $bind = []) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        $connection->execute($sql, $bind);
        Pool::release($connection);
    }

    /**
     * 使用sql查询
     *
     * @param string $sql   sql
     * @param array  $bind  数据绑定
     * @param bool   $cache 是否使用缓存
     *
     * @return array
     */
    public static function query($sql, $bind = [], $cache = false) {
        /* @var $connection Connection */
        $connection = Pool::get(Connection::class);
        $items= $connection->query($sql, $bind, $cache);
        Pool::release($connection);
        return $items;
    }
}