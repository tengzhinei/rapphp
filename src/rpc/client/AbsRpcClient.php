<?php


namespace rap\rpc\client;


use rap\ioc\Ioc;
use rap\rpc\auth\AuthHandler;
use rap\rpc\auth\DefaultAuthHandler;
use rap\swoole\pool\PoolTrait;

abstract class AbsRpcClient implements RpcClient {
    use PoolTrait;

    public    $FUSE_STATUS     = 3;
    public    $FUSE_FAIL_COUNT = 0;
    public    $FUSE_OPEN_TIME;

    protected $config          = ['host' => '',
                                  'auth' => '',
                                  'port' => 9501,
                                  'base_path' => '',
                                  'path' => '/rpc_____call',
                                  'auth_handler' => DefaultAuthHandler::class,
                                  'serialize' => 'serialize',
                                  'timeout' => 3,
                                  'fuse_time' => 30,//熔断器熔断后多久进入半开状态
                                  'fuse_fail_count' => 20,//连续失败多少次开启熔断
                                  'pool' => ['min' => 1, 'max' => 10]];

    /**
     * @var AuthHandler
     */
    protected $authHandler;

    /**
     * 设置配置项
     * @param $config
     */
    public function config($config) {
        $this->config = array_merge($this->config, $config);
        $this->authHandler = Ioc::get($this->config[ 'auth_handler' ]);
    }


    /**
     * 连接池配置
     * @return mixed
     */
    public function poolConfig() {
        return $this->config[ 'pool' ];
    }


    /**
     * 熔断配置
     * @return array
     */
    public function fuseConfig() {
        return ['fuse_time' => $this->config[ 'fuse_time' ],//熔断器熔断后多久进入半开状态
                'fuse_fail_count' => $this->config[ 'fuse_fail_count' ],//连续失败多少次开启熔断
        ];
    }


    /**
     * 获取签过名的请求头
     *
     * @param string $path    路径
     * @param array  $headers 原请求头
     * @param string $data    原始数据
     *
     * @return mixed
     */
    public function authHeader($path, $headers, $data) {
        return $this->authHandler->authHeader($this->config[ 'name' ], $path, $headers, $data);
    }


}