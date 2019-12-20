<?php

namespace rap\ioc\construstor ;

/**
 * 可以对 bean 进行配置
 * @package rap\ioc
 */
interface BeanConstructor
{

    /**
     * 返回需要处理的类
     * @return string
     */
    public  function constructorClass();

    /**
     * 返回构造器需要的参数
     * @return array
     */
    public function constructorParams();

    /**
     * 处理方法
     * @param $bean
     */
    public function afterConstructor($bean);


}