<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 下午9:15
 */

namespace rap\config;


use rap\cache\Cache;
use rap\help\ArrayHelper;
use rap\ioc\Ioc;

class ConfigHandler{

    private $module;
    /**
     * @var ConfigInterface
     */
    private $config;

    private $cacheName="";
    /**
     * ConfigHandler constructor.
     * @param $moudle
     */
    public function __construct($module){
        $this->module = $module;
        $this->config=Ioc::get(ConfigInterface::class);
        $this->cacheName=md5("config_".$module);
    }
    public function get($key,$default=null){
       $data=Cache::get($this->cacheName);
       if(!is_null($data)){
           $value=$data[$key];
           if(is_null($value)){
                $value=$default;
           }
            return $value;
       }
      $data = $this->config->get($this->module);
       if(is_null($data)){
           return $default;
       }
       Cache::set($this->cacheName,$data);
        $value=$data[$key];
        if(is_null($value)){
            $value=$default;
        }
        return $value;
    }

    public function removeCache(){
        Cache::remove($this->cacheName);
    }

    public function set($key, $value=""){
        $data=Cache::get($this->cacheName);
        if(is_null($data)){
            $data = $this->config->get($this->module);
        }
        if(is_array($key)){
            $data = array_merge($data,$key);
        }else{
            $data[$key]=$value;
        }
        $count= $this->config->set($this->module,$data);
        if($count){
            Cache::remove($this->cacheName);
        }
    }


}