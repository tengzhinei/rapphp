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
use rap\rpc\RpcClientException;
use rap\web\mvc\ControllerHandlerAdapter;
use rap\web\mvc\HandlerMapping;
use rap\web\Request;
use rap\web\Response;

class RpcHandlerMapping implements HandlerMapping {

    private $config = ['path' => '/rpc_____call',
                       ];

    private $rpcHandlerAdapter;

    /**
     * @param RpcHandlerAdapter $rpcHandlerAdapter
     */
    public function _initialize(RpcHandlerAdapter $rpcHandlerAdapter) {
        $config = Config::getFileConfig()[ 'rpc_service' ];
        $this->config = array_merge($this->config, $config);
        $this->rpcHandlerAdapter = $rpcHandlerAdapter;
    }

    public function map(Request $request, Response $response) {
        $path = $request->routerPath();

        if ($path !== $this->config[ 'path' ]) {
            return null;
        }

        return $this->rpcHandlerAdapter;
    }


}