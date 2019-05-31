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

    public static $second_prefix       = "db_second_cache_";
    public static $second_table_prefix = "db_second_table_cache_";

    // UPDATE 正则条件
    private static $updateExpression = '/UPDATE[\\s`]+?(\\w+)[\\s`]+?/is';

    // INSERT 正则条件
    private static $insertExpression = '/INSERT\\s+?INTO[\\s`]+?(\\w+)[\\s`]+?/is';

    // DELETE 正则条件
    private static $deleteExpression = '/DELETE\\s+?FROM[\\s`]+?(\\w+)[\\s`]+?/is';

    // SELECT 正则条件
    private static $selectExpression = '/((SELECT.+?FROM)|(LEFT\\s+JOIN|JOIN|LEFT))[\\s`]+?(\\w+)[\\s`]+?/is';

    /**
     * 一级缓存 id
     * 根据 id 获取缓存
     *
     * @param string     $model 类名
     * @param string|int $id    主键
     *
     * @return mixed
     */
    public function recordCache($model, $id) {
        /* @var $record Record */
        $record = new $model;
        $cache_key = "record_" . $record->getTable() . $id;
        $data = Cache::get($cache_key);
        if ($data) {
            Log::info("命中缓存" . $model . " " . $id);
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
    public function recordCacheSave($table, $id, $value) {
        $cache_key = "record_" . $table . $id;
        Cache::set($cache_key, $value);
    }

    /**
     * 一级缓存 id 删除
     *
     * @param string     $table 表名
     * @param string|int $id    主键
     */
    public function recordCacheDel($table, $id) {
        $cache_key = "record_" . $table . $id;
        Cache::remove($cache_key);
    }


    /**
     * 一级缓存 where
     *
     * @param string $model 类名
     * @param array  $where 条件
     *
     * @return null|Record
     */
    public function recordWhereCache($model, $where) {
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
                $cache_key = "record_" . $t->getTable() . "_" . $key . "_" . implode(",", array_values($where));
                $data = Cache::get($cache_key);
                if ($data) {
                    Log::info("命中缓存 " . $model, $where);
                    $t->fromDbData($data);
                    return $t;
                }
                break;
            }
        }
        return null;
    }

    /**
     * 一级缓存 where 保存
     *
     * @param string $model 类名
     * @param array  $where 条件
     * @param array  $value 值
     *
     * @return null
     */
    public function recordWhereCacheSave($model, $where, $value) {
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
                $cache_key = "record_" . $t->getTable() . "_" . $key . "_" . implode(",", array_values($where));
                break;
            }
        }
        if ($cache_key) {
            Cache::set($cache_key, $value);
        }
        return null;
    }

    /**
     * 一级缓存 where 删除
     *
     * @param string $model     类名
     * @param array  $cacheKeys 所有缓存的 keys
     * @param array  $_db_data  数据库原来的数据
     */
    public function recordWhereCacheDel($model) {
        /* @var $model Record */
        $cacheKeys = $model->cacheKeys();
        $_db_data = $model->getOldDbData();
        if ($cacheKeys) {
            foreach ($cacheKeys as $cacheKey) {
                $cks = explode(',', $cacheKey);
                sort($cks);
                $oldV = [];
                foreach ($cks as $ck) {
                    $oldV[] = $_db_data[ $ck ];
                }
                $cacheKey = implode(",", $cks);
                $cache_key = "record_" . $model->getTable() . "_" . $cacheKey . "_" . implode(",", $oldV);
                Cache::remove($cache_key);
            }
        }
    }


    /**
     * 查询二级缓存
     *
     * @param string $sql  sql
     * @param array  $bind 绑定的数据
     *
     * @return null
     */
    public function queryCache($sql, $bind = []) {

        $key = $this->cacheKey($sql, $bind);
        $cache_name = $this->cacheName($sql);
        $result = Cache::hashGet($cache_name, $key);
        if ($result) {
            Log::info("命中缓存" . $sql, $bind);
        }
        return $result;
    }

    /**
     * 获取相关表的缓存名称
     *
     * @param string $sql
     *
     * @return string
     */
    public function cacheName($sql) {
        $ts = $this->getTableNames($sql);
        $t = static::$second_prefix . implode('|', $ts);
        return $t;
    }

    /**
     * 计算缓存的key
     *
     * @param $sql
     * @param $params
     *
     * @return string
     */
    public function cacheKey($sql, $params) {
        $params[] = $sql;
        return md5(serialize($params));
    }

    /**
     * 保存二级缓存
     *
     * @param  string $sql
     * @param array   $bind
     * @param   array $value
     */
    public function saveCache($sql, $bind = [], $value) {


        $key = $this->cacheKey($sql, $bind);
        $cache_name = $this->cacheName($sql);
        Cache::hashSet($cache_name, $key, $value);
        $tables = $this->getTableNames($sql);
        $redis = Cache::redis();
        try {
            foreach ($tables as $t) {
                $redis->sAdd(static::$second_table_prefix . $t, $cache_name);
            }
        } finally {
            Cache::release();
        }


    }

    /**
     * 清除表关联的二级缓存
     *
     * @param string $sql
     */
    public function deleteCache($sql) {
        if (strpos($sql, 'SELECT') == 0) {
            return;
        }
        $tables = $this->getTableNames($sql);
        foreach ($tables as $table) {
            $caches=[];
            try {
                $redis = Cache::redis();
                if($redis){
                    $caches = $redis->sMembers(static::$second_table_prefix . $table);
                }
            } finally {
                Cache::release();
            }
            foreach ($caches as $cache) {
                Cache::remove($cache);
            }
        }
    }

    /**
     * 获取 sql关联的表
     *
     * @param string $sql
     * @param bool   $select
     *
     * @return array
     */
    public function getTableNames($sql) {
        $sql = trim(strtoupper($sql));

        if (strpos($sql, 'SELECT') === 0) {
            return $this->express($sql, static::$selectExpression);
        } else if (strpos($sql, 'UPDATE') === 0) {
            return $this->express($sql, static::$updateExpression);
        } else if (strpos($sql, 'INSERT') === 0) {
            return $this->express($sql, static::$insertExpression);
        } else if (strpos($sql, 'DELETE') === 0) {
            return $this->express($sql, static::$deleteExpression);
        }
        return [];
    }

    /**
     * 正则出 sql 的表
     *
     * @param string $sql        sql
     * @param string $expression 表达式
     *
     * @return array
     */
    private function express($sql, $expression) {
        preg_match_all($expression, $sql, $matches);
        return array_unique(array_pop($matches));
    }

}