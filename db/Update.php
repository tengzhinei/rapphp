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

class Update extends Where {
    use Comment;

    private $table;

    private $data;

    protected $updateSql = '%COMMENT% UPDATE %TABLE% SET %FIELD% %WHERE% %ORDER%%LIMIT% %LOCK%';


    /**
     * 设置表
     *
     * @param                 $table
     *
     * @return Update
     */
    public static function table($table) {
        $update = new Update();
        $update->table = $table;
        return $update;
    }


    /**
     * 设置值
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function set($key, $value) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[ $key ] = $value;
        }
        return $this;
    }

    /**
     * 执行
     * @return int
     */
    public function excuse() {
        $fields = [];
        $values = [];
        foreach ($this->data as $field => $value) {
            $fields[] = $field . "=?";
            $values[] = $value;
        }
        $fields = implode(' , ', $fields);
        $sql = str_replace(['%TABLE%',
                            '%FIELD%',
                            '%WHERE%',
                            '%ORDER%',
                            '%LIMIT%',
                            '%LOCK%',
                            '%COMMENT%'], [$this->table,
                                           $fields,
                                           $this->whereSql(),
                                           $this->order,
                                           $this->limit,
                                           $this->lock,
                                           $this->comment,], $this->updateSql);
        $connection = Pool::get(Connection::class);
        $connection->execute($sql, array_merge($values, $this->whereParams()));
        $count= $connection->rowCount();
        Pool::release($connection);
        return $count;
    }

    /**
     * 静态更新
     *
     * @param       string    $table
     * @param     array       $data
     * @param          array  $where
     */
    public static function update($table, $data, $where) {
        $update = Update::table($table);
        foreach ($data as $field => $value) {
            $update->set($field, $value);
        }
        if (is_array($where)) {
            foreach ($where as $field => $value) {
                $update->where($field, $value);
            }
        } else {
            $update->where("id", $where);
        }
        $update->excuse();
    }

}