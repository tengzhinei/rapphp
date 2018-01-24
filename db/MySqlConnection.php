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

    }


    public function getTables()
    {
        $sql = 'SHOW TABLES ';
        return $sql;
    }

    public  function getPkField($table){
        return 'id';
    }


}