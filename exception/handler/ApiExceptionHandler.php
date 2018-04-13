<?php
namespace rap\exception\handler;
use rap\exception\MsgException;
use rap\web\Request;
use rap\web\Response;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:28
 */
class ApiExceptionHandler implements ExceptionHandler{
    function handler(Request $request, Response $response, \Exception $exception){
        $msg=$exception instanceof MsgException?$exception->getMessage():"服务器处理过程中出现问题";
        $response->contentType("application/json");
        $value=json_encode([
            'success'=>false,
            'code'=>$exception instanceof MsgException?'100000':'101010',
            'msg'=>$msg
        ]);
        $response->setContent($value);
        $response->send();
    }




}