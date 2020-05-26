<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午3:06
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc\client;

use rap\config\Config;
use rap\ioc\Ioc;
use rap\rpc\auth\AuthHandler;
use rap\rpc\auth\DefaultAuthHandler;
use rap\swoole\pool\PoolTrait;
use rap\util\http\client\CoroutineHttpClient;
use rap\util\http\client\RequestHttpClient;
use rap\util\http\hmac\HmacHttp;
use rap\util\http\Http;
use Swoole\Coroutine\Http\Client;

/**
 * 通过 http 实现的 Rpc 客户端
 * swoole环境下用协程客户端,否者自动降级
 */
class RpcHttpClient extends AbsRpcClient {


    /**
     * 发起请求
     *
     * @param $interface    string 接口
     * @param $method       string 方法名
     * @param $data         array 参数
     * @param $header       array 参数
     * @param $timeout      int|float 超时时间
     *
     * @return mixed
     * @return mixed   返回结果
     */
    public function query($interface, $method, $data, $header = [], $timeout = -1) {
        if ($this->config[ 'auth' ]) {
            $headers[ 'Rpc-Auth' ] = md5($this->config[ 'auth' ] . $interface . $method);
        }
        $authorization = $header[ 'authorization' ];
        $headers = array_merge($header, ['Rpc-Client-Name' => Config::get('app')[ 'name' ],
                                         'Authorization-Forward' => $authorization,
                                         'Rpc-Serialize' => $this->config[ 'serialize' ],
                                         'Rpc-Interface' => $interface,
                                         'Rpc-Method' => $method]);
        return $this->queryByRequest($headers, $data, $timeout);
    }


    public function queryByRequest($headers, $data, $timeout = -1) {
        $scheme = 'http://';
        if ($this->config[ 'port' ] == 443) {
            $scheme = 'https://';
        }
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        if ($timeout == -1) {
            $timeout = $this->config[ 'timeout' ];
        }
        $url = $scheme . $this->config[ 'host' ] . ':' . $this->config[ 'port' ] . $this->config[ 'base_path' ] . $this->config[ 'path' ];
        $http = $this->httpClient();
        $headers = $this->authHeader($this->config[ 'base_path' ] . $this->config[ 'path' ], $headers, $data);
        $response = $http->put($url, $headers, $data, $timeout);
        if ($response->status_code == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->body;
            if ($data && strpos($type, 'application/php-serialize') == 0) {
                $data = unserialize($data);
            } elseif ($data && strpos($type, 'application/json') == 0) {
                $data = json_decode($data, true);
            }
            if ($response->headers[ 'rpc-exception' ]) {
                $type = $data[ 'type' ];
                $msg = $data[ 'msg' ];
                $code = $data[ 'code' ];
                $exception = new $type($msg, $code);
                throw $exception;
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }
    }

    /**
     * 获取http服务
     * @return mixed|HmacHttp
     */
    private function httpClient() {
        if (IS_SWOOLE && \Co::getuid() !== -1) {
            return Ioc::get(CoroutineHttpClient::class);
        } else {
            return Ioc::get(RequestHttpClient::class);
        }
    }


    public function connect() {
    }
}
