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
     * @param $interface string 接口
     * @param $method    string 方法名
     * @param $data      array 参数
     *
     * @return mixed
     */
    public function query($interface, $method, $data);


}