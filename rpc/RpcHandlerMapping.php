<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/9
 * Time: 下午10:23
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc;


use rap\config\Config;
use rap\web\mvc\ControllerHandlerAdapter;
use rap\web\mvc\HandlerAdapter;
use rap\web\mvc\HandlerMapping;
use rap\web\Request;
use rap\web\Response;

class RpcHandlerMapping implements HandlerMapping {

    private $config = ['path' => 'rpc_____call',
                       'token' => '',];

    /**
     * RpcInterceptor _initialize.
     */
    public function _initialize() {
        $config = Config::getFileConfig()[ 'rpc_service' ];
        $this->config = array_merge($this->config, $config);
    }

    public function map(Request $request, Response $response) {
        $path = $request->path();
        if ($path !== $this->config[ 'path' ]) {
            return null;
        }
        $header = $request->header();
        $rpc_token = $header[ 'rpc_token' ];
        if ($rpc_token !== $this->config[ 'token' ]) {
            throw new RpcException('无效的token', 1001);
        }
        $rpc_interface = $header[ 'rpc_interface' ];
        $rpc_method = $header[ 'rpc_method' ];
//        $response->setContent(json_encode($header));
//        $response->send();
//        return;
        $handlerAdapter = new ControllerHandlerAdapter($rpc_interface, $rpc_method);
        $handlerAdapter->pattern($path);
        return $handlerAdapter;
    }


}