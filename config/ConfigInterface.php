<?php
namespace rap\config;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 下午9:53
 */
interface ConfigInterface{

    /**
     * 获取缓存
     * @param $module
     */
    public  function get($module);


    /**
     * 设置配置
     * @param $module
     * @param $value
     */
    public function set($module,$value);

}