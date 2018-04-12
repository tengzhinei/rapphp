<?php
namespace rap\db;
use \PDO;
use rap\config\Config;
use rap\log\Log;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午1:25
 */
abstract class Connection{

    /** @var \PDO PDO实例 */
    private $pdo;

    private $dsn;

    private $username;

    private $password;

    public function config($config){
        $this->dsn=$config['dsn'];
        $this->username=$config['username'];
        $this->password=$config['password'];
    }


    // PDO连接参数
    private $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_PERSISTENT=>true,
        PDO::ATTR_EMULATE_PREPARES  => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true
    ];

    /** @var \PDOStatement PDO操作实例 */
    private $PDOStatement;


    // 事务指令数
    protected $transTimes = 0;
    /** @var string 当前SQL指令 */
    private $queryStr;
    /**
     * 连接数据库方法
     * @access public
     * @return PDO
     * @throws \Exception
     */
    public function connect()
    {
        if (!$this->pdo) {
            try {
                $this->pdo =new PDO($this->dsn, $this->username, $this->password, $this->params);
            } catch (\PDOException $e) {
                throw $e;
            }
        }
        return $this->pdo;
    }


    /**
     * 执行查询 返回数据集
     * @param $sql
     * @param array $bind
     * @return array
     * @throws \Exception
     */
    public function query($sql, $bind = [])
    {
        $this->execute($sql,$bind);
        $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
        if ($procedure){
            $item = [];
            do {
                $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
                if ($result) {
                    $item[] = $result;
                }
            } while ($this->PDOStatement->nextRowset());

        }else{
            $item=$this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        $this->PDOStatement=null;
        return $item;
    }


    /**
     * SQL指令安全过滤
     * @param $str
     */
    public function quote($str){
        $pdo=$this->connect();
        $pdo->quote($str);
    }

    public function value($sql, $bind ){
        $this->execute($sql,$bind);
        return $this->PDOStatement->fetchColumn();
    }

    /**
     * 执行sql
     * @param $sql
     * @param array $bind
     * @throws \Exception
     */
    public function execute($sql, $bind = [])
    {
        $pdo=$this->connect();
        // 根据参数绑定组装最终的SQL语句
         $this->logSql($sql, $bind);
        //释放前次的查询结果
        $this->PDOStatement=null;

        try {
            // 调试开始
            // 预处理
            $this->PDOStatement = $pdo->prepare($sql);
            // 参数绑定
            $this->bindValue($bind);
            // 执行查询

            $this->PDOStatement->execute();
        } catch (\PDOException $e) {
            $error=$e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if($error[1]==2006||$error[1]==2013){
                $this->pdo=null;
                 $this->connect();
                $this->execute($sql,$bind);
            }else{
                throw $e;
            }
        }
    }

    public function userDb($db){
        $pdo=$this->connect();
        // 根据参数绑定组装最终的SQL语句
        try {
             $pdo->exec("use ".$db);
              } catch (\PDOException $e) {
            $error=$e->errorInfo;
            //2006和2013表示表示连接失败,需要重连接
            if($error[1]==2006||$error[1]==2013){
                $this->pdo=null;
                $this->connect();
                $this->userDb($db);
            }else{
                throw $e;
            }
        }

    }

    public function rowCount(){
        return $this->PDOStatement->rowCount();
    }

    /**
     * 根据参数绑定组装最终的SQL语句
     * @access public
     * @param string    $sql 带参数绑定的sql语句
     * @param array     $bind 参数绑定列表
     * @return string
     */
    private function logSql($sql, array $bind = [])
    {
        if(!Config::get('app','debug')){
            return;
        }
        if ($bind) {
            foreach ($bind as $key => $val) {
                $value = is_array($val) ? $val[0] : $val;
                $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;
                if (PDO::PARAM_STR == $type) {
                    $value = $this->pdo->quote($value);
                } elseif (PDO::PARAM_INT == $type && '' === $value) {
                    $value = 0;
                }
                // 判断占位符
                $sql = is_numeric($key) ?
                    substr_replace($sql, $value, strpos($sql, '?'), 1) :
                    str_replace(
                        [':' . $key . ')', ':' . $key . ',', ':' . $key . ' '],
                        [$value . ')', $value . ',', $value . ' '],
                        $sql . ' ');
            }
        }
        $this->queryStr =rtrim($sql);
        Log::debug($this->queryStr,'sql');
        return;
    }

    /**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     * @param array $bind
     * @throws \Exception
     */
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                }
                 $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                 $this->PDOStatement->bindValue($param, $val);
            }
        }
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->connect();
        $this->transTimes++;
        if (1 == $this->transTimes) {
            $this->pdo->beginTransaction();
        }
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
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
     * @access public
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
     * @param \Closure $closure
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function runInTrans(\Closure $closure){
        $this->startTrans();
        try {
            $result=$closure();
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
     * @param array $sqlArray
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
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }
    /**
     * 获取最近插入的ID
     * @access public
     * @param string  $sequence     自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return $this->pdo->lastInsertId($sequence);
    }

    /**
     * 获取最近的错误信息
     * @access public
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
        if ('' != $this->queryStr) {
            $error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        return $error;
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 释放查询
        $this->PDOStatement=null;
        // 关闭连接
        $this->pdo=null;
    }

    /**
     * 获取数据字段
     * @param $table
     */
    public abstract function getFields($table);

    public abstract function getTables();

    public abstract function getPkField($table);
}