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

class PoolBuffer
{

    public $className;

    /**
     * @var PoolTrait
     */
    public $bean;

    public $lastActiveTime;

    public $is_use;

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function get()
    {
        if (!$this->bean) {
            $this->bean = Ioc::beanCreate($this->className);
            $this->bean->_poolBuffer_ = $this;
            $this->bean->_poolName_ = $this->className;
        }
        $this->lastActiveTime = time();
        $this->is_use = true;
        return $this->bean;
    }

    public function active()
    {
        $this->lastActiveTime = time();
        $this->is_use = false;
    }
}
