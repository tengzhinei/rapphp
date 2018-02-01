<?php
namespace rap\db;
use rap\cache\Cache;
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
        $cache_key="record_".get_class().$pk;
        Cache::remove($cache_key);
    }


    /**
     * 删除当前对象
     * @param $force
     */
    public  function delete($force=false){
        $pk=$this->getPkField();
        $id=$this->$pk;
        if(isset($id)) {
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
        $cache_key="record_".get_class().$id;
        Cache::remove($cache_key);


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
     *  根据主键获取对象
     * @param $id
     * @param bool $cache   是否使用缓存
     * @param null $cache_time 缓存时间
     * @return mixed|Record|string
     */
    public static function get($id,$cache= false,$cache_time=null){
        $model= get_called_class();
        $cache_key="record_".$model.$id;
        $data=Cache::get($cache_key);
        /* @var $model Record  */
        $model=new $model;
        if($cache&&$data){
            $data=json_decode($data,true);
            foreach ($data as $key=>$value) {
                $model->$key=$value;
            }
            return $model;
        }
        $pk=$model->getPkField();
        $where[ $pk ] = $id;
        $data= $model::find($where);
        Cache::set($cache_key,json_encode($data),$cache_time);
        return $data;
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
    public function getFields(){
        /* @var $connection  Connection */
        $connection=Ioc::get(Connection::class);
        return $connection->getFields($this->getTable());
    }

    /**
     * 获取主键
     * @return mixed
     */
    public function getPkField(){
        $connection=Ioc::get(Connection::class);
        return $connection->getPkField($this->getTable());
    }

    /**
     * 获取表
     * @return string
     */
    public function getTable(){
        $table=get_class($this);
        return $table;
    }

}