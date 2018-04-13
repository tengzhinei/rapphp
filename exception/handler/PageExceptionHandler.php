<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 下午2:33
 */

namespace rap\exception\handler;


use rap\exception\MsgException;
use rap\web\Request;
use rap\web\Response;

class PageExceptionHandler implements  ExceptionHandler{
    function handler(Request $request, Response $response, \Exception $exception){
        $msg=$exception instanceof MsgException?$exception->getMessage():"服务器处理过程中出现问题";
        $html=file_get_contents(__DIR__.'/exception.html');
        $html = str_replace('{$msg}',$msg,$html);
        $response->setContent($html);
        $response->send();
    }
}