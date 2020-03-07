<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午3:59
 */

namespace rap\db;

/**
 * 搜索条件
 * Class Where
 * @package rap\db
 */
class Where
{

    private static $exp = ["eq" => "=",
                   "neq" => "<>",
                   "gt" => ">",
                   "egt" => ">=",
                   "lt" => "<",
                   "elt" => ">=",];

    /**
     * where 条件
     * @var array
     */
    private $wheres = [];
    /**
     * where 参数
     * @var array
     */
    protected $params = [];

    /**
     * @param string|array|\Closure $field     字段 'name' 或者['name','tengzhinei']或者['age',['>',23]] 或者 闭包 闭包会传入 WHERE
     * @param string                $op        操作符 is in = 等
     * @param string|int|array      $condition 条件 数组时为in is 等具体看操作符
     *
     * @return $this
     */
    public function where($field, $op = null, $condition = null)
    {
        $this->addWhere("AND", $field, $op, $condition);
        return $this;
    }

    /**
     * 拼 sql
     * 注意 sql 需要 ? 做编译,防止被sql注入
     * @param string $sql
     * @param array  $condition
     *
     * @return $this
     */
    public function sql($sql, array $condition)
    {
        $this->addWhere("AND", $sql, 'sql', $condition);
        return $this;
    }

    /**
     * 拼 sql
     * 注意 sql 需要 ? 做编译,防止被sql注入
     * @param string $sql
     * @param array  $condition
     *
     * @return $this
     */
    public function sqlOr($sql, array $condition)
    {
        $this->addWhere("OR", $sql, 'sql', $condition);
        return $this;
    }


    /**
     * Or 连接条件
     *
     * @param string|array|\Closure $field     字段 'name' 或者['name','tengzhinei']或者['age',['>',23]] 或者 闭包 闭包会传入 WHERE
     * @param string                $op        操作符 is in = 等
     * @param string|int|array      $condition 条件 数组时为in is 等具体看操作符
     *
     * @return $this
     */
    public function whereOr($field, $op = null, $condition = null)
    {
        $this->addWhere("OR", $field, $op, $condition);
        return $this;
    }

    /**
     * XOr 连接条件
     *
     * @param string|array|\Closure $field     字段 'name' 或者['name','tengzhinei']或者['age',['>',23]] 或者 闭包 闭包会传入 WHERE
     * @param string                $op        操作符 is in = 等
     * @param string|int|array      $condition 条件 数组时为in is 等具体看操作符
     *
     * @return $this
     */
    public function whereXOr($field, $op = null, $condition = null)
    {
        $this->addWhere("XOR", $field, $op, $condition);
        return $this;
    }

    /**
     * 获取where条件的sql语句
     * @return string
     */
    protected function whereChildSql()
    {
        $this->params = [];
        $sql = $this->parseWhere($this->wheres, $this->params);
        return $sql;
    }

    /**
     * 获取where条件的sql语句
     * @return string
     */
    protected function whereSql()
    {
        $this->params = [];
        $sql = $this->parseWhere($this->wheres, $this->params);
        if ($sql) {
            $sql = " WHERE " . $sql;
        }
        return $sql;
    }


    /**
     * 添加条件
     *
     * @param string                $logic     逻辑
     * @param string|array|\Closure $field     字段 'name' 或者['name','tengzhinei']或者['age',['>',23]] 或者 闭包 闭包会传入 WHERE
     * @param string                $op        操作符 is in = 等
     * @param string|int|array      $condition 条件 数组时为in is 等具体看操作符
     */
    private function addWhere($logic, $field, $op = null, $condition = null)
    {
        if (!$field) {
            return;
        }
        if ($op == null) {
            $op = 'null';
        }
        if ($field instanceof \Closure) {
            $select = new Where();
            $field($select);
            $where = $select->wheres;
            $this->wheres[] = ['child' => $where,
                               'logic' => $logic];
        } else {
            if ($op == 'is') {
                if (is_array($condition)) {
                    $c = count($condition);
                    if ($c == 1) {
                        $op = "=";
                        $condition = $condition[ 0 ];
                    } else {
                        $op = "in";
                    }
                } else {
                    $op = "=";
                }
            } elseif ($op == 'not') {
                if (is_array($condition)) {
                    $c = count($condition);
                    if ($c == 1) {
                        $op = "!=";
                        $condition = $condition[ 0 ];
                    } else {
                        $op = "not in";
                    }
                } else {
                    $op = "!=";
                }
            } elseif ($op == 'start') {
                $op = "like";
                $condition = $condition . "%";
            } elseif ($op == 'end') {
                $op = "like";
                $condition = "%" . $condition;
            } elseif ($op == "contain") {
                $op = "like";
                $condition = "%" . $condition . "%";
            }
            if (self::$exp[ $op ]) {
                $op = self::$exp[ $op ];
            }
            if (is_null($condition) && 'null' !== $op && 'not null' !== $op) {
                $condition = $op;
                $op = '=';
            }
            if (is_array($field)) {
                foreach ($field as $item => $value) {
                    $where = ['field' => $item,
                              'logic' => $logic];
                    if (is_array($value)) {
                        $where[ 'op' ] = $value[ 0 ];
                        $where[ 'condition' ] = $value[ 1 ];
                    } else {
                        $where[ 'op' ] = '=';
                        $where[ 'condition' ] = $value;
                    }
                    $this->wheres[] = $where;
                }
            } else {
                $this->wheres[] = ['field' => $field,
                                   'op' => $op,
                                   'logic' => $logic,
                                   'condition' => $condition];
            }
        }
    }

