<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午4:04
 */
namespace rap\db;


use rap\ioc\Ioc;

class Update extends Where{
    use Comment;

    private $table;

    private $data;

    protected $updateSql    = '%COMMENT% UPDATE %TABLE% SET %FIELD% %WHERE% %ORDER%%LIMIT% %LOCK%';
    /**
     * @var Connection
     */
    private $connection;


    /**
     * @param $table
     * @param Connection|null $connection
     * @return Update
     */
    public static function table($table,Connection $connection=null){
        $update=new Update();
        $update->table=$table;
        if(!$connection){
            $connection  =Ioc::get(Connection::class);
        }
        $update->connection=$connection;
        return $update;
    }


    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key,$value){
        if(is_array($key)){
            $this->data = array_merge($this->data,$key);
        }else{
            $this->data[$key]=$value;
        }
        return $this;
    }

    public function excuse(){
        $fields=[];
        $values=[];
        foreach ($this->data as $field=>$value) {
            $fields[]=$field."=?";
            $values[]=$value;
        }
        $fields=implode(' , ', $fields);
        $sql = str_replace(
            ['%TABLE%', '%FIELD%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->table,
                $fields,
                $this->whereSql(),
                $this->order,
                $this->limit,
                $this->lock,
                $this->comment,
            ], $this->updateSql);
        $this->connection->execute($sql,array_merge($values,$this->whereParams()));
        return  $this->connection->rowCount();
    }


    public static function update($table,$data,$where,Connection $connection=null){
        $update = Update::table($table,$connection);
        foreach ($data as $field=>$value) {
            $update->set($field,$value);
        }
        if(is_array($where)){
            foreach ($where as $field=>$value) {
                $update->where($field,$value);
            }
        }else{
            $update->where("id",$where);
        }
        $update->excuse();
    }

}