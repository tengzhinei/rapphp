<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/14
 * Time: 下午11:49
 */

namespace rap\db;

use rap\cache\Cache;
use rap\log\Log;

class DBCache {

    const FIRST_CACHE_KEY="first_cache_";

    const SECOND_CACHE_KEY="second_cache_";

    /**
     * 一级缓存 id
     * 根据 id 获取缓存
     *
     * @param string     $model 类名
     * @param string|int $id    主键
     *
     * @return mixed
     */
    public function firstCacheGet($model, $id) {
        /* @var $record Record */
        $record = new $model;
        $cache_key =self::FIRST_CACHE_KEY . $record->getTable().":".$id ;
        $data = Cache::get($cache_key);
        if ($data == 'null') {
            return 'null';
        }
        if ($data) {
            Log::debug("命中缓存" . $model . " " . $id);
            $record->fromDbData($data);
            return $record;
        }
        return null;
    }

    /**
     * 一级缓存 id 保存
     *
     * @param string     $table 表名
     * @param string|int $id    主键
     * @param array      $value
     */
    public function firstCacheSave($table, $id, $value) {
        $cache_key = self::FIRST_CACHE_KEY  . $table.":".$id ;
        Cache::set($cache_key,$value);
    }

    /**
     * 一级缓存 id 删除
     *
     * @param string     $table 表名
     * @param string|int $id    主键
     */
    public function firstCacheRemove($table, $id) {
        $cache_key = self::FIRST_CACHE_KEY  . $table .":".$id ;
        Cache::remove($cache_key);
    }


    /**
     * cacheKeys 二级缓存获取
     *
     * @param string $model 类名
     * @param array  $where 条件
     *
     * @return null|Record
     */
    public function secondCacheGet($model, $where) {
        /* @var $t Record */
        $t = new $model;
        $cacheKeys = $t->cacheKeys();
        if (!$cacheKeys) {
            return null;
        }
        ksort($where);
        $key = implode(",", array_keys($where));
        foreach ($cacheKeys as $cacheKey) {
            $m = explode(',', $cacheKey);
            sort($m);
            $cacheKey = implode(",", $m);
            if ($key == $cacheKey) {
                $keys=[];
                foreach ($where as $key=>$value) {
                    $keys[].=$key.'#'.$value;
                }
                $cache_key = implode(":", $keys);
                $data = Cache::get(self::SECOND_CACHE_KEY.$t->getTable().':'.$cache_key);
                if ($data) {
                    Log::debug("命中缓存 " . $model, $where);
                    $t->fromDbData($data);
                    return $t;
                }
                break;
            }
        }
        return null;
    }

    /**
     * cacheKeys 二级缓存保存
     *
     * @param string $model 类名
     * @param array  $where 条件
     * @param array  $value 值
     *
     * @return null
     */
    public function secondCacheSave($model, $where, $value) {
        /* @var $t Record */
        $t = new $model;
        $cacheKeys = $t->cacheKeys();
        if (!$cacheKeys) {
            return null;
        }
        ksort($where);
        $cache_key = "";
        $key = implode(",", array_keys($where));
        foreach ($cacheKeys as $cacheKey) {
            $m = explode(',', $cacheKey);
            sort($m);
            $cacheKey = implode(",", $m);
            if ($key == $cacheKey) {
                $keys=[];
                foreach ($where as $key=>$v) {
                    $keys[].=$key.'#'.$v;
                }
                $cache_key = implode(":", $keys);
                break;
            }
        }
        if ($cache_key) {
            Cache::set(self::SECOND_CACHE_KEY.$t->getTable().':'.$cache_key, $value);
        }
        return null;
    }

    /**
     * cacheKeys 二级缓存删除
     *
     * @param string $model 类名
     */
    public function secondCacheRemove($model) {
        /* @var $model Record */
        $cacheKeys = $model->cacheKeys();
        $_db_data = $model->getOldDbData();
        if (!$cacheKeys) {
            return;
        }
        if (!$_db_data) {
            $m = $model::get($model->getPk());
            if($m){
                $_db_data = $m->toArray('', false);
            }
        }
        foreach ($cacheKeys as $cacheKey) {
            $m = explode(',', $cacheKey);
            sort($m);
            $oldV = [];
            $new_value = [];
            foreach ($m as $ck) {
                $oldV[$ck] = $_db_data[ $ck ];
                $new_value[$ck] = $model[ $ck ];
            }

            $keys=[];
            foreach ($oldV as $key=>$value) {
                $keys[].=$key.'#'.$value;
            }
            $cache_key = implode(":", $keys);

            Cache::remove(self::SECOND_CACHE_KEY.$model->getTable().':'.$cache_key);
            $keys=[];
            foreach ($new_value as $key=>$value) {
                $keys[].=$key.'#'.$value;
            }
            $cache_key = implode(":", $keys);
            Cache::remove(self::SECOND_CACHE_KEY.$model->getTable().':'.$cache_key);
        }
    }



    /**
     * 查询三级缓存
     *
     * @param string $sql          sql
     * @param array  $bind         绑定的数据
     * @param string $cacheHashKey 缓存需要存储的hash的key
     *
     * @return mixed
     */
    public function thirdCacheGet($sql, $bind = [], $cacheHashKey = '') {
        if(!$cacheHashKey)return null;
        $key = $this->thirdCacheKey($sql, $bind);
        $result = Cache::hashGet($cacheHashKey, $key);
        if ($result) {
            Log::debug("命中缓存" . $sql, $bind);
        }
        return $result;
    }



    /**
     * 计算缓存的key
     *
     * @param $sql
     * @param $params
     *
     * @return string
     */
    private function thirdCacheKey($sql, $params) {
        $params[] = $sql;
        sort($params);
        return md5(serialize($params));
    }

    /**
     * 保存三级缓存
     *
     * @param   string  $sql
     * @param   array    $bind
     * @param   array  $value
     * @param   string $cacheHashKey
     */
    public function thirdCacheSave($sql, $bind, $value, $cacheHashKey = '') {
        if(!$cacheHashKey)return;
        if (!$bind) {
            $bind = [];
        }
        $key = $this->thirdCacheKey($sql, $bind);
        Cache::hashSet($cacheHashKey, $key, $value);
    }



}
