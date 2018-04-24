<?php
namespace rap\web\filter;
use rap\web\Request;
use rap\web\Response;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/20
 * Time: 下午12:03
 */
interface Filter{

    public function handler(Request $request,Response $response);

}