<?php
namespace rap;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Router;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/20
 * Time: 下午8:14
 */
interface Init{
    public function appInit(AutoFindHandlerMapping $autoMapping, Router $router);

}