<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:03
 */

namespace rap\db;

use rap\swoole\pool\Pool;

class Insert
{
    use Comment;

    private $table;

    private $data;

    protected $insertSql = '%COMMENT% %INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) ';

    private $connection_name=Connection::class;

    /**
     * 设置表
     *
     * @param string $table 表名
     * @param string $connection_name 连接名称
     *
     * @return Insert
     */
    public static function table($table, $connection_name = '')
    {
        $insert = new Insert();
        $insert->table = $table;
        if ($connection_name) {
            $insert->connection_name=$connection_name;
        }
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
     * 执行,返回自增id
     * @return string|int
     * @throws \Error
     */
    public function excuse()
    {
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
        $connection = Pool::get($this->connection_name);
        try {
            $connection->execute($sql, $values);
            $id = $connection->getLastInsID();
            return $id;
        } finally {
            Pool::release($connection);
        }
    }

    /**
     * 静态插入
     *
     * @param string $table
     * @param array  $data
     * @param string  $connection_name
     *
     * @return int|string
     */
    public static function insert($table, $data, $connection_name = '')
    {
        $insert = Insert::table($table, $connection_name);
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
    public function replace($replace = true)
    {
        $this->replace = $replace;
        return $this;
    }
}
