<?php
namespace rap\web\mvc;
use rap\web\HttpRequest;
use rap\web\HttpResponse;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:46
 */
interface  HandlerMapping{

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return HandlerAdapter
     */
    public function map(HttpRequest $request,HttpResponse $response);

}