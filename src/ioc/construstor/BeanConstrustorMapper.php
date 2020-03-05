<?php


namespace rap\ioc\construstor;

use rap\ioc\Ioc;

abstract class BeanConstrustorMapper
{

    public function register()
    {
        $map = $this->mapper();
        foreach ($map as $item) {
            $simpleConstrustor = new SimpleBeanConstrustor();
            $simpleConstrustor->set($item);
            Ioc::register($simpleConstrustor);
        }
    }
    abstract public function mapper();
}
