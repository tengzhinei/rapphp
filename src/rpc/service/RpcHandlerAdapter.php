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


    private $config = ['path' => '/rpc_____call',
                       ];

    /**
     * RpcInterceptor __construct.
     */
    public function __construct() {
        $config = Config::getFileConfig()[ 'rpc_service' ];
        $this->config = array_merge($this->config, $config);
    }


    public function handle(Request $request, Response $response) {
        $rpc_interface =$request->header('rpc-interface');
        $rpc_method =$request->header('rpc-method');
        $serialize = $request->header('rpc-serialize');
        $exception=true;
        try {
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
            $exception=false;
        } catch (RpcServiceException $rpcException) {
            $value = ['type'=>RpcServiceException::class,'code'=>$rpcException->getCode(),'msg'=>$rpcException->getMessage()];
        } catch (MsgException $msgException) {
            $value = ['type'=>MsgException::class,'code'=>$msgException->getCode(),'msg'=>$msgException->getMessage()];
        } catch (\RuntimeException $runtimeException) {
            //TODO LOG
            $value = ['type'=>\RuntimeException::class,'code'=> $runtimeException->getCode(),'msg'=>'rpc call exception '
                .$runtimeException->getMessage()];
        } catch (\Error $error) {
            //TODO LOG
            $value = ['type'=>\Error::class,'code'=> $error->getCode(),'msg'=>'rpc call error '
                .$error->getMessage()];
        }

        if ($value && $serialize == 'serialize') {
            $response->contentType("application/php-serialize");
            if ($exception) {
                $response->header('Rpc-Exception', 'true');
                $response->setContent(serialize($value));
            } else {
                $response->setContent(serialize($value));
            }
        } else if ($value && $serialize == 'json') {
            $response->contentType("application/json");
            if ($exception) {
                $response->header('Rpc-Exception', 'true');
                $response->setContent(json_encode($value));
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