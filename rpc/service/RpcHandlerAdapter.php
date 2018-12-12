<?php
namespace rap\rpc\service;

use rap\config\Config;
use rap\exception\MsgException;
use rap\ioc\Ioc;

use rap\web\mvc\HandlerAdapter;
use rap\web\Request;
use rap\web\Response;

/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午5:50
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
class RpcHandlerAdapter extends HandlerAdapter {


    private $config = ['path' => 'rpc_____call',
                       'token' => '',];

    /**
     * RpcInterceptor _initialize.
     */
    public function _initialize() {
        $config = Config::getFileConfig()[ 'rpc_service' ];
        $this->config = array_merge($this->config, $config);
    }


    public function handle(Request $request, Response $response) {
        $header = $request->header();
        $rpc_interface = $header[ 'rpc_interface' ];
        $rpc_method = $header[ 'rpc_method' ];
        $rpc_token = $header[ 'rpc_token' ];
        $serialize = $request->header('rpc-serialize');
        try {
            if ($rpc_token !== $this->config[ 'token' ]) {
                throw new RpcServiceException('无效的token', 101);
            }
            $service = Ioc::get($rpc_interface);
            if (!($service instanceof RPCable)) {
                throw new RpcServiceException("服务方该接口没有继承RPCable", 102);
            }
            $args = $request->body();
            if ($serialize == 'serialize') {
                $args = unserialize($args);
            } else {
                $args = json_decode($args, true);
            }
            try {
                $method = new \ReflectionMethod(get_class($service), $rpc_method);
            } catch (\Exception $e) {
                throw new RpcServiceException("该接口没有对应的方法", 103);
            }
            $value = $method->invokeArgs($service, $args);
        } catch (RpcServiceException $rpcException) {
            $value = $rpcException;
        } catch (MsgException $msgException) {
            $value = $msgException;
        } catch (\RuntimeException $runtimeException) {
            //TODO LOG
            $value = new RpcServiceException("接口运行遇到错误" . $runtimeException->getMessage(), $runtimeException->getCode());
        } catch (\Error $error) {
            //TODO LOG
            $value = new RpcServiceException("接口运行遇到异常" . $error->getMessage(), $error->getCode());
        }
        if ($value && $serialize == 'serialize') {
            $response->contentType("application/rap-rpc");
            $response->setContent(serialize($value));
        } else if ($value && $serialize == 'json') {
            $response->contentType("application/json");
            if ($value instanceof RpcServiceException) {
                $response->header('rpc-exception', 'true');
                $response->setContent(json_encode(['type' => get_class($value),
                                                   'code' => $value->getCode(),
                                                   'msg' => $value->getMessage()]));
            } else {
                $response->setContent(json_encode($value));
            }
        }
        return null;
    }

    public function viewBase() {
        return '';
    }


}