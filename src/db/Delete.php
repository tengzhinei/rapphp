<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */

namespace rap\db;


use rap\swoole\pool\Pool;

class Delete extends Where {
    use Comment;
    /**
     * 表
     * @var string
     */
    private $table;


    protected $deleteSql = '%COMMENT% DELETE FROM %TABLE% %WHERE% %ORDER%%LIMIT% %LOCK%';


    private $connection_name = Connection::class;

    /**
     * @param   string $table
     * @param string   $connection_name 连接名称
     *
     * @return Delete
     */
    public static function table($table, $connection_name = '') {
        $delete = new Delete();
        $delete->table = $table;
        if ($connection_name) {
            $delete->connection_name = $connection_name;
        }
        return $delete;
    }

    /**
     * 执行,返回执行的条数
     * @return int
     * @throws \Error
     */
    public function excuse() {
        $search = ['%TABLE%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'];
        $sql = str_replace($search, [$this->table,
                                     $this->whereSql(),
                                     $this->order,
                                     $this->limit,
                                     $this->lock,
                                     $this->comment,], $this->deleteSql);
        $connection = Pool::get($this->connection_name);
        try {
            $connection->execute($sql, $this->whereParams());
            $count = $connection->rowCount();
            Pool::release($connection);
            return $count;
        } catch (\RuntimeException $e) {
            Pool::release($connection);
            throw $e;
        } catch (\Error $e) {
            Pool::release($connection);
            throw $e;
        }
    }

    /**
     * 静态删除方法
     *
     * @param                 $table
     * @param                 $where
     * @param                 $connection_name
     *
     * @return int
     */
    public static function delete($table, $where, $connection_name = '') {
        $delete = Delete::table($table, $connection_name);
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