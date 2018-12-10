<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/1
 * Time: 下午11:50
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;


use rap\ioc\Ioc;

class PoolBuffer {

    public $className;
    public $name;

    /**
     * @var PoolTrait
     */
    public $bean;

    public $lastActiveTime;

    public $is_use;

    function __construct($className,$name=null) {
        if(!$name){
            $name=$className;
        }
        $this->className = $className;
        $this->name = $name;
    }

    public function get() {
        if (!$this->bean) {
            $this->bean = Ioc::beanCreate($this->name);
            $this->lastActiveTime = time();
            $this->bean->_poolBuffer_ = $this;
            $this->bean->_poolName_ = $this->name;
        }
        $this->is_use = true;
        return $this->bean;
    }

    public function active() {
        $this->lastActiveTime = time();
        $this->is_use = false;
    }


}