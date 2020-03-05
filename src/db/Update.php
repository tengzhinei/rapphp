<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */
namespace rap\db;

use rap\swoole\pool\Pool;

class Update extends Where
{
    use Comment;

    private $table;

    private $data;

    protected $updateSql = '%COMMENT% UPDATE %HINT% %TABLE% SET %FIELD% %WHERE% %ORDER%%LIMIT% %LOCK%';

    /**
     * update 附加指令
     * @var string
     */
    private $hint = '';

    private $connection_name = Connection::class;

    /**
     * 设置表
     *
     * @param                 $table
     * @param                 $connection_name
     *
     * @return Update
     */
    public static function table($table, $connection_name = '')
    {
        $update = new Update();
        $update->table = $table;
        if ($connection_name) {
            $update->connection_name = $connection_name;
        }
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
    public function set($key, $value)
    {
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
     * @throws \Error
     */
    public function excuse()
    {
        $fields = [];
        $values = [];
        foreach ($this->data as $field => $value) {
            $fields[] = $field . "=?";
            $values[] = $value;
        }
        $fields = implode(' , ', $fields);
        $sql = str_replace(['%TABLE%',
                            '%HINT%',
                            '%FIELD%',
                            '%WHERE%',
                            '%ORDER%',
                            '%LIMIT%',
                            '%LOCK%',
                            '%COMMENT%'], [$this->table,
                                           $this->hint,
                                           $fields,
                                           $this->whereSql(),
                                           $this->order,
                                           $this->limit,
                                           $this->lock,
                                           $this->comment,], $this->updateSql);
        $connection = Pool::get($this->connection_name);
        try {
            $connection->execute($sql, array_merge($values, $this->whereParams()));
            $count = $connection->rowCount();
            return $count;
        } finally {
            Pool::release($connection);
        }
    }

    /**
     * 延迟队列更新
     * 这个需要配合阿里云的rds使用才有效果
     *
     * @param string|int       $pk
     * @param string           $table
     * @param array            $data
     * @param array|string|int $where
     * @param string           $connection_name
     */
    public static function delayUpdate($pk, $table, $data, $where, $connection_name = null)
    {
        $update = self::buildUpdate($table, $data, $where, $connection_name);
        $update->hint = "COMMIT_ON_SUCCESS ROLLBACK_ON_FAIL QUEUE_ON_PK $pk";
        $update->excuse();
    }

    /**
     * 静态更新
     *
     * @param     string           $table
     * @param     array            $data
     * @param     array|string|int $where
     * @param     string           $connection_name
     *
     * @throws \Error
     * @return int 更新条数
     */
    public static function update($table, $data, $where, $connection_name)
    {
        return self::buildUpdate($table, $data, $where, $connection_name)->excuse();
    }


    private static function buildUpdate($table, $data, $where, $connection_name)
    {
        $update = Update::table($table, $connection_name);
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
        return $update;
    }
}
