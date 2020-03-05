<?php
namespace rap\config;

use rap\ioc\Ioc;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 下午9:52
 */
class Config
{



    /**
     * 获取缓存
     *
     * @param string $module
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($module, $key = "", $default = "")
    {
        /* @var $file FileConfig */
        $file = Ioc::get(FileConfig::class);
        $data = $file->get($module);
        if (!$data) {
            /* @var $db DbConfig  */
            $db = Ioc::get(DbConfig::class);
            $data = $db->get($module);
        }
        if (!$data) {
            return $default;
        }
        if ($key) {
            $value = $data[ $key ];
            if (!$value) {
                return $default;
            }
            return $value;
        } else {
            return $data;
        }
    }

    /**
     * 设置配置
     *
     * @param string       $module
     * @param string|array $key
     * @param string|array $value
     */
    public static function set($module, $key, $value = null)
    {
        /* @var $db DbConfig  */
        $db = Ioc::get(DbConfig::class);
        $db->set($module, $key, $value);
    }

    /**
     * 设置配置,不做合并
     *
     * @param string $module
     * @param array  $data
     */
    public static function setAll($module, $data)
    {
        /* @var $db DbConfig  */
        $db = Ioc::get(DbConfig::class);
        $db->setAll($module, $data);
    }


    /**
     * 获取文件配置
     * @return array
     */
    public static function getFileConfig()
    {
        /* @var $file FileConfig */
        $file = Ioc::get(FileConfig::class);
        return $file->getAll();
    }
}
