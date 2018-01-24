<?php
namespace rap\db;
use rap\help\ArrayHelper;
use rap\ioc\Ioc;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/22
 * Time: 上午10:28
 */
class Record{

    /**
     * 保存 如果主键存在就更新,否则插入
     */
    public function save(){
        $pk=$this->getPkField();
        if($this->$pk){
            $this->update();
        }else{
            $this->insert();
        }
    }

    /**
     * 获取保存的对象
     * @return array
     */
    private function getDBData(){
        $data=[];
        $fields=$this->getFields();
        foreach ($fields as $field=>$type){
            $value = $this->$field;
            if(!is_null($value)){
                if($type=='int'){
                    $value=(int)$value;
                }else
                if($type=='float'){
                    $value=(float)$value;
                }
                $data[$field]=$value;
            }
        }
        return $data;
    }

    /**
     * 插入
     */
    public function insert(){
        $pk=$this->getPkField();
        $data=$this->getDBData();
        $create_time='create_time';
        if(property_exists(get_class(),$create_time)){
            $data[$create_time]=time();
        }
        $pk_value=DB::insert($this->getTable(),$data);

        $this->$pk=$pk_value;
    }

    /**
     * 更新
     */
    public function update(){
        $pk=$this->getPkField();
        $where[$pk]=$this->$pk;
        $data=$this->getDBData();
        $update_time='update_time';
        if(property_exists(get_class(),$update_time)){
            $data[$update_time]=time();
        }
        DB::update($this->getTable(),$data,$where);
    }


    /**
     * 删除当前对象
     * @param $force
     */
    public  function delete($force=false){
        $pk=$this->getPkField();
        $id=$this->$pk;
        if($id) {
            $where[ $pk ] = $id;
            $delete_time="delete_time";
            if(property_exists(get_class(),$delete_time)){
                if(!$force){
                    $this->$delete_time=time();
                    $this->update();
                    return;
                }
            }
            DB::delete($this->getTable())->where($pk,$id)->excuse();
        }
    }

    /**
     * 查找一个对象
     * @param array $where ['a'=>'a','b'=>'b']
     * @return Record|string
     */
    public static function find(array $where){
        $model= get_called_class();
        /* @var $model Record  */
        $model=new $model;
        $data=DB::select($model->getTable())->where($where)->find();
        if($data){
            foreach ($data as $key=>$value) {
                $model->$key=$value;
            }
            return $model;
        }
        return $model;
    }

    /**
     * 静态删除  destroy方法不管delete_time字段
     * @param $id
     */
    public static function destroy($id){
        $model= get_called_class();
        /* @var $model Record  */
        $model=new $model;
        $pk=$model->getPkField();
        $model->$pk=$id;
        $model->delete();
    }

    /**
     * 根据主键获取对象
     * @param $id
     * @return Record|string
     */
    public static function get($id){
        $model= get_called_class();
        /* @var $model Record  */
        $model=new $model;
        $pk=$model->getPkField();
        $where[ $pk ] = $id;
        return $model::find($where);
    }

    /**
     * 检索
     * @param string $fields
     * @param bool $contain
     * @return Select
     */
    public static function select($fields='', $contain=true){
        $model= get_called_class();
        /* @var $model Record  */
        $model=new $model;
        $select=DB::select($model->getTable())->setItemClass(get_called_class());
        if($fields){
            if(!$contain){
                $fieldAll=$model->getFields();
                $fields=explode(",",$fields);
                $need=[];
                foreach ($fieldAll as $field=>$value) {
                    if(!in_array($field,$fields)){
                        $need[]=$field;
                    }
                }
                $fields=implode(",",$need);
            }
            $select->fields($fields);
        }
        $delete_time="delete_time";
        if(property_exists(get_class(),$delete_time)){

        }
        return $select;
    }


    /**
     * 获取字段
     * @return mixed
     */
    protected function getFields(){
        /* @var $connection  Connection */
        $connection=Ioc::get(Connection::class);
        return $connection->getFields($this->getTable());
    }

    /**
     * 获取主键
     * @return mixed
     */
    protected function getPkField(){
        $connection=Ioc::get(Connection::class);
        return $connection->getPkField($this->getTable());
    }

    /**
     * 获取表
     * @return string
     */
    protected function getTable(){
        $table=get_class($this);
        return $table;
    }

}