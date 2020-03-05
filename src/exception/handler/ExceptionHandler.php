<?php
namespace rap\exception\handler;

use rap\web\Request;
use rap\web\Response;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:28
 */
interface ExceptionHandler
{
    public function handler(Request $request, Response $response, \Exception $exception);
}
