<?php
namespace rap\config;
use rap\cache\Cache;
use rap\db\Select;
use rap\db\Update;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 下午9:52
 */
class Config{

    private static $fileDate=[];


    /**
     * 获取缓存
     * @param $module
     * @param $key
     * @param $default
     * @return mixed
     */
    public static function get($module, $key="", $default=""){
        $data=self::getFileConfig()[$module];
        if($data===null){
            $data=self::getModuleFromDB($module);
        }
        if($key){
            $value = $data[$key];
        }else{
            $value =$data;
        }
        if($value===null){
            $value=$default;
        }
        return $value;
    }

    /**
     * 设置配置
     * @param $module
     * @param $key
     * @param $value
     */
    public static function set($module,$key,$value=null){
        $data=self::getModuleFromDB($module);
        if(!$data){
            $data=[];
        }
        if(is_array($key)){
            $data=array_merge($data,$key);
        }else{
            $data[$key]=$value;
        }
        $data=json_encode($data);
        $config=self::get("config");
        $table=$config&&key_exists('db_table',$config)?$config['db_table']:"config";
        $module_key=$config&&key_exists('module_field',$config)?$config['module_field']:"module";
        $content=$config&&key_exists('content_field',$config)?$config['content_field']:"content";
        Update::table($table)->set($content,$data)
            ->where($module_key,$module)
            ->excuse();
        Cache::remove(md5("config_".$module));
    }


    private static function getModuleFromDB($module){
        $data=Cache::get(md5("config_".$module));
        if(!$data){
            $config=self::get("config");
            $table=$config&&key_exists('db_table',$config)?$config['db_table']:"config";
            $module_key=$config&&key_exists('module_field',$config)?$config['module_field']:"module";
            $content=$config&&key_exists('content_field',$config)?$config['content_field']:"content";
            $data= Select::table($table)
                ->where($module_key,$module)
                ->value($content);
            Cache::set(md5("config_".$module),$data);
        }
        if($data){
            $data=json_decode($data,true);
        }
        return $data;
    }

    public static function getFileConfig(){
        if(!static::$fileDate){
            static::$fileDate=include APP_PATH.'config.php';
        }
        return static::$fileDate;
    }
}