<?php
namespace rap\web\mvc;

use rap\web\Request;
use rap\web\Response;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:46
 */
interface HandlerMapping
{

    /**
     * @param Request $request
     * @param Response $response
     * @return HandlerAdapter
     */
    public function map(Request $request, Response $response);
}
