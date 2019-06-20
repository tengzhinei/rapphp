<?php
namespace rap\exception\handler;

use rap\exception\ErrorException;
use rap\exception\MsgException;
use rap\web\Request;
use rap\web\Response;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:28
 */
class ApiExceptionHandler implements ExceptionHandler {
    function handler(Request $request, Response $response, \Exception $exception) {

        if ($exception instanceof ErrorException) {
            $exception = $exception->error;
        }
        $code='101010';
        $msg = $exception->getMessage();
        if (!($exception instanceof MsgException)) {
            $msg .= "  |" . str_replace("rap\\exception\\", "", get_class($exception)) . " in " . str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
        }else{
            $code=$exception->getCode();
        }
        $response->contentType("application/json");
        $value = json_encode(['success' => false,
                              'code' => $code,
                              'msg' => $msg]);
        $response->setContent($value);
        $response->send();

    }


}