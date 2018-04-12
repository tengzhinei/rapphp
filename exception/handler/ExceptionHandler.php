<?php
namespace rap\exception\handler;
use rap\web\HttpRequest;
use rap\web\HttpResponse;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:28
 */
interface ExceptionHandler{
     function handler(HttpRequest $request,HttpResponse $response,\Exception $exception);
}