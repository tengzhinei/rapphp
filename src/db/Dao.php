<?php

namespace rap\db;

use rap\ioc\IocInject;

class Dao
{
    use IocInject;

    /**
     * 插入
     * @param BaseModel $baseModel
     * @return int|string
     */
    public static function insert(BaseModel $baseModel): int|string
    {
        return DBDao::fromIoc()->insert($baseModel);
    }

    /**
     * 更新
     * @param BaseModel $baseModel
     */
    public static function update(BaseModel $baseModel)
    {
        return DBDao::fromIoc()->update($baseModel);
    }

    /**
     * 删除
     * @param BaseModel $baseModel
     */
    public static function delete(BaseModel $baseModel)
    {
        return DBDao::fromIoc()->delete($baseModel);
    }

    /**
     * 查找
     * @param string $clazz_name
     * @param string $to_clazz
     * @return Select
     */
    public static function select(string $clazz_name, string $to_clazz = null)
    {
        return DBDao::fromIoc()->select($clazz_name, $to_clazz);
    }


    /**
     * 获取单个
     * @param string $clazz_name
     * @param string|int $pk
     * @param string $to_record
     * @return mixed|null
     */
    public static function get($clazz_name, $pk, $to_record = '', string $fields = '')
    {
        return DBDao::fromIoc()->get($clazz_name, $pk, $to_record,$fields);
    }

    /**
     * 查找单个
     * @param string $clazz_name
     * @param string|int $pk
     * @param string $to_record
     * @return mixed|null
     */
    public static function find($clazz_name, $where,$to_record='')
    {
        return DBDao::fromIoc()->find($clazz_name, $where,$to_record);
    }


}
