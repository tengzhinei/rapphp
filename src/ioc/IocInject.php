<?php


namespace rap\ioc;


/**
 * 支持快速从ioc获取对象
 * @author: 藤之内
 */
trait IocInject {

    /**
     * @return self
     */
    public static function fromIoc() {
        $clazz = get_called_class();
        return Ioc::get($clazz);
    }

}