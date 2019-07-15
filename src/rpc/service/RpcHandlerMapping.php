<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/9
 * Time: 下午10:23
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc\service;


use rap\config\Config;
use rap\ioc\Ioc;
use rap\web\mvc\HandlerMapping;
use rap\web\Request;
use rap\web\Response;

class RpcHandlerMapping implements HandlerMapping {

    private $config = ['path' => '/rpc_____call',
                       ];

    public function _initialize() {
        $config = Config::getFileConfig()[ 'rpc_service' ];
        $this->config = array_merge($this->config, $config);
    }

    public function map(Request $request, Response $response) {
        $path = $request->routerPath();

        if ($path !== $this->config[ 'path' ]) {
            return null;
        }

        return Ioc::get(RpcHandlerAdapter::class);
    }


}