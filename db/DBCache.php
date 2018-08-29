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

class DBCache{

    public static $second_prefix="db_second_cache_";
    public static $second_table_prefix="db_second_table_cache_";

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
     * @param $model
     * @param $id
     * @return mixed
     */
    public function recordCache($model,$id){
        $cache_key="record_".$model.$id;
        $data=Cache::get($cache_key);
        if($data){
             Log::debug("命中缓存".$model." ".$id,'cache');
            /* @var $record Record  */
            $record=new $model;
            $record->fromDbData($data);
            return $record;
        }
        return null;
    }

    /**
     * 一级缓存 id 保存
     * @param $model
     * @param $id
     * @param $value
     */
    public function recordCacheSave($model,$id,$value){
        $cache_key="record_".$model.$id;
        Cache::set($cache_key,$value);
    }

    /**
     * 一级缓存 id 删除
     * @param $model
     * @param $id
     */
    public function recordCacheDel($model,$id){
        $cache_key="record_".$model.$id;
        Cache::remove($cache_key);
    }


    /**
     * 一级缓存 where
     * @param $model
     * @param $where
     * @return null|Record
     */
    public function recordWhereCache($model,$where){
        /* @var $t Record  */
        $t=new $model;
        $cacheKeys=$t->cacheKeys();
        if(!$cacheKeys)return null;
        ksort($where);
        $key=implode(",",array_keys($where));
        foreach ($cacheKeys as $cacheKey) {
            $m=explode(',',$cacheKey);
            sort($m);
            $cacheKey=implode(",",$m);
            if($key==$cacheKey){
                $cache_key="record_".$model."_".$key."_".implode(",",array_values($where));
                $data=Cache::get($cache_key);
                if($data){
                    Log::debug("命中缓存 ".$model." 条件:".json_encode($where),'cache');
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
     * @param $model
     * @param $where
     * @param $value
     * @return null
     */
    public function recordWhereCacheSave($model,$where,$value){
        /* @var $t Record  */
        $t=new $model;
        $cacheKeys=$t->cacheKeys();
        if(!$cacheKeys)return null;
        ksort($where);
        $cache_key="";
        $key=implode(",",array_keys($where));
        foreach ($cacheKeys as $cacheKey) {
            $m=explode(',',$cacheKey);
            sort($m);
            $cacheKey=implode(",",$m);
            if($key==$cacheKey){
                $cache_key="record_".$model."_".$key."_".implode(",",array_values($where));
                break;
            }
        }
        if($cache_key){
            Cache::set($cache_key,$value);
        }
        return null;
    }

    /**
     * 一级缓存 where 删除
     * @param $cacheKeys
     * @param $_db_data
     */
    public function recordWhereCacheDel($model,$cacheKeys,$_db_data){
        if($cacheKeys){
            foreach ($cacheKeys as $cacheKey) {
                $cks= explode(',',$cacheKey);
                sort($cks);
                $oldV=[];
                foreach ($cks as $ck) {
                    $oldV[]=$_db_data[$ck];
                }
                $cacheKey=implode(",",$cks);
                $cache_key="record_".$model."_".$cacheKey."_".implode(",",$oldV);
                Cache::remove($cache_key);
            }
        }
    }


    /**
     * 查询二级缓存
     * @param $sql
     * @param array $bind
     * @return null
     */
    public function queryCache($sql, $bind = []){
        $redis=Cache::redis();
        if(!$redis){
            //没有使用 redis 不支持二级缓存
            return null;
        }
        $key = $this->cacheKey($sql,$bind);
        $cache_name=$this->cacheName($sql);
        $result=Cache::hashGet($cache_name,$key);
        if($result){
            Log::debug("命中缓存".$sql.json_encode($bind),'cache');
        }
        return $result;
    }

    public function cacheName($sql){
        $ts= $this->getTableNames($sql);
        $t=static::$second_prefix.implode('|',$ts);
        return $t;
    }

    /**
     * 计算缓存的key
     * @param $sql
     * @param $params
     * @return string
     */
    public function cacheKey($sql,$params){
        $params[]=$sql;
        return md5(serialize($params));
    }

    /**
     * 保存二级缓存
     * @param $sql
     * @param array $bind
     * @param $value
     */
    public function saveCache($sql, $bind = [],$value){
        $redis=Cache::redis();
        if(!$redis){
            //没有使用 redis 不支持二级缓存
            return;
        }
        $key = $this->cacheKey($sql,$bind);
        $cache_name=$this->cacheName($sql);
        Cache::hashSet($cache_name,$key,$value);
        $tables=$this->getTableNames($sql);
        foreach ($tables as $t) {
            $redis->sAdd(static::$second_table_prefix.$t,$cache_name);
        }
    }

    /**
     * 清除表关联的二级缓存
     * @param $sql
     */
    public function deleteCache($sql){
        $redis=Cache::redis();
        if(!$redis){
            return;
        }
        $tables=$this->getTableNames($sql);
        foreach ($tables as $table) {
            $caches=$redis->sMembers(static::$second_table_prefix.$table);
            foreach ($caches as $cache) {
                Cache::remove($cache);
            }
        }
    }

    /**
     * 获取 sql关联的表
     * @param $sql
     * @return array
     */
    public  function getTableNames($sql){
        $sql=trim(strtoupper($sql));
        if(strpos($sql,'SELECT')===0){
            return $this->express($sql,static::$selectExpression);
        }else if(strpos($sql,'UPDATE')===0){
            return $this->express($sql,static::$updateExpression);
        }else if(strpos($sql,'INSERT')===0){
            return $this->express($sql,static::$insertExpression);
        }else if(strpos($sql,'DELETE')===0){
            return $this->express($sql,static::$deleteExpression);
        }
        return [];
    }

    /**
     * 正则出 sql 的表
     * @param $sql
     * @param $expression
     * @return array
     */
    private  function express($sql,$expression){
        preg_match_all($expression, $sql, $matches);
        return array_unique(array_pop($matches));
    }

}