<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/6/1 10:19 PM
 */

namespace rap\config;

use rap\cache\Cache;
use rap\db\Select;
use rap\db\Update;
use rap\log\Log;

class DbConfig
{

    private $config = ['db_table' => 'config',
                       'module_field' => 'module',
                       'content_field' => 'content'];


    /**
     * @param FileConfig $fileConfig
     */
    public function __construct(FileConfig $fileConfig)
    {
        $config = $fileConfig->get('config');
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }


    /**
     * 获取缓存
     * @param string $module 模块
     *
     * @return mixed|null|string
     * @throws
     */
    public function get($module)
    {
        $data = $this->getModuleFromDB($module);
        return $data;
    }

    /**
     * 设置配置
     *
     * @param string       $module
     * @param string|array $key
     * @param string|array $value
     * @throws
     */
    public function set($module, $key, $value = null)
    {
        $data = $this->getModuleFromDB($module);
        if (!$data) {
            $data = [];
        }
        if (is_array($key)) {
            $data = array_merge($data, $key);
        } else {
            $data[ $key ] = $value;
        }
        $data = json_encode($data);
        Update::table($this->config[ 'db_table' ])
              ->set($this->config[ 'content_field' ], $data)
              ->where($this->config[ 'module_field' ], $module)
              ->excuse();
        Cache::remove(md5("config_" . $module));
        Log::notice('config set:设置配置:' . $module . ' 值:' . $data);
    }

    /**
     * 设置配置,不做合并
     *
     * @param string $module
     * @param array  $data
     * @throws
     */
    public function setAll($module, $data)
    {
        $data = json_encode($data);
        Update::table($this->config[ 'db_table' ])
              ->set($this->config[ 'content_field' ], $data)
              ->where($this->config[ 'module_field' ], $module)
              ->excuse();
        Cache::remove(md5("config_" . $module));
        Log::notice('config setAll:设置配置:' . $module . ' 值:' . $data);
    }


    /**
     * 从数据库中获取数据
     *
     * @param $module
     *
     * @return mixed|null|string
     * @throws
     */
    private function getModuleFromDB($module)
    {

        $data = Cache::get(md5("config_" . $module));

        if (!$data) {
            $data = Select::table($this->config[ 'db_table' ])
                          ->where($this->config[ 'module_field' ], $module)
                          ->value($this->config[ 'content_field' ]);
            if (!$data) {
                $data='null';
            }

            Cache::set(md5("config_" . $module), $data);
        }
        if ($data) {
            if ($data=='null') {
                return null;
            }
            $data = json_decode($data, true);
        }
        return $data;
    }
}
