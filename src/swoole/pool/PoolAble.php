<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/2
 * Time: 下午12:54
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;

interface PoolAble
{
    public function poolConfig();

    public function connect();
}
