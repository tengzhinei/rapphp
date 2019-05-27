<?php
namespace rap\swoole\pool;
    /**
     * User: jinghao@duohuo.net
     * Date: 18/11/28
     * Time: 下午2:58
     * Link:  http://magapp.cc
     * Copyright:南京灵衍信息科技有限公司
     */

/**
 */
trait PoolTrait {

    public $_poolName_ = "";

    public $_poolLock_ = false;

    /**
     * @var PoolBuffer
     */
    public $_poolBuffer_ = null;




}