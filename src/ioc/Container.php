<?php


namespace rap\ioc;

use Psr\Container\ContainerInterface;


class Container implements ContainerInterface {

    public function get($id) {
        return Ioc::get($id);
    }

    public function has($id) {
        return Ioc::has($id);
    }

}