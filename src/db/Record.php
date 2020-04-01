<?php
namespace rap\db;

use rap\aop\Event;
use rap\ioc\Ioc;
use rap\storage\Storage;
use rap\swoole\Context;
use rap\swoole\pool\Pool;
use rap\web\mvc\Search;
use rap\web\Request;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/22
 * Time: 上午10:28
 */
class Record implements \ArrayAccess, \JsonSerializable
{

    /**
     * 获取表名,包含 as 时会添加上as  如 User::table('u') 返回user u
     *
     * @param string $as
     *
     * @return string
     */
    public static function table($as = "")
    {
        $model = get_called_class();
        /* @var $model Record */
        $model = new $model;
        $table = $model->getTable();
        if ($as) {
            $table .= " " . $as;
        }
        return $table;
    }

    /**
     * 获取数据库所有字段名
     * @return array
     * @throws \Error
     */
    public static function fields()
    {
        $model = get_called_class();
        /* @var $model Record */
        $model = new $model;
        return array_keys($model->getFields());
    }


    /**
     * 是否延迟更新
     * @return bool
     */
    public function delayUpdate()
    {
        return false;
    }

    /**
     * 存在数据库里的数据
     * @var array
     */
    private $_db_data = [];


    public function getOldDbData()
    {
        return $this->_db_data;
    }

    /**
     * 供find 使用的缓存的可以  如['user_id,open_id','cat_id,good_id']
     * 同一组用,隔开 整体是数组
     * @return array
     */
    public function cacheKeys()
    {
        return [];
    }


    /**
     * 表明是否替换
     * @var bool
     */
    private $to_update = false;

    /**
     * 表明时候是替换
     *
     * @param bool $update
     */
    public function isUpdate($update = true)
    {
        $this->to_update = $update;
    }

    /**
     * 数据库字段组装对象
     *
     * @param $items
     *
     * @throws \Error
     * @throws \rap\exception\SystemException
     */
    public function fromDbData($items)
    {
        $this->to_update = true;
        $_fields = $this->getFields();
        $this->_db_data = $items;
        foreach ($items as $item => $value) {
            $type_p = $_fields[ $item ];
            if (is_array($type_p)) {
                $type = $type_p[ 'type' ];
            } else {
                $type = $type_p;
            }
            if ($type == 'const_s') {
                $value_temp = [];
                $tmp=2;
                while ($value-$tmp>=0){
                    $value_temp[]=$tmp;
                    $value=$value-$tmp;
                    $tmp=$tmp*2;
                }
                $value=$value_temp;
            }elseif ($type == 'json') {
                $value = json_decode($value, true);
            } elseif ($type == 'int') {
                if ($value !== null) {
                    $value = (int)$value;
                }
            } elseif ($type == 'boolean') {
                $value = (int)$value;
                $value = $value == 1 ? true : false;
            } elseif ($type == 'float') {
                $value = (float)$value;
            } elseif ($type == 'time') {
                $time = (int)$value;
                if ($time . "" == $value . "") {
                    $value = date("Y-m-d H:i:s", $this->_db_data[ $item ]);
                }
            } elseif ($type == 'date') {
                $format = '';
                if (is_array($type_p)) {
                    $format = $type_p[ 'format' ];
                }
                if (!$format) {
                    $format = 'Y-m-d';
                }
                $time = (int)$value;
                if ($time . "" == $value) {
                    $value = date($format, $this->_db_data[ $item ]);
                }
            } elseif ($type == 'attach' || $type == 'attach_i') {
                $attach = json_decode($value, true);
                if (count($attach) > 0) {
                    $attach = $attach[ 0 ];
                } else {
                    continue;
                }
                $url = $attach[ 'url' ];
                $image_type = 'default';
                if (key_exists('type', $attach)) {
                    $image_type = $attach[ 'type' ];
                }
                $domian = Storage::getStorage($image_type)->getDomain();
                if (!(strpos($url, 'http') === 0) && $url) {
                    $attach[ 'url' ] = $domian . $url;
                }
                if ($type == 'attach') {
                    $value = $attach;
                } else {
                    $value = $attach[ 'url' ];
                }
            } elseif ($type == 'attach_s') {
                $value = json_decode($value, true);

                $values = [];

                foreach ($value as $v) {
                    $type = 'default';
                    if (key_exists('type', $v)) {
                        $type = $v[ 'type' ];
                    }
                    $domian = Storage::getStorage($type)->getDomain();
                    $url = $v[ 'url' ];
                    if (!(strpos($url, 'http') === 0)) {
                        $v[ 'url' ] = $domian . $url;
                    }
                    $values[] = $v;
                }
                $value = $values;
            }
            $this->$item = $value;
        }
    }

