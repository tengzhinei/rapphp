<?php

namespace rap\rpc\header;


/**
 * 整理需要 rpc 传递的请求头
 * Interface RpcHeaderPrepare
 * @package rap\rpc
 */
interface HeaderPrepare {

    /**
     * 获取需要 rpc 传递的请求头
     *
     * @param string $interface 接口
     * @param string $method    方法
     * @param array  $data      数据
     *
     * @return mixed
     */
    public function header($interface, $method, $data);

}