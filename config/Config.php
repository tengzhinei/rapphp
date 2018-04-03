<?php
namespace rap\config;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 下午9:52
 */
class Config{


    private static $configs=[];

    /**
     * @param string $module
     * @return ConfigHandler
     */
    public static function getConfig($module="default"){
        if(!self::$configs[$module]){
            $config=new ConfigHandler($module);
            self::$configs[$module]=$config;
        }
        return self::$configs[$module];
    }

    /**
     * 获取缓存
     * @param $key
     * @param $default
     * @return mixed
     */
    public static function get($key,$default=""){
       return self::getConfig()->get($key,$default);
    }

    /**
     * 清空配置缓存
     */
    public static function removeCache(){
         self::getConfig()->removeCache();
    }

    /**
     * 设置配置
     * @param $key
     * @param $value
     */
    public static function set($key,$value){
        self::getConfig()->set($key,$value);
    }
}