    /**
     * 保存 如果主键存在就更新,否则插入
     * 如果数据库中没有设置自增主键的话也会进行判定
     */
    public function save()
    {
        $pk = $this->getPkField();
        //主键是id
        if (($pk == 'id' && $this->$pk) || $this->to_update) {
            $this->update();
        } else {
            if ($this->$pk) {
                $this->checkHas();
            }
            if ($this->to_update) {
                $this->update();
            } else {
                $this->insert();
            }
        }
    }


    /**
     * 获取保存的对象
     * @return array
     * @throws
     */
    public function getDBData()
    {
        $data = [];
        $fields = $this->getFields();
        foreach ($fields as $field => $type) {
            $value = $this->$field;
            if ($value === null) {
                continue;
            }
            $oldValue = $this->_db_data[ $field ];
            if (is_array($type)) {
                $type = $type[ 'type' ];
            }
            if ($type == 'json' && !is_string($value)) {
                $value = json_encode($value);
            }
            //值没有变不保存
            if ($value == $oldValue && $oldValue !== null) {
                continue;
            }

            if ($value === 'null') {
                $value = null;
                $this->$field = null;
            }elseif ($type === 'const_s') {
                $cs=0;
                foreach ($value as $item) {
                    $cs+=(int)$item;
                }
                $value=$cs;
            } elseif ($type == 'int') {
                $value = (int)$value;
            } elseif ($type == 'float') {
                $value = (float)$value;
            } elseif ($type == 'time' || $type == 'date') {
                $time = (int)$value;
                if ($time . "" != $value) {
                    $value = strtotime($value);
                }
            } elseif ($type == 'attach_s') {
                if (is_string($value)) {
                    $attach = [['url' => $value]];
                } else {
                    $attach = $value;
                }
                $values = [];
                foreach ($attach as $item) {
                    $type = 'default';
                    if (key_exists('type', $item)) {
                        $type = $item[ 'type' ];
                    }
                    $url = $item[ 'url' ];
                    $domian = Storage::getStorage($type)->getDomain();
                    if ($domian) {
                        $url = str_replace($domian, "", $url);
                    }
                    $item[ 'url' ] = $url;
                    $values[] = $item;
                }
                $value = json_encode($values);
                if ($value == $oldValue && $oldValue != null) {
                    continue;
                }
            } elseif ($type == 'attach' || $type == 'attach_i') {
                if (is_string($value)) {
                    $item = ['url' => $value];
                } else {
                    $item = $value;
                }
                $type = 'default';
                if (key_exists('type', $item)) {
                    $type = $item[ 'type' ];
                }
                $url = $item[ 'url' ];
                $domian = Storage::getStorage($type)->getDomain();
                if ($domian) {
                    $url = str_replace($domian, "", $url);
                }
                $item[ 'url' ] = $url;
                $item = [$item];
                $value = json_encode($item);
                if ($value == $oldValue && $oldValue != null) {
                    continue;
                }
            }
            $data[ $field ] = $value;
        }
        return $data;
    }

    private $is_insert = false;


    /**
     * 插入
     */
    public function insert()
    {
        Event::trigger(RecordEvent::RECORD_BEFORE_INSERT, $this);
        $pk = $this->getPkField();
        $data = $this->getDBData();
        $create_time = 'create_time';
        if (property_exists(get_called_class(), $create_time) && !$data[ $create_time ]) {
            $data[ $create_time ] = time() - 10;
        }
        $version_field = $this->getVersionField();
        if ($version_field) {
            $data[ $version_field ] = 1;
        }
        $pk_value = DB::insert($this->getTable(), $data, $this->connectionName());
        if (!$this->$pk) {
            $this->$pk = $pk_value;
        }
        //数据放入缓存防止立马拿,由于主从库延迟拿不到
        $data[ $pk ] = $this->$pk;
        if (!($this instanceof NoAutoCache)) {
            /* @var $db_cache DBCache */
            $db_cache = Ioc::get(DBCache::class);
            $db_cache->recordCacheSave($this->getTable(), $this->$pk, $data);
            foreach ($data as $field => $value) {
                $this->_db_data[ $field ] = $value;
            }
            $this->is_insert = true;
        }
    }

    /**
     * 检查是否是插入
     * @return bool
     */

