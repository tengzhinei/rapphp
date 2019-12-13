<?php


namespace rap\ioc;

/**
 * 可以对 bean 进行配置
 *\ce BeanPrepare
 * @package rap\ioc
 */
interface BeanPrepare
{

    /**
     * 返回需要处理的类
     * @return array|string
     */
    public static function register();

    /**
     * 处理方法
     * @param $bean
     */
    public function prepare($bean);
}