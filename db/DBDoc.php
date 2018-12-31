<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2018/12/31 11:42 AM
 */

namespace rap\db;


use rap\swoole\pool\Pool;

class DBDoc {

    public static function getDoc() {
        $connection = Pool::get(Connection::class);
        /* @var $connection Connection */
        $tables = $connection->getTables();
        $tables_comments = $connection->getTableComments();
        $doc = [];
        foreach ($tables as $table) {
            $fields = $connection->getFields($table);
            $comments = $connection->getFieldsComment($table);
            $comment = $tables_comments[ $table ];
            $names = '####';
            if ($comment) {
                $names .= $comment;
            }
            $names .= '(' . $table . ')';
            $doc[] = $names;
            $doc[] = '------------';
            $doc[] = '|  名称 |含义   |类型|';
            $doc[] = '| ------------ | ------------ | ------------ |';
            foreach ($fields as $field => $value) {
                $doc[] = '| ' . $field . ' | ' . $comments[ $field ] . '|' . $value . ' |';
            }
            $doc[] = '';
        }
        $doc = implode("\n", $doc);

        return $doc;
    }

}