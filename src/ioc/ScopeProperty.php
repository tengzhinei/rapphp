<?php

namespace rap\ioc;

use rap\swoole\Context;


/**
 * 可以将类备注中'@property'标明的属性从IOC拿
 * @author: 藤之内
 */
trait ScopeProperty {

    private $_scope_property;


    public function __get($name) {
        if ($this->_scope_property && $this->_scope_property->$name) {
            $value = $this->_scope_property->$name;
            $value = Ioc::get($value);
            return $value;
        }
        $contextProperty = $this->_contextProperty();
        return $contextProperty->$name;
    }

    protected function _contextProperty() {
        $configProvide = Context::get(ScopeProperty::class);
        if (!$configProvide) {
            $configProvide = new \stdClass();
            Context::set(ScopeProperty::class, $configProvide);
        }
        $clazz = __CLASS__;
        if (!$configProvide->$clazz) {
            $temp = new \stdClass();
            $configProvide->$clazz = $temp;
        }
        return $configProvide->$clazz;
    }

    public function __set($name, $value) {

        if ($value instanceof RequestScope || $value instanceof WorkerScope) {
            if (!$this->_scope_property) {
                $this->_scope_property = new \stdClass();
            }
            $this->_scope_property->$name = get_class($value);
        } else {
            $contextProperty = $this->_contextProperty();
            $contextProperty->$name = $value;
        }

    }

}