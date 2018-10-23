<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:03
 */

namespace rap\db;


use rap\ioc\Ioc;

class Insert {
    use Comment;

    private $table;

    private $data;

    protected $insertSql = '%COMMENT% %INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) ';
    /**
     * @var Connection
     */
    private $connection;


    /**
     * 设置表
     *
     * @param string          $table 表名
     * @param Connection|null $connection
     *
     * @return Insert
     */
    public static function table($table, Connection $connection = null) {
        $insert = new Insert();
        $insert->table = $table;
        if (!$connection) {
            $connection = Ioc::get(Connection::class);
        }
        $insert->connection = $connection;
        return $insert;
    }

    /**
     * set 数据
     *
     * @param string $key
     * @param mixed  $value
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
     * 执行,返回自增id
     * @return string|int
     */
    public function excuse() {
        $fields = [];
        $values = [];
        $valuePlace = [];
        foreach ($this->data as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
            $valuePlace[] = "?";
        }
        $fields = implode(' , ', $fields);
        $valuePlace = implode(' , ', $valuePlace);
        $sql = str_replace(['%INSERT%',
                            '%TABLE%',
                            '%FIELD%',
                            '%DATA%',
                            '%COMMENT%'], [$this->replace ? 'REPLACE' : 'INSERT',
                                           $this->table,
                                           $fields,
                                           $valuePlace,
                                           $this->comment], $this->insertSql);
        $this->connection->execute($sql, $values);
        return $this->connection->getLastInsID();
    }

    /**
     * 静态插入
     *
     * @param string          $table
     * @param array           $data
     * @param Connection|null $connection
     *
     * @return int|string
     */
    public static function insert($table, $data, Connection $connection = null) {
        $insert = Insert::table($table, $connection);
        foreach ($data as $field => $value) {
            $insert->set($field, $value);
        }
        return $insert->excuse();
    }

    /**
     * 替换插入
     * @var bool
     */
    private $replace = false;

    /**
     * 替换插入
     *
     * @param bool $replace
     *
     * @return $this
     */
    public function replace($replace = true) {
        $this->replace = $replace;
        return $this;
    }


}