    /**
     * 检查是否是插入
     * @return bool
     */
    public function isInsert()
    {
        if ($this->is_insert) {
            return true;
        }
        $pk = $this->getPkField();
        //主键是id
        if (($pk == 'id' && $this->$pk) || $this->to_update) {
            return false;
        } else {
            if ($this->$pk) {
                $this->checkHas();
            }
            if ($this->to_update) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 更新
     *
     * @param bool   $check_version 是否使用乐观锁检查数据
     * @param string $error_msg     使用乐观锁检查数据错误时的错误
     *
     * @throws UpdateVersionException
     */
    public function update($check_version = true, $error_msg = "数据更新失败,请重试")
    {
        Event::trigger(RecordEvent::RECORD_BEFORE_UPDATE, $this);
        $pk = $this->getPkField();
        $where[ $pk ] = $this->$pk;
        $data[ $pk ] = $this->$pk;
        $data = $this->getDBData();
        if (!$data) {
            return;
        }
        $update_time = 'update_time';
        if (property_exists(get_called_class(), $update_time)) {
            $data[ $update_time ] = time();
        }
        //当前数据存在版本号
        $version_field = $this->getVersionField();
        if ($check_version && $version_field && $this->$version_field) {
            $data[ $version_field ] = $this->$version_field + 1;
            $where[ $version_field ] = $this->$version_field;
        }
        if ($this->delayUpdate()) {
            $row_count = Update::delayUpdate($this->$pk, $this->getTable(), $data, $where, $this->connectionName());
        } else {
            $row_count = Update::update($this->getTable(), $data, $where, $this->connectionName());
        }

        //需要检查版本号
        if ($check_version && $version_field && $this->$version_field && !$row_count) {
            //数据被变更,更新失败
            throw new UpdateVersionException($error_msg);
        }

        //删除缓存
        /* @var $db_cache DBCache */
        $db_cache = Ioc::get(DBCache::class);
        $db_cache->recordWhereCacheDel($this);
        if (!($this instanceof NoAutoCache)) {
            $db_cache->recordCacheDel($this->getTable(), $this->$pk);
        }
        foreach ($data as $field => $value) {
            $this->_db_data[ $field ] = $value;
        }
    }


    /**
     * 删除当前对象
     * 如果有 delete_time字段 默认是设置为当前时间
     *
     * @param bool $force 是否强制
     *
     * @throws
     */
    public function delete($force = false)
    {
        Event::trigger(RecordEvent::RECORD_BEFORE_DELETE, $this);
        $model = get_called_class();
        $pk = $this->getPkField();
        $id = $this->$pk;
        if (isset($id)) {
            $where[ $pk ] = $id;
            $delete_time = "delete_time";
            if (property_exists($model, $delete_time)) {
                if (!$force) {
                    $this->$delete_time = time();
                    $this->update();
                    return;
                }
            }
            DB::delete($this->getTable(), null, $this->connectionName())->where($pk, $id)->excuse();
        }
        //删除缓存
        /* @var $db_cache DBCache */
        $db_cache = Ioc::get(DBCache::class);
        $db_cache->recordWhereCacheDel($this);
        if (!($this instanceof NoAutoCache)) {
            $db_cache->recordCacheDel($this->getTable(), $id);
        }
    }

    /**
     * 查找一个对象
     *
     * @param array $where ['a'=>'a','b'=>'b']
     *
     * @return $this;
     */
    public static function find(array $where)
    {
        $model = get_called_class();
        /* @var $t Record */
        $t = new $model;
        /* @var $db_cache DBCache */
        $db_cache = Ioc::get(DBCache::class);
        $data = $db_cache->recordWhereCache($model, $where);
        if ($data) {
            return $data;
        }
        /* @var $data Record */
        $select = DB::select($t->getTable(), $t->connectionName())->where($where)->setRecord($model);
        Event::trigger(RecordEvent::RECORD_BEFORE_SELECT, $t, $select);
        $data = $select->find();
        $db_cache->recordWhereCacheSave($model, $where, $data->_db_data);
        return $data;
    }

    /**
     * 查找一个对象,如果不存在就创建
     *
     * @param array $where ['a'=>'a','b'=>'b']
     *
     * @return $this
     */
    public static function findCreate(array $where)
    {
        $item = self::find($where);
        if (!$item) {
            $model = get_called_class();
            /* @var $item Record */
            $item = new $model;
            $item->fromArray($where);
        }
        return $item;
    }


    /**
     * 静态删除  destroy方法不管delete_time字段
     *
     * @param string|int $id
     *
     * @throws
     */
    public static function destroy($id)
    {
        $model = get_called_class();
        /* @var $model Record */
        $model = new $model;
        $pk = $model->getPkField();
        $model->$pk = $id;
        $model->delete();
    }

    /**
     * 根据主键获取对象
     *
     * @param string|int $id
     * @param bool       $cache 是否使用缓存
     *
     * @return $this;
     */
    public static function get($id, $cache = true)
    {
        if (!$id) {
            return null;
        }
        $model = get_called_class();
        $db_cache = null;
        /* @var $bean Record */
        $bean = new $model;
        if ($cache && !($bean instanceof NoAutoCache)) {
            /* @var $db_cache DBCache */
            $db_cache = Ioc::get(DBCache::class);
            $data = $db_cache->recordCache($model, $id);
            if ($data) {
                if ($data=='null') {
                    return null;
                }
                return $data;
            }
        }
        $pk = $bean->getPkField();
        $where[ $pk ] = $id;
        $data = $bean::find($where);
        if ($cache) {
            if ($data) {
                $db_cache->recordCacheSave($bean->getTable(), $id, $data->_db_data);
            } else {
                $db_cache->recordCacheSave($bean->getTable(), $id, 'null');
            }
        }
        return $data;
    }

    /**
     * 查询对象并获取对象的事务锁
     *
     * @param string|int $id
     *
     * @return $this
     */
    public static function getLock($id)
    {

        $model = get_called_class();
        /* @var $t Record */
        $t = new $model;
        $select = DB::select($t->getTable(), $t->connectionName())
                    ->where($t->getPkField(), $id)
                    ->lock()
                    ->setRecord($model);
        Event::trigger(RecordEvent::RECORD_BEFORE_SELECT, $t, $select);
        $data = $select->find();
        return $data;
    }

    /**
     * 检索
     *
     * @param string $fields 多个字段用,隔开
     * @param bool   $contain
     *
     * @return Select
     * @throws
     */
    public static function select($fields = '', $contain = true)
    {
        $model = get_called_class();
        preg_match_all('/([A-Z]{1})/', substr($model, strrpos($model, '\\') + 1), $matches);
        $as = strtolower(implode("", $matches[ 0 ]));
        if ($as == 'or') {
            $as = 'o_r';
        }
        if ($as == 'to') {
            $as = 't_o';
        }
        if ($as == 'and') {
            $as = 'a_n_d';
        }
        return self::selectAs($fields, $as, $contain);
    }

    /**
     * 检索
     *
     * @param string $fields
     * @param string $as
     * @param bool   $contain
     *
     * @return Select
     */
    public static function selectAs($fields = '', $as = '', $contain = true)
    {
        $model = get_called_class();

        /* @var $model Record */
        $model = new $model;
        $select = DB::select($model->getTable() . " " . $as, $model->connectionName())->setRecord(get_called_class());
        $pkField = $model->getPkField();
        //默认按 id或是创建时间倒序
        if ($pkField == 'id') {
            $select->order($as . '.id desc');
        } elseif (property_exists(get_called_class(), 'create_time')) {
            $select->order($as . '.create_time desc');
        }
        Event::trigger(RecordEvent::RECORD_BEFORE_SELECT, $model, $select);
        if ($fields) {
            if (!$contain) {
                $fieldAll = $model->getFields();
                $fields = explode(",", $fields);
                $need = [];
                foreach ($fieldAll as $field => $value) {
                    if (!in_array($field, $fields)) {
                        $need[] = $field;
                    }
                }
                $fields = implode(",", $need);
            }
            $select->fields($fields);
        }

        return $select;
    }


    /**
     * 获取字段
     * @return mixed
     * @throws \Error
     */
    public function getFields()
    {
        $connection = Pool::get(Connection::class);
        try {
            $fields = $connection->getFields($this->getTable());
            return $fields;
        } finally {
            Pool::release($connection);
        }
    }

    /**
     * 获取主键 默认
     * @return mixed
     */
    public function getPkField()
    {
        return "id";
    }

    /**
     * 获取数据版本号字段
     * @return string
     */
    public function getVersionField()
    {
        return "";
    }

    /**
     * 获取表
     * @return string
     */
    public function getTable()
    {
        $table = get_called_class();
        return $table;
    }

    /**
     * 字符串转对象
     *
     * @param $str
     */
    public function fromString($str)
    {
        $array = json_decode($str, true);
        $this::fromArray($array);
    }

    /**
     * 数组转对象
     *
     * @param $array
     */
    public function fromArray($array)
    {
        if ($array instanceof Record) {
            $array = $array->toArray('', false);
        }
        foreach ($array as $key => $value) {
            if (isset($value)) {
                if ($value instanceof Search) {
                    $value = $value->value();
                }
                $this->$key = $value;
            }
        }
    }

    /**
     * 转化为数组
     *
     * @param string $fields  ['id,name']
     * @param bool   $contain 如果为 false就反向选取字段
     *
     * @return array|mixed|string
     */
    public function toArray($fields = '', $contain = true)
    {
        return RecordArray::toArray($this, $fields, $contain);
    }

    /**
     * 常量类型替换
     */
    public function renderConst()
    {
        $fields = $this->getFields();
        foreach ($fields as $field => $type) {
            if (!is_array($type)) {
                continue;
            }
            $con = $type[ 'const' ];
            $type = $type[ 'type' ];
            $value = $this->$field;
            $field_show = $field . "_show";
            if($type=='const_s'){
                $temps=[];
                foreach ($value as $item) {
                    $temps[]= $con[ $item ];
                }
                $this->$field_show = $temps;
            }else{
                if(array_values($con) === $con){
                    $value -= 1;
                }
                $this->$field_show = $con[ $value ];
            }
        }
    }

    /**
     * 同时删除对象关联的附件
     */
    public function deleteAttach()
    {
        $model = get_called_class();
        /* @var $model Record */
        $model = new $model;
        $model = $model::get($this->getPk());
        $fields = $this->getFields();
        foreach ($fields as $field) {
            if ($field == 'attach' || $field == 'attach_s') {
                $value = $model->_db_data[ $field ];
                if (!$value) {
                    continue;
                }
                $attach_s = json_encode($value, true);
                foreach ($attach_s as $attach) {
                    $type = $attach[ 'type' ];
                    $url = $attach[ 'url' ];
                    if (!$type) {
                        $type = 'default';
                    }
                    Storage::getStorage($type)->delete($url);
                }
            }
        }
    }

    /**
     * 获取主键的值
     * @return string|int
     */
    public function getPk()
    {
        $pk = $this->getPkField();
        return $this->$pk;
    }

    /**
     * 检查数据库是否有
     * @return bool
     */
    public function checkHas()
    {
        /* @var $model Record */
        $model = get_called_class();
        $model = $model::get($this->getPk());
        if ($model) {
            $this->isUpdate(true);
            return true;
        }
        return false;
    }


    //准许已数组访问
    public function offsetExists($offset)
    {
        return $this->$offset;
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    /**
     * 从数据库中加载数据并覆盖当前对象,只覆盖空的属性
     */
    public function load()
    {
        $pk = $this->getPk();
        if (!$pk) {
            return;
        }
        $data = self::get($pk);
        $data = $data->toArray('', false);
        foreach ($data as $key => $value) {
            if (empty($this->$key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * 从 request 中获取 不建议使用
     * @return $this
     */
    public static function buildRequest()
    {
        $model = get_called_class();
        /* @var $model Record */
        $model = new $model;
        $params = Context::get('request_params');
        $pk = $model->getPkField();
        $pk = $params[ $pk ];
        if ($pk) {
            $model = $model::get($pk);
        }
        $model->fromArray($params);
        return $model;
    }

    /**
     * 返回连接名称多连接池可覆盖
     * @return mixed
     */
    public function connectionName()
    {
        return Connection::class;
    }

    /**
     * 转化为 json/数组 可以覆盖
     * @return array|mixed|string
     */
    public function jsonSerialize()
    {
        $fb = $this->toJsonField();
        if (is_array($fb)) {
            return $this->toArray($fb[ 0 ], $fb[ 1 ]);
        } else {
            return $this->toArray($fb);
        }
    }

    /**
     *  通过request 参数构建对象
     *
     * @param Request $request
     */
    public function parseRequest(Request $request)
    {
        $fb = $this->requestField();
        if (!is_array($fb)) {
            $fb = [$fb, trim($fb) != ''];
        }
        if (!$fb[ 1 ]) {
            $fields = explode(',', $fb[ 0 ]);
            $data = RecordArray::toArray($this, '', true);
            foreach ($data as $key => $value) {
                if (!in_array($key, $fields)) {
                    $this->$key = $request->param($key);
                }
            }
            return;
        } else {
            $keys = explode(',', $fb[ 0 ]);
        }
        foreach ($keys as $key) {
            $this->$key = $request->param($key);
        }
    }

    /**
     * 获取通过 request 创建时摘取的字段
     * 默认返回同toJsonField相同
     * return '字段1,字段2' 或return ['字段1,字段2',false]//反向字段
     * @return string|array
     */
    public function requestField()
    {
        return $this->toJsonField();
    }


    /**
     * 获取转换为json时的字段
     * 默认返回所有 public的字段
     * return '字段1,字段2' 或return ['字段1,字段2',false]//反向字段
     * @return string|array
     */
    public function toJsonField()
    {
        return "";
    }
}
