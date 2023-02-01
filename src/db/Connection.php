<?php

namespace rap\db;

use \PDO;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\swoole\pool\PoolAble;
use rap\swoole\pool\PoolTrait;
use rap\util\bean\field\MType;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午1:25
 */
abstract class Connection implements PoolAble
{
    use PoolTrait;

    /**
     *  PDO实例
     * @var PDO
     */
    private $pdo;

    /**
     * dsn 数据库连接信息
     * @var string
     */
    private $dsn;

    /**
     * 用户名
     * @var string
     */
    private $username;

    /**
     * 密码
     * @var string
     */
    private $password;


    private $poolConifg = [];

    /**
     * 设置配置项
     *
     * @param array $config
     */
    public function config($config)
    {
        $this->dsn = $config['dsn'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        if ($config['pool']) {
            $this->poolConifg = $config['pool'];
        }
        //非 swoole 环境下无法使用连接池,可以使用 pdo 的持久化连接方式
        //swoole 环境下,没有必要使用
        if (!IS_SWOOLE) {
            $this->params[PDO::ATTR_PERSISTENT] = true;
        }
    }

    public function poolConfig()
    {
        return $this->poolConifg;
    }

    /**
     * PDO连接参数
     * @var array
     */
    private $params = [PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,];

    /**
     * PDO操作实例
     * @var \PDOStatement
     */
    private $PDOStatement;


    /**
     *  事务指令数
     * @var int
     */
    protected $transTimes = 0;


    /**
     * 连接数据库
     * @access public
     * @return PDO
     * @throws \Exception
     */
    public function connect()
    {
        if (!$this->pdo) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->params);
            if ($this->db) {
                $this->pdo->exec("use " . $this->db);
            }
        }
        return $this->pdo;
    }


    /**
     * 执行查询 返回数据集
     *
     * @param string $sql
     * @param array $bind
     * @param string $cacheHashKey
     *
     * @return array
     * @throws \Exception
     */
    public function query($sql, $bind = [], $cacheHashKey = '')
    {
        //防止 cacheHashKey 不是字符串类型
        if ($cacheHashKey === true) {
            $cacheHashKey = false;
        }
        $items = null;
        $dbCache = null;
        if ($cacheHashKey) {
            /* @var $dbCache DBCache */
            $dbCache = Ioc::get(DBCache::class);
            $items = $dbCache->thirdCacheGet($sql, $bind, $cacheHashKey);
        }
        if (!$items) {
            $items = [];
            $this->execute($sql, $bind);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            if ($procedure) {
                do {
                    $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
                    if ($result) {
                        $items[] = $result;
                    }
                } while ($this->PDOStatement->nextRowset());
            } else {
                $items = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
            }
            $this->PDOStatement = null;
            if ($cacheHashKey) {
                $dbCache->thirdCacheSave($sql, $bind, $items, $cacheHashKey);
            }
        }
        return $items;
    }


    /**
     * SQL指令安全过滤
     *
     * @param string $str
     */
    public function quote($str)
    {
        try {
            $this->connect();
            $this->pdo->quote($str);
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if ($error[1] == 2006 || $error[1] == 2013) {
                $this->pdo = null;
                $this->connect();
                $this->pdo->quote($str);
            } else {
                throw $e;
            }
        }
    }

    /**
     * 查询单条数据,获取单列的值
     *
     * @param string $sql
     * @param array $bind
     * @param string $cacheHashKey
     *
     * @return null|string
     */
    public function value($sql, $bind = [], $cacheHashKey = '')
    {
        if ($cacheHashKey === true) {
            $cacheHashKey = false;
        }
        $value = null;
        $dbCache = null;

        if ($cacheHashKey) {
            /* @var $dbCache DBCache */
            $dbCache = Ioc::get(DBCache::class);
            $value = $dbCache->thirdCacheGet($sql, $bind, $cacheHashKey);
            if ($value) {
                return $value['value'];
            }
        }

        if ($value == null) {
            $this->execute($sql, $bind);

            $value = $this->PDOStatement->fetchColumn();
            if ($cacheHashKey) {
                $dbCache->thirdCacheSave($sql, $bind, ['value' => $value], $cacheHashKey);
            }
        }
        return $value;
    }

    /**
     * 查询多条数据,获取多列的值
     *
     * @param string $sql
     * @param array $bind
     * @param string $cacheHashKey
     *
     * @return array|null
     */
    public function values($sql, $bind, $cacheHashKey = '')
    {
        if ($cacheHashKey === true) {
            $cacheHashKey = false;
        }
        $values = null;
        $dbCache = null;
        if ($cacheHashKey) {
            /* @var $dbCache DBCache */
            $dbCache = Ioc::get(DBCache::class);
            $values = $dbCache->thirdCacheGet($sql, $bind, $cacheHashKey);
        }
        if ($values == null) {
            $items = $this->query($sql, $bind);
            if ($items && !empty($items)) {
                $item = $items[0];
                $key = array_keys($item)[0];
                $values = [];
                foreach ($items as $item) {
                    $values[] = $item[$key];
                }
                if ($cacheHashKey) {
                    $dbCache->thirdCacheSave($sql, $bind, $values, $cacheHashKey);
                }
            }
        }
        if (!$values) {
            $values = [];
        }
        return $values;
    }

    /**
     * 执行sql
     *
     * @param string $sql
     * @param array $bind
     *
     * @throws \Exception
     */
    public function execute($sql, $bind = [])
    {
        $this->connect();
        // 根据参数绑定组装最终的SQL语句
        $this->logSql($sql, $bind);
        //释放前次的查询结果
        $this->PDOStatement = null;
        try {
            // 调试开始
            // 预处理
            $this->PDOStatement = $this->pdo->prepare($sql);
            // 参数绑定
            $this->bindValue($bind);
            // 执行查询

            $this->PDOStatement->execute();
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if ($error[1] == 2006 || $error[1] == 2013) {
                $this->pdo = null;
                $this->connect();
                $this->execute($sql, $bind);
            } else {
                throw $e;
            }
        }
    }

    private $db = '';

    /**
     * 切换数据库
     *
     * @param string $db
     */
    public function userDb($db)
    {
        if ($this->db == $db) {
            return;
        }
        // 根据参数绑定组装最终的SQL语句
        try {
            $this->db = $db;
            if ($this->pdo) {
                $this->pdo->exec("use " . $db);
            } else {
                $this->connect();
            }
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if ($error[1] == 2006 || $error[1] == 2013) {
                $this->pdo = null;
                try {
                    $this->connect();
                } catch (\PDOException $pdo) {
                    $this->db = '';
                    throw $pdo;
                }
            } else {
                $this->db = '';
                throw $e;
            }
        }
    }

    /**
     * 获取返回行数
     * @return int
     */
    public function rowCount()
    {
        return $this->PDOStatement->rowCount();
    }

    /**
     * 根据参数绑定组装最终的SQL语句
     * @access public
     *
     * @param string $sql 带参数绑定的sql语句
     * @param array $bind 参数绑定列表
     *
     * @return string
     */
    private function logSql($sql, array $bind = [])
    {
        Log::info('sql', ['sql' => $sql, 'args' => $bind]);
    }

    /**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     *
     * @param array $bind
     *
     * @throws \Exception
     */
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                $v = $val[0];
                if (PDO::PARAM_INT == $val[1] && '' === $v) {
                    $v = 0;
                }
                if ($v instanceof MType) {
                    $v = $v->dbValue();
                }
                $this->PDOStatement->bindValue($param, $v, $val[1]);
            } else {
                if ($val instanceof MType) {
                    $val = $val->dbValue();
                }
                if (is_array($val)) {
                    $val = json_encode($val);
                }
                $this->PDOStatement->bindValue($param, $val);
            }
        }
    }

    /**
     * 启动事务
     * @return void
     */
    public function startTrans()
    {
        $this->connect();
        $this->transTimes++;
        try {
            if (1 == $this->transTimes) {
                $this->pdo->beginTransaction();
            }
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if ($error[1] == 2006 || $error[1] == 2013) {
                $this->pdo = null;
                $this->connect();
                $this->pdo->beginTransaction();
            } else {
                throw $e;
            }
        }
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @return void
     */
    public function commit()
    {
        $this->connect();
        if (1 == $this->transTimes) {
            $this->pdo->commit();
        }
        --$this->transTimes;
    }

    /**
     * 事务回滚
     * @return void
     */
    public function rollback()
    {
        $this->connect();
        if (1 == $this->transTimes) {
            $this->pdo->rollBack();
        }
        $this->transTimes = max(0, $this->transTimes - 1);
    }

    /**
     * 在事务中执行
     *
     * @param \Closure $closure
     *
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function runInTrans(\Closure $closure)
    {
        $this->startTrans();
        try {
            $result = $closure();
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }


    /**
     * 批处理执行SQL语句
     *
     * @param array $sqlArray
     *
     * @return bool
     * @throws \Exception
     */
    public function batchQuery($sqlArray = [])
    {
        if (!is_array($sqlArray)) {
            return false;
        }
        // 自动启动事务支持
        $this->startTrans();
        try {
            foreach ($sqlArray as $sql) {
                $this->execute($sql);
            }
            // 提交事务
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * 获取最近一次查询的sql语句
     * @return string
     */
    public function getLastSql()
    {
        return "";
    }

    /**
     * 获取最近插入的ID
     *
     * @param string $sequence 自增序列名
     *
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return $this->pdo->lastInsertId($sequence);
    }

    /**
     * 获取最近的错误信息
     * @return string
     */
    public function getError()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1] . ':' . $error[2];
        } else {
            $error = '';
        }
        return $error;
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        // 释放查询
        $this->PDOStatement = null;
        // 关闭连接
        $this->pdo = null;
    }


    /**
     * 获取数据字段
     *
     * @param $table
     */
    abstract public function getFields($table);

    /**
     * 获取所有表信息
     * @return mixed
     */
    abstract public function getTables();

    /**
     * 获取主键字段
     *
     * @param $table
     *
     * @return mixed
     */
    abstract public function getPkField($table);

    abstract public function getFieldsComment($table);

    abstract public function getTableComments();
}
