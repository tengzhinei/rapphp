<?php
namespace rap\console\command;

use rap\console\Command;
use rap\db\Connection;
use rap\swoole\pool\Pool;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/24
 * Time: 下午3:47
 * @SuppressWarnings(PHPMD)
 */
class RecordBuild extends Command
{


    public function configure()
    {
        $this->name('record')
             ->asName("生成record")
             ->param("s", true, '需要生成的表的前缀', "")
             ->param("p", true, '需要去除的表的前缀', "")
             ->param("n", true, '类命名空间', "")
             ->des("会根据search去查数据库生成所有表前缀为search的record模型文件,
生成的类文件前缀去除prefix
生成的文件在 runtime/model
            ");
    }


    private function convertUnderline($str, $ucfirst = true)
    {
        while (($pos = strpos($str, '_')) !== false) {
            $str = substr($str, 0, $pos) . ucfirst(substr($str, $pos + 1));
        }
        return $ucfirst ? ucfirst($str) : $str;
    }

    public function run($s = '', $p = '', $n = '')
    {
        $this->initWork();
        set_time_limit(0);
        /*  @var Connection $connection */
        $connection = Pool::get(Connection::class);
        $tables = $connection->getTables();
        foreach ($tables as $table) {
            if ($s) {
                if (strpos($table, $s) > -1) {
                    $this->create($table, $p, $n);
                }
            } else {
                $this->create($table, $p, $n);
            }
        }
    }

    /**
     * @param $table_name
     * @param $prefix
     * @param $namespace
     * @SuppressWarnings(PHPMD)
     */
    public function create($table_name, $prefix, $namespace)
    {
        if (!$namespace) {
            $namespace = APP_DIR.'\model';
        }
        $connection = Pool::get(Connection::class);
        /* @var Connection $connection */
        $name = $table_name;
        if ($prefix) {
            $name = str_replace($prefix . '_', "", $table_name);
        }
        $name = $this->convertUnderline($name);
        $fields = $connection->getFields($table_name);
        $comments = $connection->getFieldsComment($table_name);

        $date = date("Y-m-d", time());
        $time = date("H:i", time());
        $pk_field = $connection->getPkField($table_name);
        $txt = <<<EOF
<?php
/**
 * 
 * User: RapPhp auto build
 * Date: $date
 * Time: $time
 */
namespace $namespace;
use rap\db\Record;

class $name extends Record {
    
    /**
     * 获取表名
     * @return string
     */     
    public function getTable() {
        return "$table_name";
    }
    
    /**
     * 获取主键
     * @return string
     */
    public function getPkField() {
        return "$pk_field";
    }
EOF;
        if ($fields['version']) {
            $txt .= <<<EOF
     /**
     * 获取数据版本号字段
     * @return string
     */
    public function getVersionField() {
        return "version";
    }
EOF;
        }

        $txt .= <<<EOF
    /**
     * 获取数据库字段
     * @return array
     */
    public function getFields() {
        return [

EOF;
        $i = 0;
        foreach ($fields as $key => $value) {
            $comment = $comments[ $key ];
            if (strpos($comment, 'json')>0||strpos($comment, 'object')>0||strpos($comment, 'array')>0) {
                $value='json';
            }
            if (strpos($comment, '时间')>0&&strpos($comment, '时间戳')===false) {
                $value='time';
            }
            if (strpos($comment, '日期')>0) {
                $value='date';
            }
            $txt .= "            '$key'=>'$value'";
            if ($i < count($fields) - 1) {
                $txt .= ",\r\n";
            } else {
                $txt .= "\r\n";
            }
            $i++;
        }
        $txt .= <<<EOF
        ];
    }

EOF;

        $txt .= "    /**** 对应数据库字段 start *****/\r\n\r\n";
        foreach ($fields as $key => $value) {
            $comment = $comments[ $key ];
            if (strpos($comment, 'json')>0||strpos($comment, 'object')>0||strpos($comment, 'array')>0) {
                $value='array';
            }
            $txt .="   /**\r\n";
            $txt .="     * $comment\r\n";
            $txt .="     * @var $value\r\n";
            $txt .="     */\r\n";
            $txt .= "    public $$key;\r\n ";
            $txt .= "    \r\n ";
        }
        $txt .= "    /**** 对应数据库字段 end *****/\r\n\r\n";
        $txt .= "}";
        mkdir(RUNTIME . "model" . DS);
        file_put_contents(RUNTIME . 'model' . DS . $name . '.php', $txt);
    }
}
