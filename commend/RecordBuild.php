<?php
namespace rap\commend;
use rap\db\Connection;
use rap\ioc\Ioc;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/24
 * Time: 下午3:47
 */
class RecordBuild{

    function convertUnderline ( $str , $ucfirst = true)
    {
        while(($pos = strpos($str , '_'))!==false)
            $str = substr($str , 0 , $pos).ucfirst(substr($str , $pos+1));

        return $ucfirst ? ucfirst($str) : $str;
    }

    /**
     *
     */
    public function create($table_name,$prefix){
        /* @var Connection $connection  */
        $connection=Ioc::get(Connection::class);
        $name=$table_name;
        if($prefix){
            $name=str_replace($prefix.'_',"",$table_name);
        }
        $name=$this->convertUnderline($name);
        $namespace="app\\sass\\model";
        $fields=$connection->getFields($table_name);
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
        echo $txt;
    }

}