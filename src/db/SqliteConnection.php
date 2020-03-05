<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/4/29 11:27 AM
 */

namespace rap\db;

class SqliteConnection extends Connection
{

    public function getFields($table)
    {
        $sql = "PRAGMA  table_info(\"".$table."\")";
        $result = $this->query($sql);
        $fields = [];
        if ($result) {
            foreach ($result as $item) {
                $type = strtolower($item[ 'type' ]);
                $t = 'string';
                if (strpos($type, 'integer') > -1) {
                    $t = "int";
                }
                if (strpos($type, 'real') > -1) {
                    $t = "float";
                }
                $fields[ $item[ 'name' ] ] = $t;
            }
        }
        return $fields;
    }


    public function getTables()
    {
        $sql = 'select * from sqlite_master where type="table"; ';
        $items = $this->query($sql);
        $result=[];
        foreach ($items as $item) {
            if ($item['name']=='sqlite_sequence') {
                continue;
            }

            $result[] = $item[ 'name' ];
        }
        return $result;
    }

    public function getPkField($table)
    {
        $sql = "PRAGMA  table_info(\"".$table."\")";
        $items = $this->query($sql);
        foreach ($items as $item) {
            if ($item['pk']==1) {
                return $item['name'];
            }
        }
        return '';
    }

    public function getFieldsComment($table)
    {

        return [];
    }

    public function getTableComments()
    {
        return [];
    }
}
