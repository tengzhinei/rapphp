<?php

namespace rap\ioc\construstor ;

class SimpleBeanConstrustor implements BeanConstructor
{
    public $class;
    public $constructor;
    public $after;

    public function constructorClass()
    {
        return $this->class;
    }

    public function constructorParams()
    {
        return $this->constructor;
    }

    public function afterConstructor($bean)
    {
        if ($this->after) {
            foreach ($this->after as $key => $value) {
                $bean->$key=$value;
            }
        }
    }

    public function set($config)
    {

        $this->class=$config['class'];
        $this->constructor=$config['constructor'];
        $this->after=$config['after'];
    }
}
