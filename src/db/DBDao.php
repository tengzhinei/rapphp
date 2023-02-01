<?php

namespace rap\db;

use rap\db\DB;
use rap\db\Select;
use rap\ioc\IocInject;
use RuntimeException;

/**
 * dao 工具
 */
class DBDao
{

    use IocInject;

    /**
     * 插入
     * @param BaseModel $baseModel
     * @return int|string
     */
    public function insert(BaseModel $baseModel): int|string
    {
        $data = $baseModel->toDbArray();
        $id = DB::insert($baseModel::TABLE)->set($data)->excuse();
        $pk = $baseModel::PK;
        $baseModel->$pk = $id;
        return $id;
    }

    /**
     * 更新
     * @param BaseModel $baseModel
     * @return int
     */
    public function update(BaseModel $baseModel)
    {
        $data = $baseModel->toDbArray();
        $pk = $baseModel::PK;
        unset($data[$pk]);
        $pk_value = $baseModel->$pk;
        return DB::update($baseModel::TABLE)->set($data)->where($pk, $pk_value)->excuse();
    }

    /**
     * 删除
     * @param BaseModel $baseModel
     * @return int
     */
    public function delete(BaseModel $baseModel)
    {
        $pk = $baseModel::PK;
        $pk_value = $baseModel->$pk;
        if (!$pk_value) {
            return;
        }
        return DB::delete($baseModel::TABLE)->where($pk, $pk_value)->excuse();
    }

    /**
     * 查找
     * @param string $clazz_name
     * @param ?string $table
     * @return Select
     */
    public function select($clazz_name, $to_clazz = null)
    {
        $table = $clazz_name::TABLE;
        if (!$to_clazz) {
            $to_clazz = $clazz_name;
        }
        $as = $this->tableAsName($clazz_name);
        return DB::select("$table $as")->setRecord($to_clazz);
    }

    /**
     * 获取单个
     * @param string $clazz_name
     * @param string|int $pk
     * @param string $to_record
     * @param string $fields
     * @return mixed|null
     */
    public function get($clazz_name, $pk, $to_record = '', string $fields = '')
    {
        if (!is_subclass_of($clazz_name, BaseModel::class)) {
            throw new RuntimeException("$clazz_name must is_subclass_of BaseModel");
        }
        $table = $clazz_name::TABLE;
        $pk_field = $clazz_name::PK;
        if (!$to_record) {
            $to_record = $clazz_name;
        }

        $select = DB::select($table);
        if($fields){
            $select->fields($fields);
        }
        return $select->setRecord($to_record)->where($pk_field, $pk)->find();
    }


    /**
     * 查找单个
     * @param string $clazz_name
     * @param string|int $pk
     * @param string $to_record
     * @return mixed|null
     */
    public function find($clazz_name, $where, $to_record = '')
    {
        if (!is_subclass_of($clazz_name, BaseModel::class)) {
            throw new RuntimeException("$clazz_name must is_subclass_of BaseModel");
        }
        $table = $clazz_name::TABLE;
        $pk_field = $clazz_name::PK;
        if (!$to_record) {
            $to_record = $clazz_name;
        }
        return DB::select($table)->setRecord($to_record)->where($where)->find();
    }


    /**
     * 获取表的别名
     * @param $clazz_name
     * @return string
     */
    private function tableAsName($clazz_name)
    {
        preg_match_all('/([A-Z])/', substr($clazz_name, strrpos($clazz_name, '\\') + 1), $matches);
        $as = strtolower(implode("", $matches[0]));
        if ($as == 'or') {
            $as = 'o_r';
        }
        if ($as == 'to') {
            $as = 't_o';
        }
        if ($as == 'as') {
            $as = 'a_s';
        }
        if ($as == 'and') {
            $as = 'a_n_d';
        }
        return $as;
    }


}
