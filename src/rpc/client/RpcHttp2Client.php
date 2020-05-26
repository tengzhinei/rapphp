<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午3:08
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc\client;

use rap\config\Config;
use rap\swoole\pool\PoolTrait;
use Swoole\Coroutine\Http2\Client;

/**
 * 通过 http2 实现的 Rpc 客户端 支持长链接
 */
class RpcHttp2Client  extends AbsRpcClient
{


    /**
     * @var Client
     */
    private $cli;



    /**
     * 发起请求
     *
     * @param $interface   string 接口
     * @param $method      string 方法名
     * @param $data        array 参数
     * @param $header      array 参数
     * @param $timeout      int|float 超时时间
     *
     * @return mixed
     * @return mixed   返回结果
     */
    public function query($interface, $method, $data, $header = [], $timeout = -1)
    {

        if ($this->config[ 'auth' ]) {
            $headers[ 'Rpc-Auth' ] = md5($this->config[ 'auth' ] . $interface . $method);
        }
        if (!$this->cli) {
            $this->connect();
        }
        if (!$this->cli->connected) {
            $this->cli->connect();
        }
        if (!$this->cli->connected) {
            throw new RpcClientException('连接rpc服务失败', 100);
        }

        $req = new \swoole_http2_request();
        $req->method = 'POST';
        $req->path = $this->config[ 'base_path' ] . $this->config[ 'path' ];
        $authorization = $header[ 'authorization' ];
        $header = array_merge($header, ['Rpc-Client-Name' => Config::get('app')[ 'name' ],
                                        'Rpc-Serialize' => $this->config[ 'serialize' ],
                                        'Authorization' => $this->config[ 'authorization' ],
                                        'Authorization-Forward' => $authorization,
                                        'Rpc-Interface' => $interface,
                                        'Rpc-Method' => $method]);




        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        $header = $this->authHeader($this->config[ 'base_path' ] . $this->config[ 'path' ], $header, $data);
        $req->headers = $header;
        $req->data = $data;
        $this->cli->send($req);

        if($timeout==-1){
          $timeout=$this->config[ 'timeout' ];
        }
        $response = $this->cli->read($timeout);
        if (!$this->cli->errCode && $response->statusCode == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->data;
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


    public function connect()
    {
        $this->cli = new Client($this->config[ 'host' ], $this->config[ 'port' ], false);
        $this->cli->set(['timeout' => $this->config[ 'timeout' ]]);
        $this->cli->connect();
    }
}
