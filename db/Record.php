<?php
namespace rap\db;
use rap\cache\Cache;
use rap\ioc\Ioc;
use rap\storage\Storage;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/22
 * Time: 上午10:28
 */
class Record{

    private $_db_data=[];
    public function cacheKeys(){
        return [];
    }

    /**
     * @return Connection
     */
    protected function getConnection(){
        return Ioc::get(Connection::class);
    }

    public function setDbData($items){
        $_fields= $this->getFields();
        foreach ($items as $item=>$value) {
            $type=$_fields[$item];
            if($type=='json'){
                if(is_string($value)){
                    $this->_db_data[$item]=$value;
                    $value=json_decode($value,true);
                }else{
                    $this->_db_data[$item]=json_encode($value);
                }
            }else if($type=='attach'){
                if(is_string($value)){
                    $this->_db_data[$item]=$value;
                    $value=json_decode($value,true);
                }else{
                    $this->_db_data[$item]=json_encode($value);
                }
                $values=[];
                foreach ($value as $v) {
                    $type='default';
                    if(key_exists('type',$v)){
                        $type=$v['type'];
                    }
                    $url=$v['url'];
                    $domian=Storage::getStorage($type)->getDomain();
                    if(!(strpos($url, 'http') === 0)){
                        $v['url']=$domian.$url;


                    }
                    $values[]=$v;
                }
                $value=$values;
            }else if($type=='int'){
                $value=(int)$value;
                $this->_db_data[$item]=$value;
            }else if($type=='boolean'){
                $value=(int)$value;
                $this->_db_data[$item]=$value;
                $value=$value==1?true:false;
            }else if($type=='float'){
                $value=(float)$value;
                $this->_db_data[$item]=$value;
            }else if($type=='time'){
                $time=(int)$value;
                if($time.""==$value.""){
                    $this->_db_data[$item]=(int)$value;
                    $value=date("Y-m-d H:i:s",$this->_db_data[$item]);
                }else{
                    $this->_db_data[$item]=strtotime($value);
                }
            }else if($type=='date'){
                $time=(int)$value;
                if($time.""==$value){
                    $this->_db_data[$item]=(int)$value;
                    $value=date("Y-m-d",$this->_db_data[$item]);
                }else{
                    $this->_db_data[$item]=strtotime($value);
                }
            }
            $this->$item=$value;
        }

    }

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
            if($value===null)continue ;
            $oldValue=$this->_db_data[$field];
            if($type=='json'||$type=='attach'&&!is_string($value)){
               $value=json_encode($value);
            }
            if($value==$oldValue&&$oldValue!=null)continue;
            if(!is_null($value)){
                if($value==='null'){
                    $value=null;
                }else{
                    if($type=='int'){
                        $value=(int)$value;
                    }else if($type=='float'){
                            $value=(float)$value;
                    } else if($type=='time'||$type=='date'){
                        $time=(int)$value;
                        if($time.""!=$value){
                            $value=strtotime($value);
                        }
                    }else if($type=='attach'){
                       $attach=json_decode($value,true);
                        $values=[];
                        foreach ($attach as $item) {
                            $type='default';
                            if(key_exists('type',$item)){
                                $type=$item['type'];
                            }
                            $url=$item['url'];
                            $domian=Storage::getStorage($type)->getDomain();
                            if($domian){
                                $url=str_replace($domian,"",$url);
                            }
                            $item['url']=$url;
                            $values[]=$item;
                        }
                        $value=json_encode($values);
                    }
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
        if(property_exists(get_called_class(),$create_time)){
            $data[$create_time]=time();
        }
        $pk_value=DB::insert($this->getTable(),$data,$this->getConnection());
        $this->$pk=$pk_value;
    }

    /**
     * 更新
     */
    public function update(){
        $pk=$this->getPkField();
        $where[$pk]=$this->$pk;
        $data=$this->getDBData();
        if(!$data)return;
        $update_time='update_time';
        if(property_exists(get_called_class(),$update_time)){
            $data[$update_time]=time();
        }
        DB::update($this->getTable(),$data,$where,$this->getConnection());
        $cacheKeys=$this->cacheKeys();
        if($cacheKeys){
            foreach ($cacheKeys as $cacheKey) {
                $cks=explode(',',$cacheKey);
                sort($cks);
                $oldV=[];
                foreach ($cks as $ck) {
                    $oldV[]=$this->_db_data[$ck];
                }
                $cacheKey=implode(",",$cks);
                $cache_key="record_".get_called_class()."_".$cacheKey."_".implode(",",$oldV);
                Cache::remove($cache_key);
            }
        }
        $cache_key="record_".get_called_class().$this->$pk;
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
            if(property_exists(get_called_class(),$delete_time)){
                if(!$force){
                    $this->$delete_time=time();
                    $this->update();
                    return;
                }
            }
            DB::delete($this->getTable(),null,$this->getConnection())->where($pk,$id)->excuse();
        }
        $cacheKeys=$this->cacheKeys();
        if($cacheKeys){
            foreach ($cacheKeys as $cacheKey) {
                $cks=sort(explode(',',$cacheKey));
                $oldV=[];
                foreach ($cks as $ck) {
                    $oldV[]=$this->_db_data[$ck];
                }
                $cacheKey=implode(",",$cks);
                $cache_key="record_".get_called_class()."_".$cacheKey."_".implode(",",$oldV);
                Cache::remove($cache_key);
            }
        }
        $cache_key="record_".get_called_class().$id;
        Cache::remove($cache_key);
    }

