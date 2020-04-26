<?php
namespace rap\rpc\client;

use rap\swoole\pool\PoolAble;

/**
 * User: jinghao@duohuo.net
 * Date: 18/12/7
 * Time: 下午2:50
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
interface RpcClient extends PoolAble {


    /**
     * 发起请求
     *
     * @param string    $interface 接口
     * @param string    $method    方法名
     * @param array     $data      参数
     * @param array     $header    参数
     * @param int|float $timeout   超时时间
     *
     * @return mixed
     */
    public function query($interface, $method, $data, $header = [], $timeout = -1);

    public function fuseConfig();

}