    /**
     * where条件的sql
     *
     * @param array $wheres 条件
     * @param array $data   数据
     *
     * @return string
     */
    private function parseWhere($wheres, &$data)
    {
        $sql = "";
        foreach ($wheres as $where) {
            if (isset($where[ 'child' ])) {
                if ($sql) {
                    $sql .= " " . $where[ 'logic' ];
                }
                $sql .= " (";
                $sql .= $this->parseWhere($where[ 'child' ], $data);
                $sql .= ")";
            } else {
                if ($sql) {
                    $sql .= " " . $where[ 'logic' ];
                }
                $op = $where[ 'op' ];
                if ($op == 'sql') {
                    $sql .= " " . $where[ 'field' ];
                    $condition = $where[ 'condition' ];
                    if ($condition && is_array($condition)) {
                        foreach ($condition as $item) {
                            $data[] = $item;
                        }
                    }
                } elseif ($op == 'null') {
                    $op = "is null";
                    $sql .= " " . $where[ 'field' ] . ' ' . $op;
                } elseif ($op == 'not null') {
                    $op = "is not null";
                    $sql .= " " . $where[ 'field' ] . ' ' . $op;
                } elseif ($op == 'in' || $op == 'not in') {
                    $condition = $where[ 'condition' ];
                    if (!is_array($condition)) {
                        $condition = explode(',', $condition);
                    }
                    $p = [];
                    foreach ($condition as $item) {
                        $p[] .= "?";
                        $data[] = $item;
                    }
                    $p = implode(",", $p);
                    $op .= " (" . $p . ")";
                    $sql .= " " . $where[ 'field' ] . ' ' . $op;
                } elseif ($op == 'between' || $op == 'not between') {
                    $sql .= " " . $where[ 'field' ] . ' ' . $op . ' ? and ? ';
                    $data[] = $where[ 'condition' ][ 0 ];
                    $data[] = $where[ 'condition' ][ 1 ];
                } elseif ($op == 'day') {
                    if (is_array($where[ 'condition' ])) {
                        $sql .= " " . $where[ 'field' ] . ' ' . 'between' . ' ? and ? ';
                        $data[] = strtotime($where[ 'condition' ][ 0 ]);
                        $data[] = strtotime($where[ 'condition' ][ 1 ]);
                    } else {
                        $sql .= " " . $where[ 'field' ] . ' ' . '>' . ' ?  ';
                        $day = $where[ 'condition' ];
                        $time = strtotime(date("Y-m-d", time()));
                        $data[] = $time - $day * 24 * 60 * 60;
                    }
                } else {
                    if (key_exists($op, Where::$exp)) {
                        $op = Where::$exp[ $op ];
                    }
                    $sql .= " " . $where[ 'field' ] . ' ' . $op;
                    if ($where[ 'condition' ] === 'now') {
                        $sql .= " unix_timestamp(now()) ";
                    } else {
                        $sql .= ' ? ';
                        $data[] = $where[ 'condition' ];
                    }
                }
            }
        }
        return $sql;
    }

    /**
     * 获取where条件的参数
     * @return array
     */
    protected function whereParams()
    {
        return $this->params;
    }

    protected $order = '';

    /**
     * 排序 支持 ['timefile'=>'desc','name'=>asc] 或者直接 order("time desc","name asc")
     *
     * @param $field
     *
     * @return $this
     */
    public function order($field)
    {
        $order = [];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $order[] = $key . ' ' . $value;
            }
        } else {
            $order = func_get_args();
        }
        $order = implode(',', $order);
        $this->order = !empty($order) ? ' ORDER BY ' . $order : '';
        return $this;
    }

    protected $limit = '';

    /**
     * limit限制
     *
     * @param     $offset
     * @param int $length
     *
     * @return $this
     */
    public function limit($offset, $length = 0)
    {
        if ($length == 0) {
            $length = $offset;
            $offset = 0;
        }
        $this->limit = " LIMIT " . (int)$offset . ',' . (int)$length;
        return $this;
    }

    /**
     * 锁
     * @var string
     */
    protected $lock = '';

    /**
     * 锁行 请在事务中使用
     * @return $this
     */
    public function lock()
    {
        $this->lock = " FOR UPDATE ";
        return $this;
    }
}
