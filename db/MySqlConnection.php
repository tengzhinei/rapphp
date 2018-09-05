<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/27
 * Time: 下午9:38
 */

namespace rap\db;


class MySqlConnection extends Connection{

    public function getFields($table){
        $sql = 'SHOW COLUMNS FROM `' . $table . '`';
        $result = $this->query($sql);
        $fields=[];
        if ($result) {
            foreach ($result as $key => $val) {
                $val   = array_change_key_case($val);
                $type=$val['type'];
                $t='string';

                if(strpos($type,'int')>-1){
                    $t="int";
                }
                if(strpos($type,'float')>-1){
                    $t="float";
                }
                if(strpos($type,'decimal')>-1){
                    $t="float";
                }
                $fields[$val['field']]=$t;
            }
        }
        return $fields;
    }


    public function getTables()
    {
        $sql = 'SHOW TABLES ';
        $items=$this->query($sql);
        $result=[];
        foreach ($items as $item) {
           $key=array_keys($item)[0];
            $result[]= $item[$key];
        }
        return $result;
    }

    public  function getPkField($table){
        $sql="select database()";
        $db_name=$this->value($sql);
        $sql = "SELECT COLUMN_NAME as name FROM INFORMATION_SCHEMA.Columns WHERE 
        TABLE_NAME =? AND 
        TABLE_SCHEMA=? AND COLUMN_KEY=?";
        $pk=$this->value($sql,[$table,$db_name,'PRI']);
        return $pk;
    }

    public function getFieldsComment($table) {
        $sql="select database()";
        $db_name=$this->value($sql);
        $sql = "SELECT COLUMN_NAME as name,COLUMN_COMMENT as comment FROM INFORMATION_SCHEMA.Columns WHERE 
        table_name=? AND 
        table_schema=?";
        $values=$this->query($sql,[$table,$db_name]);
        $data=[];
        foreach ($values as $item) {
            $data[$item['name']]=$item['comment'];
        }
       return $data;
    }


}