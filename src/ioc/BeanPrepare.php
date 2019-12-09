<?php


namespace rap\ioc;


interface BeanPrepare
{

    /**
     * 返回需要处理的类
     * @return array|string
     */
    public function register();

    /**
     * 处理方法
     * @param $bean
     */
    public function prepare($bean);
}