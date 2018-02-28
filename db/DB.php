<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/27
 * Time: 下午8:00
 */

namespace rap\db;


use rap\ioc\Ioc;

class DB{


    /**
     * 插入
     * @param $table
     * @param null $data
     * @return Insert|string
     */
    public static function insert($table,$data=null){
        if($data){
             return  Insert::insert($table,$data);
        }else{
            return  Insert::table($table);
        }
    }

    /**
     * 删除
     * @param $table
     * @param null $where
     * @return null|Delete
     */
    public static function delete($table,$where=null){
        if($where){
            Delete::delete($table,$where);
        }else{
            return   Delete::table($table);
        }
        return null;
    }


    /**
     * 更新
     * @param $table
     * @param null $data
     * @param null $where
     * @return Update|void
     */
    public static function update($table,$data=null,$where=null){
        if($data){
            return  Update::update($table,$data,$where);
        }else{
            return Update::table($table);
        }
    }

    /**
     * 查询
     * @param $table
     * @return Select
     */
    public static function select($table){
        return  Select::table($table);
    }

    /**
     * 事务中运行
     * @param \Closure $closure
     */
    public static function runInTrans(\Closure $closure){
        /* @var $connection Connection  */
        $connection=Ioc::get(Connection::class);
        $connection->runInTrans($closure);
    }

    /**
     * 执行sql语句
     * @param $sql
     * @param array $bind
     */
   public static function execute($sql, $bind = []){
       /* @var $connection Connection  */
       $connection=Ioc::get(Connection::class);
       $connection->execute($sql, $bind);
   }

    /**
     * 使用sql查询
     * @param $sql
     * @param array $bind
     * @return array
     */
   public static function query($sql, $bind = []){
       /* @var $connection Connection  */
       $connection=Ioc::get(Connection::class);
     return   $connection->query($sql, $bind);
   }
}