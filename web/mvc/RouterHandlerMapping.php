<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午9:39
 */

namespace rap\web\mvc;


use rap\web\HttpRequest;
use rap\web\HttpResponse;

class RouterHandlerMapping implements HandlerMapping{

    /**
     * @var Router
     */
    private $router;

    /**
     * RouterHandlerMapping constructor.
     * @param Router $router
     */
    public function __construct(Router $router){
        $this->router = $router;
    }

    public function map(HttpRequest $request, HttpResponse $response){
            $path=$request->path();
            $pathArray=explode('/',$path);
            array_shift($pathArray);
            return $this->router->match($request,$pathArray);
    }
}