    /**
     * 查找一个对象
     * @param array $where ['a'=>'a','b'=>'b']
     * @return $this;
     */
    public static function find(array $where){
        $model= get_called_class();
        /* @var $t Record  */
        $t=new $model;
        $cacheKeys=$t->cacheKeys();
        ksort($where);
        $key=implode(",",array_keys($where));
        $cache_key=null;
        if($cacheKeys){
            foreach ($cacheKeys as $cacheKey) {
                $m=explode(',',$cacheKey);
                sort($m);
                $cacheKey=implode(",",$m);
                if($key==$cacheKey){
                    $cache_key="record_".$model."_".$key."_".implode(",",array_values($where));
                    $data=Cache::get($cache_key);
                    if($data){
                        $data=json_decode($data,true);
                        $t->setDbData($data);
                        return $t;
                    }
                    break;
                }
            }
        }
        $data=DB::select($t->getTable(),$t->getConnection())->where($where)->setItemClass($model)->find();
        if($cache_key&&$data){
            Cache::set($cache_key,json_encode($data));
        }
        return $data;
    }
    /**
     * 查找一个对象
     * @param array $where ['a'=>'a','b'=>'b']
     * @return $this
     */
    public static function findCreate(array $where){
        $item=self::find($where);
        if(!$item){
            $model= get_called_class();
            /* @var $item Record  */
            $item=new $model;
            $item->fromArray($where);
        }
        return $item;
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
     * @param int $cache_time 缓存时间
     * @return $this;
     */
    public static function get($id,$cache= false,$cache_time=0){
        $model= get_called_class();
        $cache_key="record_".$model.$id;
        $data=Cache::get($cache_key);
        /* @var $model Record  */
        $model=new $model;
        if($cache&&$data){
            $data=json_decode($data,true);
            $model->setDbData($data);
            return $model;
        }
        $pk=$model->getPkField();
        $where[ $pk ] = $id;
        $data= $model::find($where);
        if($cache&&$data){
            Cache::set($cache_key,json_encode($data),$cache_time);
        }
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
        $select=DB::select($model->getTable(),$model->getConnection())->setItemClass(get_called_class());
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
        return $select;
    }


    /**
     * 获取字段
     * @return mixed
     */
    public function getFields(){
        $connection=$this->getConnection();
        return $connection->getFields($this->getTable());
    }

    /**
     * 获取主键
     * @return mixed
     */
    public function getPkField(){
        $connection=$this->getConnection();
        return $connection->getPkField($this->getTable());
    }

    /**
     * 获取表
     * @return string
     */
    public function getTable(){
        $table=get_called_class();
        return $table;
    }

    /**
     * @param $str
     */
    public  function fromString($str){
        $array=json_decode($str,true);
        $this::fromArray($array);
    }

    /**
     * @param $array
     */
    public  function fromArray($array){
        foreach ($array as $key=>$value) {
            if(isset($value)){
                $this->$key=$value;
            }
         }
    }



}