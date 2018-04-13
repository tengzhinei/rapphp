<?php
namespace rap\exception\handler;
use rap\exception\ErrorException;
use rap\web\Request;
use rap\web\Response;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 下午2:24
 */


class ApiExceptionReport implements ExceptionHandler{

    function handler(Request $request, Response $response, \Exception $exception){
        if($exception instanceof ErrorException){
            $exception=$exception->error;
        }
        $msg=$exception->getMessage()."  |" .str_replace("rap\\exception\\","",get_class($exception))." in ". str_replace(ROOT_PATH,"",$exception->getFile())." line ".$exception->getLine();
        $response->contentType("application/json");
        $value=json_encode([
            'success'=>false,
            'code'=>'101010',
            'msg'=>$msg
        ]);
        $response->setContent($value);
        $response->send();
    }
}