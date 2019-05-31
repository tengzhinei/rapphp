<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */
namespace rap\db;


use rap\swoole\pool\Pool;

class Update extends Where {
    use Comment;

    private $table;

    private $data;

    protected $updateSql = '%COMMENT% UPDATE %TABLE% SET %FIELD% %WHERE% %ORDER%%LIMIT% %LOCK%';

    private $connection_name=Connection::class;
    /**
     * 设置表
     *
     * @param                 $table
     * @param                 $connection_name
     * @return Update
     */
    public static function table($table,$connection_name='') {
        $update = new Update();
        $update->table = $table;
        if($connection_name){
            $update->connection_name=$connection_name;
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
     * @throws \Error
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
        $connection = Pool::get($this->connection_name);
        try {
            $connection->execute($sql, array_merge($values, $this->whereParams()));
            $count = $connection->rowCount();
            return $count;
        } finally{
            Pool::release($connection);
        }
    }

    /**
     * 静态更新
     *
     * @param       string   $table
     * @param     array      $data
     * @param          array $where
     * @param       string   $connection_name
     */
    public static function update($table, $data, $where,$connection_name) {
        $update = Update::table($table,$connection_name);
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