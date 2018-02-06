<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:37
 */

namespace rap\web;


use rap\console\Console;
use rap\ioc\Ioc;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Dispatcher;
use rap\web\mvc\Router;
use rap\web\mvc\RouterHandlerMapping;

abstract class Application{

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function _initialize(Dispatcher $dispatcher,HttpRequest $request,HttpResponse $response){
        $this->request=$request;
        $this->response=$response;
        $this->dispatcher=$dispatcher;
    }


    public function _prepared(){
        $this->addHandlerMapping();
        $this->aop();
    }

    public function addHandlerMapping(){
        $autoMapping=new AutoFindHandlerMapping();
        $this->dispatcher->addHandlerMapping($autoMapping);
        $router=new Router();
        $routerMapping=new RouterHandlerMapping($router);
        $this->dispatcher->addHandlerMapping($routerMapping);

        $this->init($this->request,$this->response,$autoMapping,$router);
    }

    public function start(){
        //注册应用异常处理
     //   ErrorHandler::register();
            $this->dispatcher->doDispatch($this->request,$this->response);
    }


    public abstract function init(HttpRequest $request,HttpResponse $response,AutoFindHandlerMapping $autoMapping,Router $router);

    /**
     * 应用配置aop
     * @return mixed
     */
    public abstract function aop();

    public function console($argv){
        /* @var $console Console  */
        $console=Ioc::get(Console::class);
        $console->run($argv);
    }
}