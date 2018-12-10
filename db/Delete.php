<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */

namespace rap\db;


use rap\ioc\Ioc;
use rap\swoole\pool\Pool;
use rap\swoole\pool\ResourcePool;

class Delete extends Where {
    use Comment;
    /**
     * 表
     * @var string
     */
    private $table;


    protected $deleteSql = '%COMMENT% DELETE FROM %TABLE% %WHERE% %ORDER%%LIMIT% %LOCK%';

   

    /**
     * @param   string        $table
     *
     * @return Delete
     */
    public static function table($table) {
        $delete = new Delete();
        $delete->table = $table;
        return $delete;
    }

    /**
     * 执行,返回执行的条数
     * @return int
     */
    public function excuse() {
        $search = ['%TABLE%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'];
        $sql = str_replace($search, [$this->table,
                                     $this->whereSql(),
                                     $this->order,
                                     $this->limit,
                                     $this->lock,
                                     $this->comment,], $this->deleteSql);
        $connection = Pool::get(Connection::class);
        $connection->execute($sql, $this->whereParams());
        $count= $connection->rowCount();
        Pool::release($connection);
        return $count;
    }


    /**
     * 静态删除方法
     *
     * @param                 $table
     * @param                 $where
     *
     * @return int
     */
    public static function delete($table, $where) {
        $delete = Delete::table($table);
        if (is_array($where)) {
            foreach ($where as $field => $value) {
                $delete->where($field, $value);
            }
        } else {
            $delete->where("id", $where);
        }
        return $delete->excuse();
    }

}