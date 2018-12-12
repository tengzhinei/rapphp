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
use rap\swoole\pool\PoolTrait;
use Swoole\Coroutine\Http\Client;


/**
 * 通过 http 实现的 Rpc 客户端
 * swoole环境下用协程客户端,否者自动降级为\Requests库
 */
class RpcHttpClient implements RpcClient {
    use PoolTrait;

    public $FUSE_STATUS     = 3;
    public $FUSE_FAIL_COUNT = 0;
    public $FUSE_OPEN_TIME;

    private $config = ['host' => '',
                       'port' => 9501,
                       'path' => 'rpc_____call',
                       'token' => '',
                       'serialize' => 'serialize',
                       'timeout' => 0.05,
                       'fuse_time'=>30,//熔断器熔断后多久进入半开状态
                       'fuse_fail_count'=>20,//连续失败多少次开启熔断
                       'pool' => ['min' => 1, 'max' => 10]];


    public function config($config) {
        $this->config = array_merge($this->config, $config);
        $this->config[ 'name' ] = Config::getFileConfig()[ 'app' ][ 'name' ];
        if (!$this->config[ 'name' ]) {
            $this->config[ 'name' ] = 'rap_rpc_client';
        }
    }

    public function poolConfig() {
        return $this->config[ 'pool' ];
    }

    /**
     * 发起请求
     *
     * @param string $name 接口名称
     * @param mixed  $data 对象或数组
     *
     * @return mixed   返回结果
     */
    public function query($interface, $method, $data) {
        $headers = ['rpc_client_name' => $this->config[ 'name' ],
                    'rpc_token' => $this->config[ 'token' ],
                    'rpc_serialize' => $this->config[ 'serialize' ],
                    'rpc_interface' => $interface,
                    'rpc_method' => $method];
        if (IS_SWOOLE && \Co::getuid()) {
            return $this->queryCoroutine($headers, $data);
        } else {
            return $this->queryByRequest($headers, $data);
        }

    }


    public function queryByRequest($headers, $data) {
        $scheme = 'http://';
        if ($this->config[ 'port' ] == 443) {
            $scheme = 'https://';
        }
        $path = $this->config[ 'path' ];
        if (strpos($path, '/') != 0) {
            $path = '/' . $path;
        }
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        $response = \Requests::put($scheme . $this->config[ 'host' ] . ':' . $this->config[ 'port' ] . $path, $headers, $data);
        if ($response->status_code == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->body;
            if ($data && strpos($type, 'application/rap-rpc')) {
                $data = unserialize($data);
                //有错误异常直接外抛
                if ($data instanceof \RuntimeException) {
                    throw $data;
                }
            } else if ($data && strpos($type, 'application/json')) {
                $data = json_decode($data, true);
                if( $response->headers[ 'rpc-exception' ]){
                    $type=$data['type'];
                    $msg=$data['msg'];
                    $code=$data['code'];
                    $exception=new $type($msg,$code);
                    throw $exception;
                }
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }
    }

    public function queryCoroutine($headers, $data) {
        $cli = new Client($this->config[ 'host' ], $this->config[ 'port' ]);
        $cli->setHeaders($headers);
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        $cli->post($this->config[ 'path' ], $data);
        $cli->close();
        if ($cli->statusCode == 200) {
            $type = $cli->headers[ 'content-type' ];
            $data = $cli->body;
            if ($data && strpos($type, 'application/rap-rpc')) {
                $data = unserialize($data);
                //有错误异常直接外抛
                if ($data instanceof \RuntimeException) {
                    throw $data;
                }
            } else if ($data && strpos($type, 'application/json')) {
                $data = json_decode($data, true);
                if( $cli->headers[ 'rpc-exception' ]){
                    $type=$data['type'];
                    $msg=$data['msg'];
                    $code=$data['code'];
                    $exception=new $type($msg,$code);
                    throw $exception;
                }
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }

    }


    public function connect() {

    }

    public function fuseConfig() {
        return [
            'fuse_time'=> $this->config[ 'fuse_time' ],//熔断器熔断后多久进入半开状态
            'fuse_fail_count'=>$this->config[ 'fuse_fail_count' ],//连续失败多少次开启熔断
        ];
    }

}