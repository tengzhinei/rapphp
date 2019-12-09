<?php

namespace rap\ioc;
use rap\swoole\Context;


/**
 *
 * 可以将类备注中'@property'标明的属性的作用域保存到request context 里
 *
 * @author: 藤之内
 */
trait RequestScopeProperty
{

    private $_scope_request;


    public function __get($name)
    {
        $contextProperty=$this->_contextProperty();
        if ($this->_scope_request && $this->_scope_request->$name) {
            $value = $this->_scope_request->$name;
            $value = Ioc::get($value);
            return $value;
        }
        return $contextProperty->$name;
    }

    protected function _contextProperty()
    {
        $configProvide = Context::get(RequestScopeProperty::class);
        if (!$configProvide) {
            $configProvide = new \stdClass();
            Context::set(RequestScopeProperty::class, $configProvide);
        }
        $clazz = __CLASS__;
        if (!$configProvide->$clazz) {
            $temp = new \stdClass();
            $configProvide->$clazz = $temp;
        }
        return $configProvide->$clazz;
    }
    public function __set($name, $value)
    {

        if ($value instanceof RequestScope) {
            if (!$this->_scope_request) {
                $this->_scope_request = new \stdClass();
            }
            $this->_scope_request->$name = get_class($value);
        } else {
            $contextProperty=$this->_contextProperty();
            $contextProperty->$name = $value;
        }

    }

}