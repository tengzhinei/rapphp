<?php
namespace rap\console\command;
use rap\console\Command;
use rap\db\Connection;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/24
 * Time: 下午3:47
 */
class RecordBuild extends Command{

    /**
     * @var Connection
     */
    private $connection;


    public function _initialize(Connection $connection){
        $this->connection=$connection;
    }


    public function configure(){
        $this->name('record')
            ->asName("生成record")
            ->param("s",true,'需要生成的表的前缀',"")
            ->param("p",true,'需要去除的表的前缀',"")
            ->param("n",true,'类命名空间',"")
            ->des("会根据search去查数据库生成所有表前缀为search的record模型文件,
生成的类文件前缀去除prefix
生成的文件在 runtime/model
            ");
    }



   private function convertUnderline ( $str , $ucfirst = true)
    {
        while(($pos = strpos($str , '_'))!==false)
            $str = substr($str , 0 , $pos).ucfirst(substr($str , $pos+1));
        return $ucfirst ? ucfirst($str) : $str;
    }

    public function run($s='', $p='', $n=''){
        set_time_limit(0);
        $tables=$this->connection->getTables();
        foreach ($tables as $table) {
            if(strpos($table,$s)>-1){
                $this->create($table,$p,$n);
            }
        }
    }

    public function create($table_name,$prefix,$namespace){
        /* @var Connection $connection  */
        $name=$table_name;
        if($prefix){
            $name=str_replace($prefix.'_',"",$table_name);
        }
        $name=$this->convertUnderline($name);
        $fields=$this->connection->getFields($table_name);
        $txt = <<<EOF
<?php
namespace $namespace;
use rap\db\Record;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/24
 * Time: 下午4:30
 */
class $name extends Record{
    public function getTable(){
        return "$table_name";
    }
    public function getFields(){
        return [

EOF;
        $i=0;
        foreach ($fields as $key=>$value) {
            $txt.="            '$key'=>'$value'";
            if($i<count($fields)-1){
                $txt.=",\r\n";
            }else{
                $txt.="\r\n";
            }
            $i++;
        }
        $txt.= <<<EOF
        ];
    }
EOF;
        foreach ($fields as $key=>$value) {
            $txt.="    public $$key;\r\n";
        }
        $txt.="}";
        mkdir(getcwd().'/runtime/model/');
        file_put_contents(getcwd().'/runtime/model/'.$name.'.php',$txt);
    }

}