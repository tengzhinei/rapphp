<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:37
 */

namespace rap\web;


use rap\config\Config;
use rap\console\Console;
use rap\exception\ErrorException;
use rap\exception\handler\ApiExceptionHandler;
use rap\exception\handler\ApiExceptionReport;
use rap\exception\handler\ExceptionHandler;
use rap\exception\handler\PageExceptionHandler;
use rap\exception\handler\PageExceptionReport;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\web\Interceptor\Interceptor;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Dispatcher;
use rap\web\mvc\Router;
use rap\web\mvc\RouterHandlerMapping;

abstract class Application{

    /**
     * swoole_server_http
     */
    public $server;
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function _initialize(Dispatcher $dispatcher){
        $this->dispatcher=$dispatcher;
        include_once __DIR__."/../".'common.php';
    }

    public function _prepared(){
        $this->addHandlerMapping();
    }


    public function addHandlerMapping(){
        $autoMapping=new AutoFindHandlerMapping();
        $this->dispatcher->addHandlerMapping($autoMapping);
        $router=new Router();
        $routerMapping=new RouterHandlerMapping($router);
        $this->dispatcher->addHandlerMapping($routerMapping);
        $this->init($autoMapping,$router);
    }


    public function start(Request $request, Response $response){
        try{
            $interceptors=Config::getFileConfig()['interceptors'];
            if($interceptors){
                /* @var $interceptor Interceptor  */
                $url=$request->url();
                $except=Config::getFileConfig()['interceptors_except'];
                $is_interceptor=true;
                foreach ($except as $item) {
                    if(strpos($url,$item)===0){
                        $is_interceptor=false;
                        break;
                    }
                }
                if($is_interceptor){
                    foreach ($interceptors as $interceptor) {
                        $interceptor=Ioc::get($interceptor);
                        $value=$interceptor->handler($request,$response);
                        if($value){
                            return;
                        }
                    }
                }

            }
            $this->dispatcher->doDispatch($request,$response);
        }catch (\Exception $exception){
            $this->handlerException( $request, $response,$exception);
        }catch (\Error $error){
            $this->handlerException( $request, $response, new ErrorException($error));
        }
    }

    public function handlerException(Request $request, Response $response, \Exception $exception){
        Log::save();
        $ext = $request->ext();
        $debug=Config::getFileConfig()["app"]["debug"];
        //没有后缀的或者后缀为 json 的认定返回类型为api的
        if(!($exception instanceof MsgException)&&$debug){
                if(!$ext||$ext=='json'){
                    $handler=Ioc::get(ApiExceptionReport::class);
                }else{
                    $handler=Ioc::get(PageExceptionReport::class);
                }
        }else{
            /* @var ExceptionHandler  */
            $handler=Ioc::get((!$ext||$ext=='json')?ApiExceptionHandler::class:PageExceptionHandler::class);
        }
        $handler->handler( $request, $response,$exception);
    }

    public abstract function init(AutoFindHandlerMapping $autoMapping,Router $router);

    public function console($argv){
        /* @var $console Console  */
        $console=Ioc::get(Console::class);
        $console->run($argv);
    }

}