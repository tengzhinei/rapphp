<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/9
 * Time: 下午3:11
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc;


use rap\aop\AopBuild;
use rap\aop\Event;
use rap\config\Config;
use rap\ioc\Ioc;
use rap\rpc\client\RpcClient;
use rap\rpc\client\RpcHttp2Client;
use rap\rpc\client\RpcHttpClient;
use rap\rpc\service\RpcHandlerMapping;
use rap\ServerEvent;
use rap\swoole\pool\Pool;
use rap\swoole\pool\ResourcePool;
use rap\web\mvc\Dispatcher;

class Rpc {

    public $rpc_inter = [];

    public static function register() {
        /* @var $rpc Rpc */
        $rpc = Ioc::get(Rpc::class);
        $rpc->init();
        if (IS_SWOOLE) {
            Event::add(ServerEvent::onServerWorkStart, Rpc::class, 'onServerWorkStart');
        }
        /* @var $dispatcher Dispatcher */
        $dispatcher = Ioc::get(Dispatcher::class);
        //rpc 提供方
        $config = Config::getFileConfig()[ 'rpc_service' ];
        if ($config&&$config['open']) {
            $mapping = Ioc::get(RpcHandlerMapping::class);
            $dispatcher->addHandlerMapping($mapping);
        }
    }

    public function init() {
        $rpcs = Config::getFileConfig()[ 'rpc' ];
        if (!$rpcs) {
            return;
        }
        foreach ($rpcs as $rpc => $config) {
            $register = $config[ 'register' ];
            $client = $config[ 'client' ];
            if (!$client) {
                $client = IS_SWOOLE ? 'http2' : 'http';
            }
            /* @var $register RpcRegister */
            $register = new $register;
            $items = $register->register();
            foreach ($items as $clazz => $downgrade) {
                if (is_numeric($clazz)) {
                    $clazz = $downgrade;
                } else {
                    Ioc::bind($clazz, $downgrade);
                }
                $this->rpc_inter[ $clazz ] = $rpc;
                AopBuild::before($clazz)->methodsAll()->wave(RpcWave::class)->using('before')->addPoint();
            }
            if ($client == 'http2') {
                Ioc::bind($rpc, RpcHttp2Client::class, function(RpcHttp2Client $client) use ($config) {
                    $client->config($config);
                });
            } else {
                Ioc::bind($rpc, RpcHttpClient::class, function(RpcHttpClient $client) use ($config) {
                    $client->config($config);
                });
            }


            //需要在 worker 进程调用
        }

    }


    public function onServerWorkStart() {
        $rpcs = Config::getFileConfig()[ 'rpc' ];
        if(IS_SWOOLE){
            //注册连接池
            foreach ($rpcs as $rpc => $config) {
                ResourcePool::instance()->preparePool($rpc);
            }
        }

    }

    /**
     * @param $clazz
     *
     * @return RpcClient
     */
    public function getRpcClient($clazz) {
        $name = $this->rpc_inter[ $clazz ];
        return Pool::get($name);
    }

}