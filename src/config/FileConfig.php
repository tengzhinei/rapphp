<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/6/1 9:50 PM
 */

namespace rap\config;


use rap\util\FileUtil;

class FileConfig {

    private $fileDate;

    private $provides=[];

    public function __construct() {
        if (is_file(APP_PATH . 'config.php')) {
            $this->fileDate = include APP_PATH . 'config.php';
        }else if(is_file(APP_PATH . 'config/config.php')) {
            $this->fileDate = include APP_PATH . 'config/config.php';

        }
        if (!is_dir(APP_PATH . 'config')) {
            return;
        }
        FileUtil::eachAll(APP_PATH . 'config', function($path, $name) {
            if ($name == 'config.php') {
                return;
            }
            if(strpos($name,'.php')>0){
                $name = str_replace('.php', '', $name);
                $data = include $path;
            }else if(strpos($name,'.json')>0){
                $name = str_replace('.json', '', $name);
                $content=file_get_contents($path);
                $data=json_decode($content,true);
            }else{
                return;
            }

            if (!$this->fileDate[ $name ]) {
                $this->fileDate[ $name ] = $data;
            } else {
                foreach ($data as $k => $v) {
                    $this->fileDate[ $name ][ $k ] = $v;
                }
            }
        });
    }

    /**
     * 获取模块
     * @param $module
     *
     * @return mixed
     */
    public function get($module) {
        $data = $this->fileDate[ $module ];
        return $data;
    }

    /**
     * 获取所有配置
     * @return mixed
     */
    public function getAll(){
        return $this->fileDate;
    }

    /**
     * 合并模块
     */
    public function mergeProvide(){
        /* @var $provide FileConfigProvide  */
        foreach ($this->provides as $provide) {
            $config = $provide->load();
            foreach ($config as $w => $item) {
                if (!$this->fileDate[ $w ]) {
                    $this->fileDate[ $w ] = $item;
                } else {
                    foreach ($item as $k => $v) {
                        $this->fileDate[ $w ][ $k ] = $v;
                    }
                }
            }

        }
    }

    /**
     * 注册配置提供器
     * @param FileConfigProvide $provide
     */
    public function registerProvide(FileConfigProvide $provide){
        $this->provides[]=$provide;
    }

}