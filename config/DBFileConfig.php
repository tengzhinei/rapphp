<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 下午5:35
 */

namespace rap\config;


use rap\db\Select;
use rap\db\Update;

class DBFileConfig implements ConfigInterface{
    private $options=[
        "file_path"=>"",//配置文件地址
        "first_query"=>"file",//先查询的类型 file,DB
        "db_table"=>"config",
        "db_module_field"=>"module",
        "db_value_field"=>"content",
    ];

    private $fileData;
    public function config($config){
        $this->options=array_merge($this->options,$config);
    }

    private function getFileConfig(){
        if(!$this->fileData){
            $data = $this->options["file_path"];
            $this->fileData=include $data;
        }
        return $this->fileData;
    }

    private function getModuleFromFile($module){
        if(!$this->fileData){
            $this->getFileConfig();
        }
        return $this->fileData[$module];
    }


    private function getModuleFromDB($module){
        $data= Select::table($this->options['db_table'])
            ->where($this->options['db_module_field'],$module)
            ->value($this->options['db_value_field']);
        if($data){
            $data=json_decode($data,true);
        }
        return $data;
    }

    /**
     * 获取模块
     * @param $module
     * @return string
     */
    public function get($module){
        if($this->options['first_query']=='db'){
            $value=$this->getModuleFromDB($module);
            if(is_null($value)){
                $value=$this->getModuleFromFile($module);
            }
        }else{
            $value=$this->getModuleFromFile($module);
            if(is_null($value)){
                $value=$this->getModuleFromDB($module);
            }
        }
        return $value;
    }

    /**
     * 更新模块
     * @param $module
     * @param $value
     */
    public function set($module, $value){
        if(is_array($value)){
            $value=json_encode($value);
        }
        Update::table($this->options['db_table'])->set($this->options['db_value_field'],$value)
            ->where($this->options['db_module_field'],$module)
            ->excuse();
    }

}