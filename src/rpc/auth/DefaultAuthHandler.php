<?php


namespace rap\rpc\auth;

/**
 * Rpc 请求认证
 *
 * @author: 藤之内
 */
class DefaultAuthHandler implements AuthHandler {

    /**
     * 对参数进行签名
     *
     * @param string $rpc_name rpc名称
     * @param string $path     路径
     * @param array  $headers  请求头
     * @param string $body     原始 body 参数
     *
     * @return mixed
     */
    public function authHeader($rpc_name, $path, $headers, $body) {
        return $headers;
    }


}