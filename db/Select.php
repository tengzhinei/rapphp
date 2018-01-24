<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午3:14
 */

namespace rap\db;


use rap\help\ArrayHelper;
use rap\ioc\Ioc;

class Select extends Where{

use Comment;
    private $table='';

    private $fields=[];

    private $joins=[];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param $table
     * @param Connection|null $connection
     * @return Select
     */
    public static function table($table,Connection $connection=null){
        $select=new Select();
        $select->table=$table;
        if(!$connection){
            $connection  =Ioc::get(Connection::class);
        }
        $select->connection=$connection;
        return $select;
    }

    /**
     * @param $field
     * @param string $tableName
     * @param string $alias
     * @return $this
     */
    public function fields($field,  $tableName = '', $alias = ''){
        if (empty($field)) {
            return $this;
        }
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }
        $real_field=[];
        foreach ($field as $val) {
            if ($tableName) {
                $val = $tableName . '.' . $val . ($alias ? ' AS ' . $alias.'_' . $val : '');
            }
            $real_field[] = $val;
        }

        $this->fields = array_merge($this->fields,$real_field);
        return $this;
    }

    /**
     * @param $join
     * @param null $condition
     * @param string $type
     * @return $this
     */
    public function join($join, $condition = null, $type = 'LEFT'){
        $this->joins[]=['join'=>$join,
            'condition'=>$condition,
            'type'=>$type
        ];
        return $this;
    }

   private $distinct="";
   public function distinct(){
        $this->distinct="DISTINCT";
   }
    private $having='';
    public function having($having){
        if($having){
            $this->having=' HAVING '.$having;
        }
    }


    private $group='';

    public function group($group){
         $this->group=!empty($group) ?' GROUP BY '.$group:'';
        return $this;
    }

    protected $selectSql    = '%COMMENT% SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %LOCK%';

    public function findAll(){
        $data = $this->connection->query($this->getSql(),$this->whereParams());
        if($this->clazz){
            $results=[];
            /* @var $item   */
            foreach ($data as $item) {
                $clazz=$this->clazz;
                $result=new $clazz;
                ArrayHelper::copyArray($item,$result);
                $results[]=$result;
            }
            return $results;
        }
        return $data;
    }

    private $clazz;

    /**
     * @param $class
     * @return $this
     */
    public function setItemClass($class){
            $this->clazz=$class;
        return $this;
    }


    private function getSql(){
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->table,
                $this->distinct,
                $this->parseField(),
                $this->parseJoin(),
                $this->whereSql(),
                $this->group,
                $this->having,
                $this->order,
                $this->limit,
                $this->lock,
                $this->comment,
                $this->force
            ], $this->selectSql);
        echo $sql;
        return $sql;
    }

    private $force="";

    /**
     * @param $index
     * @return $this
     */
    public function force($index){
      $this->force=sprintf(" FORCE INDEX ( %s ) ", $index);
        return $this;
    }

    public function find(){
        $this->limit(1);
        $list=$this->findAll();
        if($list){
            return $list[0];
        }
        return null;
    }

    public function page(){

    }

    public function value($field){
        $this->fields=[];
        $this->fields($field);
        return $this->connection->value($this->getSql(),$this->whereParams());
    }
    public function count($field = '*'){
        return (int) $this->value('COUNT(' . $field . ') AS count');
    }

    public function sum($field){
        return (int) $this->value('SUM(' . $field . ') AS count');
    }

    public function max($field){
        return (int) $this->value('MAX(' . $field . ') AS count');
    }

    public function min($field){
        return (int) $this->value('MIN(' . $field . ') AS count');
    }
    public function avg($field){
        return (int) $this->value('AVG(' . $field . ') AS count');
    }

    private function parseField()
    {
        $fieldsStr="*";
        if ($this->fields) {
            $fieldsStr = implode(',', $this->fields);
        }
        return $fieldsStr;
    }

    /**
     * 分析join
     * @return string
     */
    private function parseJoin()
    {
        $joinStr = '';
        foreach ($this->joins as $join) {
            $joinStr.=' '.$join['type'].' join '.$join['join'].' on '.$join['condition'];
        }
        return $joinStr;
    }

}