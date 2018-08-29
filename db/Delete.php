<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */

namespace rap\db;


use rap\ioc\Ioc;

class Delete extends Where {
    use Comment;
    /**
     * 表
     * @var string
     */
    private $table;


    protected $deleteSql = '%COMMENT% DELETE FROM %TABLE% %WHERE% %ORDER%%LIMIT% %LOCK%';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param   string        $table
     * @param Connection|null $connection
     *
     * @return Delete
     */
    public static function table($table, Connection $connection = null) {
        $delete = new Delete();
        $delete->table = $table;
        if (!$connection) {
            $connection = Ioc::get(Connection::class);
        }
        $delete->connection = $connection;
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
        $this->connection->execute($sql, $this->whereParams());
        return $this->connection->rowCount();
    }


    /**
     * 静态删除方法
     *
     * @param                 $table
     * @param                 $where
     * @param Connection|null $connection
     *
     * @return int
     */
    public static function delete($table, $where, Connection $connection = null) {
        $delete = Delete::table($table, $connection);
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