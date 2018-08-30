<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午3:14
 */

namespace rap\db;


use rap\ioc\Ioc;

class Select extends Where {

    use Comment;

    /**
     * 表
     * @var string
     */
    private $table = '';

    /**
     * 字段
     * @var array
     */
    private $fields = [];

    /**
     * join 信息
     * @var array
     */
    private $joins = [];

    /**
     * @var Connection
     */
    private $connection;


    /**
     * 设置表
     *
     * @param                 $table
     * @param Connection|null $connection
     *
     * @return Select
     */
    public static function table($table, Connection $connection = null) {
        $select = new Select();
        $select->table = $table;
        if (!$connection) {
            $connection = Ioc::get(Connection::class);
        }
        $select->connection = $connection;
        return $select;
    }


    /**
     * 获取表字段
     *
     * @param string $field     字段
     * @param string $tableName 表
     * @param string $alias     别名开头
     *
     * @return $this
     */
    public function fields($field, $tableName = '', $alias = '') {
        if (empty($field)) {
            return $this;
        }
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }
        $real_field = [];
        foreach ($field as $val) {
            if ($tableName) {
                $val = $tableName . '.' . $val . ($alias ? ' AS ' . $alias . '_' . $val : '');
            }
            $real_field[] = $val;
        }

        $this->fields = array_merge($this->fields, $real_field);
        return $this;
    }

    /**
     * join 表
     *
     * @param string $join      join 的表
     * @param string $condition 条件
     * @param string $type      类型
     *
     * @return $this
     */
    public function join($join, $condition = null, $type = 'LEFT') {
        $this->joins[] = ['join' => $join,
                          'condition' => $condition,
                          'type' => $type];
        return $this;
    }

    private $distinct = "";

    /**
     * DISTINCT
     */
    public function distinct() {
        $this->distinct = "DISTINCT";
    }

    private $having = '';

    /**
     * having
     *
     * @param string /Where $having
     *
     * @return $this
     */
    public function having($having) {
        if ($having instanceof Where) {
            $sql = $having->whereChildSql();
            $this->having_params = $having->whereParams();
            $having = $sql;
        }

        if ($having) {
            $this->having = ' HAVING ' . $having;
        }
        return $this;
    }

    private $having_params = [];

    private $group = '';

    /**
     * group
     *
     * @param string $group
     *
     * @return $this
     */
    public function group($group) {
        $this->group = !empty($group) ? ' GROUP BY ' . $group : '';
        return $this;
    }

    protected $selectSql = '%COMMENT% SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %LOCK%';

    /**
     * prepare
     * @return array
     */
    public function prepare() {
        $sql = $this->getSql();
        $params = array_merge($this->whereParams(), $this->having_params);
        return [$sql, $params];
    }

    private $all_do = [];

    /**
     * 迭代调用模型的方法
     *
     * @param string $all_do
     *
     * @return $this
     */
    public function allDo($all_do) {
        $this->all_do = array_merge($this->all_do, explode(',', $all_do));
        return $this;
    }

    private $each = [];

    /**
     * 迭代处理
     *
     * @param \Closure $each
     *
     * @return $this
     */
    public function each(\Closure $each) {
        $this->each[] = $each;
        return $this;
    }


    /**
     * 渲染Const的数据
     * @return $this
     */
    public function renderConst() {
        $this->allDo("renderConst");
        return $this;
    }

    /**
     * 查找所有数据
     * @return array
     */
    public function findAll() {
        $sql = $this->getSql();
        $params = array_merge($this->whereParams(), $this->having_params);
        $data = $this->connection->query($sql, $params, $this->cache);
        if ($this->clazz) {
            $results = [];
            /* @var $item */
            foreach ($data as $item) {
                $clazz = $this->clazz;
                $result = new $clazz;
                foreach ($this->subRecord as $pre => $sub) {
                    $pre .= "_";
                    $values = [];
                    $length = count($pre) + 1;
                    foreach ($item as $key => $value) {
                        if (strpos($key, $pre) === 0) {
                            unset($item[ $key ]);
                            $key = substr($key, $length);
                            $values[ $key ] = $value;
                        }
                    }
                    $clazz = $sub[ 'class' ];
                    $field = $sub[ 'field' ];
                    /* @var $subRecord Record */
                    $subRecord = new $clazz;
                    $subRecord->fromDbData($values);
                    if ($sub[ 'all_do' ]) {
                        $call = $sub[ 'all_do' ];
                        $call($subRecord);
                    }
                    $result->$field = $subRecord;
                }
                if ($result instanceof Record) {
                    $result->fromDbData($item);
                } else {
                    foreach ($item as $key => $value) {
                        $result->$key = $value;
                    }
                }
                if ($this->all_do) {
                    foreach ($this->all_do as $do) {
                        $result->$do();
                    }
                }
                if ($this->each) {
                    foreach ($this->each as $each) {
                        $each($result);
                    }
                }
                if ($this->to_array && $result instanceof Record) {
                    $result = $result->toArray($this->to_array, $this->to_array_contain);
                }
                $results[] = $result;
            }
            return $results;
        }
        return $data;
    }

    private $clazz;

    /**
     * 转换为模型
     *
     * @param $class
     *
     * @return $this
     */
    public function setRecord($class) {
        $this->clazz = $class;
        return $this;
    }

    private $subRecord = [];

    /**
     * 转换为子模型
     *
     * @param string $field  字段
     * @param string $pre    前缀
     * @param string $class  转换为类
     * @param        $all_do \Closure 所有的迭代
     *
     * @return $this
     */
    public function setSubRecord($field, $pre, $class, $all_do = null) {
        $this->subRecord[ $pre ] = ['class' => $class,
                                    'field' => $field,
                                    'all_do' => $all_do];
        return $this;
    }

    /**
     * 获取sql
     * @return string
     */
    private function getSql() {
        $sql = str_replace(['%TABLE%',
                            '%DISTINCT%',
                            '%FIELD%',
                            '%JOIN%',
                            '%WHERE%',
                            '%GROUP%',
                            '%HAVING%',
                            '%ORDER%',
                            '%LIMIT%',
                            '%LOCK%',
                            '%COMMENT%',
                            '%FORCE%'], [$this->table,
                                         $this->distinct,
                                         $this->parseField(),
                                         $this->parseJoin(),
                                         $this->whereSql(),
                                         $this->group,
                                         $this->having,
                                         $this->order,
                                         $this->limit,
                                         $this->lock,
                                         $this->comment,
                                         $this->force], $this->selectSql);
        return $sql;
    }

    private $force = "";

    /**
     * 强制索引
     *
     * @param $index
     *
     * @return $this
     */
    public function force($index) {
        $this->force = sprintf(" FORCE INDEX ( %s ) ", $index);
        return $this;
    }

    /**
     * 查找一条
     * @return mixed|null
     */
    public function find() {
        $this->limit(1);
        $list = $this->findAll();
        if ($list) {
            return $list[ 0 ];
        }
        return null;
    }

    /**
     * 分页获取
     *
     * @param int $page 页码
     * @param int $step 每页条数
     *
     * @return array
     */
    public function page($page = 1, $step = 20) {
        $start = ($page - 1) * $step;
        $this->limit($start, $step);
        $data = $this->findAll();
        return $data;
    }

    /**
     * 单条时数据时获取某字段的值
     *
     * @param $field
     *
     * @return null|string
     */
    public function value($field) {
        $this->fields = [];
        $this->order("");
        $this->fields($field);
        $this->limit(0, 1);
        return $this->connection->value($this->getSql(), $this->whereParams(), $this->cache);
    }

    /**
     * 多条时数据时获取某字段的值
     *
     * @param $field
     *
     * @return array|null
     */
    public function values($field) {
        $this->fields = [];
        $this->order("");
        $this->fields($field);
        return $this->connection->values($this->getSql(), $this->whereParams(), $this->cache);
    }

    private $cache = false;

    /**
     * 开启二级缓存
     * @return $this
     */
    public function cache() {
        $this->cache = true;
        return $this;
    }

    /**
     * 计数
     *
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*') {
        return (int)$this->value('COUNT(' . $field . ') AS count');
    }

    /**
     * 求和
     *
     * @param $field
     *
     * @return int
     */
    public function sum($field) {
        return (int)$this->value('SUM(' . $field . ') AS count');
    }

    /**
     * 最大
     *
     * @param $field
     *
     * @return int
     */
    public function max($field) {
        return (int)$this->value('MAX(' . $field . ') AS count');
    }

    /**
     * 最小
     *
     * @param $field
     *
     * @return int
     */
    public function min($field) {
        return (int)$this->value('MIN(' . $field . ') AS count');
    }

    /**
     * 平均
     *
     * @param $field
     *
     * @return int
     */
    public function avg($field) {
        return (int)$this->value('AVG(' . $field . ') AS count');
    }

    /**
     * 解析字段
     * @return string
     */
    private function parseField() {
        $fieldsStr = "*";
        if ($this->fields) {
            $fieldsStr = implode(',', $this->fields);
        }
        return $fieldsStr;
    }

    /**
     * 分析join
     * @return string
     */
    private function parseJoin() {
        $joinStr = '';
        foreach ($this->joins as $join) {
            $joinStr .= ' ' . $join[ 'type' ] . ' join ' . $join[ 'join' ] . ' on ' . $join[ 'condition' ];
        }
        return $joinStr;
    }

    private $to_array;

    private $to_array_contain;

    /**
     * 转数组
     *
     * @param      $key
     * @param bool $contain
     *
     * @return Select
     */
    public function toArray($key, $contain = true) {
        $this->to_array = $key;
        $this->to_array_contain = $contain;
        return $this;
    }
}