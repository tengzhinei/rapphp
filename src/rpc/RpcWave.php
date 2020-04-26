<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/7
 * Time: 下午4:15
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc;

use rap\aop\Aop;
use rap\aop\JoinPoint;
use rap\ioc\ScopeProperty;
use rap\log\Log;
use rap\rpc\client\RpcClient;
use rap\rpc\client\RpcClientException;
use rap\swoole\pool\Pool;

/**
 * 主要实现了 RCP 远程调用和熔断器的功能
 */
class RpcWave {
    use ScopeProperty;

    const FUSE_STATUS_OPEN      = 1;
    const FUSE_STATUS_HALF_OPEN = 2;
    const FUSE_STATUS_CLOSE     = 3;

    /**
     * @var Rpc
     */
    private $rpc;


    /**
     * RpcWave __construct.
     *
     * @param Rpc $rpc
     */
    public function __construct(Rpc $rpc) {
        $this->rpc = $rpc;
    }

    public function before(JoinPoint $point) {
        $method = $point->getMethod();//对应的反射方法
        /* @var $obj RpcSTATUS */
        $obj = $point->getObj();//对应包装对象
        $header = [];
        $context = ['clazz' => $point->getOriginalClass(),
                    'name' => $method->getName(),
                    'args' => $point->getArgs(),
                    'header' => $header];
        /* @var $client RpcClient */
        $client = $this->rpc->getRpcClient($point->getOriginalClass());
        try {
            $fuseConfig = $client->fuseConfig();
            //熔断器开启
            if ($obj->FUSE_STATUS == RpcWave::FUSE_STATUS_OPEN) {
                //熔断30s
                if (time() - $obj->FUSE_OPEN_TIME < $fuseConfig[ 'fuse_time' ]) {
                    //使用服务降级
                    Log::debug('RPC degrade service:服务降级', $context);
                    return null;
                }
                $obj->FUSE_STATUS = RpcWave::FUSE_STATUS_HALF_OPEN;
                //半开
            }
            if ($obj->FUSE_STATUS == RpcWave::FUSE_STATUS_HALF_OPEN) {
                $obj->FUSE_STATUS = RpcWave::FUSE_STATUS_OPEN;
                try {
                    $args = $point->getArgs();
                    $value = $client->query($point->getOriginalClass(), $method->getName(), $args, $header);
                    $obj->FUSE_STATUS = RpcWave::FUSE_STATUS_CLOSE;
                    Log::alert('RPC FUSE_STATUS_CLOSE :关闭熔断', $context);
                    if ($value == null) {
                        $value = Aop::AOP_NULL;
                    }
                    return $value;
                } catch (RpcClientException $exception) {
                    $obj->FUSE_STATUS = RpcWave::FUSE_STATUS_OPEN;
                    return null;
                }
            } else {
                try {
                    $args = $point->getArgs();
                    Log::debug('RPC query :调用', $context);
                    $value = $client->query($point->getOriginalClass(), $method->getName(), $args, $header);
                    if ($obj->FUSE_FAIL_COUNT) {
                        $obj->FUSE_FAIL_COUNT = 0;
                    }
                    if ($value == null) {
                        $value = Aop::AOP_NULL;
                    }
                    return $value;
                } catch (RpcClientException $exception) {
                    $obj->FUSE_FAIL_COUNT++;
                    //失败一定次数开启熔断
                    if ($obj->FUSE_FAIL_COUNT > $fuseConfig[ 'fuse_fail_count' ]) {
                        $obj->FUSE_OPEN_TIME = time();
                        $obj->FUSE_STATUS = RpcWave::FUSE_STATUS_OPEN;
                        Log::alert('RPC FUSE_OPEN :开启熔断', $context);
                    }
                    Log::warning('RPC FUSE_OPEN :开启熔断', $context);
                    return null;
                }
            }
        } finally {
            Pool::release($client);
        }
        return null;
    }


    /**
     * 网络请求
     * 拥有重试机制和超时机制
     *
     * @param RpcClient $client
     * @param string    $clazz
     * @param string    $name
     * @param  array    $args
     * @param  array    $header
     *
     * @return mixed|null
     */
    public function query(RpcClient $client, $clazz, $name, $args, $header) {
        $retry = 1;
        $timeout = -1;
        $value = null;
        for ($i = 0; $i < $retry; $i++) {
            try {
                $value = $client->query($clazz, $name, $args, $header, $timeout);
            } catch (RpcClientException $exception) {
                throw $exception;
            }
        }
        return $value;


    }

}
