<?php
namespace rap\rpc;

use rap\config\Config;
use rap\swoole\pool\PoolAble;
use rap\swoole\pool\PoolTrait;
use Swoole\Coroutine\Http2\Client;

/**
 * User: jinghao@duohuo.net
 * Date: 18/12/7
 * Time: 下午2:50
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
class RpcClient implements PoolAble {
    use PoolTrait;

    public $FUSE_STATUS     = 3;
    public $FUSE_FAIL_COUNT = 0;
    public $FUSE_OPEN_TIME;

    private $config = ['host' => '',
                       'port' => 9501,
                       'path'=>'rpc_____call',
                       'token' => '',
                       'timeout' => 0.05,
                       'pool' => ['min' => 1, 'max' => 10]];


    /**
     * @var Client
     */
    private $cli;

    public function config($config) {
        $this->config = array_merge($this->config, $config);
        $this->config[ 'name' ] = Config::getFileConfig()[ 'app' ][ 'name' ];
        if(!$this->config[ 'name' ]){
            $this->config[ 'name' ]='rap_rpc_client';
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
    public function action($interface, $method, $data) {
        //
        if (!$this->cli) {
            $this->connect();
        }

        if (!$this->cli->connected) {
            $this->cli->connect();
        }
        if (!$this->cli->connected) {
            throw new RpcException('连接rpc服务失败', 100);
        }

        $req = new \swoole_http2_request();
        $req->method = 'POST';
        $req->path = $this->config['path'];
        $req->headers = ['rpc_client_name' => $this->config[ 'name' ],
                         'rpc_token' => $this->config[ 'token' ],
                         'rpc_interface' => $interface,
                         'rpc_method' => $method];
        $req->data = json_encode($data);
        $this->cli->send($req);
        $response = $this->cli->recv();
        if (!$this->cli->errCode && $response->statusCode == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->data;
            if (strpos($type, 'json')) {
                return json_decode($data, true);
            }
            if ($data == 'true') {
                return true;
            } else if ($data == 'false') {
                return false;
            }
            return $data;
        } else {
            throw new RpcException('服务异常', 100);
        }

    }



    public function connect() {
        $this->cli = new Client($this->config[ 'host' ], $this->config[ 'port' ], false);
        $this->cli->set(['timeout' => $this->config[ 'timeout' ]]);
        $this->cli->connect();
    }

}