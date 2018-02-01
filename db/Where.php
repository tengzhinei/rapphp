<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/21
 * Time: 下午3:59
 */

namespace rap\db;

/**
 * 搜索条件
 * Class Where
 * @package rap\db
 */
class Where{
    static $exp=["eq"=>"=",
        "neq"=>"<>",
        "gt"=>">",
        "lt"=>">",
        "elt"=>">",
    ];

    /**
     * where 条件
     * @var array
     */
    private $wheres=[];
    /**
     * where 参数
     * @var array
     */
    private $params=[];

    /**
     *
     * @param $field
     * @param null $op
     * @param null $condition
     * @return $this
     */
    public function where($field, $op = null, $condition = null){
        $this->addWhere("AND",$field,$op,$condition);
        return $this;
    }



    /**
     * Or 连接条件
     * @param $field
     * @param null $op
     * @param null $condition
     * @return $this
     */
    public function whereOr($field, $op = null, $condition = null){
        $this->addWhere("OR",$field,$op,$condition);
        return $this;
    }

    /**
     * XOr 连接条件
     * @param $field
     * @param null $op
     * @param null $condition
     * @return $this
     */
    public function whereXOr($field, $op = null, $condition = null){
        $this->addWhere("XOR",$field,$op,$condition);
        return $this;
    }

    /**
     * 获取where条件的sql语句
     * @return string
     */
    protected function whereSql(){
        $this->params=[];
        $sql=$this->parseWhere($this->wheres,$this->params);
        if($sql){
            $sql=" WHERE ".$sql;
        }
        return $sql;
    }


    /**
     * 添加条件
     * @param $logic
     * @param $field
     * @param null $op
     * @param null $condition
     */
    private function addWhere($logic, $field, $op = null, $condition = null){
        if($field instanceof \Closure){
            $select = new Where();
            $field($select);
            $where=$select->wheres;
            $this->wheres[]=[
                'child'=>$where,
                'logic'=>$logic
            ];
        }else{
            if(self::$exp[$op]){
                $op=self::$exp[$op];
            }
            if(is_null($condition)&&'null'!=$op&&'not null'!=$op){
                $condition=$op;
                $op='=';
            }
            if(is_array($field)){
                foreach ($field as $item=>$value) {
                    $where=[
                        'field'=>$item
                    ];
                    if(is_array($value)){
                        $where['op']=$value[0];
                        $where['condition']=$value[1];
                    }else{
                        $where['op']='=';
                        $where['condition']=$value;
                    }
                    $this->wheres[]=$where;
                }
            }else{
                $this->wheres[]=[
                    'field'=>$field,
                    'op'=>$op,
                    'logic'=>$logic,
                    'condition'=>$condition
                ];
            }


        }
    }
    /**
     * where条件的sql
     * @param $wheres
     * @param $data
     * @return string
     */
    private function parseWhere($wheres, &$data){
        $sql="";
        foreach ($wheres as $where) {
            if (isset($where['child'])){
                $sql.=" ".$where['logic']." (";
                $sql.= $this->parseWhere($where['child'],$data);
                $sql.=")";
            }else{
                if($sql){
                    $sql.=" ".$where['logic'];
                }
                $op=$where['op'];
                if($op=='null'){
                    $op="is null";
                    $sql.= " ".$where['field'].' '.$op;
                } else if($op=='not null'){
                    $op="is not null";
                    $sql.= " ".$where['field'].' '.$op;
                } else if($op=='in' ||$op=='not in'){
                    $condition=$where['condition'];
                    if(!is_array($condition)){
                        $condition=explode(',',$condition);
                    }
                    $p=[];
                    foreach ($condition as $item) {
                        $p[].="?";
                        $data[]=$item;
                    }
                    $p=implode(",",$p);
                    $op.="(".$p.")";
                    $sql.= " ".$where['field'].' '.$op;
                }else if($op=='between'||$op=='not between'){
                    $sql.= " ".$where['field'].' '.$op. ' ? and ? ';
                    $data[]=$where['condition'][0];
                    $data[]=$where['condition'][1];
                }else{
                    if(key_exists($op,Where::$exp)){
                        $op=Where::$exp[$op];
                    }
                    $sql.= " ".$where['field'].' '.$op;
                    $sql.=' ? ';
                    $data[]=$where['condition'];
                }
            }
        }
        return $sql;
    }

    /**
     * 获取where条件的参数
     * @return array
     */
    protected function whereParams(){
        return $this->params;
    }

    protected $order='';
    /**
     * 排序 支持 ['timefile'=>'desc','name'=>asc] 或者直接 order("time desc","name asc")
     * @param $field
     * @return $this
     */
    public function order($field){
        $order=[];
        if(is_array($field)){
            foreach ($field as $key=>$value) {
                $order[]=$key.' '.$value;
            }
        }else{
            $order=func_get_args();
        }

        $order =  implode(',', $order);
        $this->order=!empty($order) ? ' ORDER BY ' . $order : '';
        return $this;
    }
    protected $limit='';

    /**
     * limit限制
     * @param $offset
     * @param int $length
     * @return $this
     */
    public function limit($offset, $length=0){
        if($length==0){
            $length=$offset;
            $offset=0;
        }
        $this->limit=" LIMIT ".(int)$offset.','.(int)$length;
        return $this;
    }

    protected $lock='';
    /**
     * 锁行 请在事务中使用
     * @return $this
     */
    public function lock(){
        $this->lock=" FOR UPDATE ";
        return $this;
    }
}