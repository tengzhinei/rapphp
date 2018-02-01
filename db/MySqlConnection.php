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
        return 'id';
    }


}