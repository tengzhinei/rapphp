<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:37
 */

namespace rap\web;

use rap\aop\Event;
use rap\config\Config;
use rap\console\Console;
use rap\exception\ErrorException;
use rap\exception\handler\ApiExceptionHandler;
use rap\exception\handler\ExceptionHandler;
use rap\exception\handler\PageExceptionHandler;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\rpc\Rpc;
use rap\ServerEvent;
use rap\util\Lang;
use rap\web\interceptor\AfterInterceptor;
use rap\web\interceptor\Interceptor;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Dispatcher;
use rap\web\mvc\Router;
use rap\web\mvc\RouterHandlerMapping;
use rap\web\response\JSONBody;
use rap\web\response\ResponseBody;

abstract class Application
{

    /**
     * swoole_server_http
     */
    public $server;

    public $worker_id=-1;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    private $interceptors        = [];
    private $interceptors_except = [];

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        include_once __DIR__ . "/../" . 'common.php';
        $interceptors = Config::getFileConfig()[ 'interceptors' ];
        if ($interceptors) {
            foreach ($interceptors as $interceptor) {
                $this->interceptors[ $interceptor ] = 100;
            }
        }
        $interceptors_except = Config::getFileConfig()[ 'interceptors_except' ];
        if ($interceptors_except) {
            $this->interceptors_except = $interceptors;
        }
    }

    public function _prepared()
    {
        //注射rpc功能
        Rpc::register();
        $this->addHandlerMapping();
    }

    /**
     * 添加拦截器
     *
     * @param     $clazz
     * @param int $priority 优先
     */
    public function addInterceptor($clazz, $priority = 99)
    {
        $this->interceptors[ $clazz ] = $priority;
    }


    public function addHandlerMapping()
    {
        $autoMapping = new AutoFindHandlerMapping();
        $this->dispatcher->addHandlerMapping($autoMapping);
        $router = new Router();
        $routerMapping = new RouterHandlerMapping($router);
        $this->dispatcher->addHandlerMapping($routerMapping);
        $this->init($autoMapping, $router);
        asort($this->interceptors);


    }


    public function start(Request $request, Response $response)
    {
        try {
            if (!IS_SWOOLE) {
                Event::trigger(ServerEvent::ON_SERVER_WORK_START, null, 0);
            }
            Event::trigger(ServerEvent::ON_REQUEST_START);
            Log::info('http request start', ['url' => $request->url()]);
            //加载语言包
            Lang::loadLand($request);
            $need_interceptors=$this->interceptors&&$this->needInterceptor($request);
            /* @var $interceptor Interceptor */
            if ($need_interceptors) {
                $this->beforeInterceptors($request,$response);
            }
            if($response->hasSend){
               return;
            }
            $this->dispatcher->doDispatch($request, $response);
            if ($need_interceptors) {
                $this->afterInterceptors($request,$response);
            }
            $response->send();
        } catch (\Exception $exception) {
            $this->handlerException($request, $response, $exception);
        } catch (\Error $error) {
            $this->handlerException($request, $response, new ErrorException($error));
        }
    }

    /**
     * 运行前置烂机器
     * @param Request  $request
     * @param Response $response
     */
    private function beforeInterceptors(Request $request, Response $response){
        foreach ($this->interceptors as $interceptor => $priority) {
            $interceptor = Ioc::get($interceptor);
            if(!($interceptor instanceof Interceptor)){
                continue;
            }
            $value = $interceptor->handler($request, $response);
            if(!$value&&!is_array($value)){
                continue;
            }elseif ($value instanceof ResponseBody) {
                $value->beforeSend($response);
                $response->send();
                return;
            } else if(is_bool($value)){
                return;
            } else {
                $json = new JSONBody($value);
                $json->beforeSend($response);
                $response->send();
                return;
            }
        }
    }

    /**
     * 运行后置拦截器
     * @param Request  $request
     * @param Response $response
     */
    private function afterInterceptors(Request $request, Response $response){
        foreach ($this->interceptors as $interceptor => $priority) {
            $interceptor = Ioc::get($interceptor);
            if(!($interceptor instanceof AfterInterceptor)){
                continue;
            }
            $interceptor->afterDispatcher($request,$response);
        }
    }


    private function needInterceptor(Request $request){
        $url = $request->url();
        foreach ($this->interceptors_except as $item) {
            if (strpos($url, $item) === 0) {
                return false;
            }
        }
        return true;

    }

    public function handlerException(Request $request, Response $response, \Exception $exception)
    {
        $ext = $request->ext();
        if (($ext == 'json' ||
                strpos($request->header('accept'), 'html') ===false||
                $request->header('rpc-interface')) && $ext != 'html') {
            $handler = ApiExceptionHandler::class;
        } else {
            $handler = PageExceptionHandler::class;
        }
        /* @var ExceptionHandler */
        $handler = Ioc::get($handler);
        $handler->handler($request, $response, $exception);
    }

    abstract public function init(AutoFindHandlerMapping $autoMapping, Router $router);

    public function console($argv)
    {
        /* @var $console Console */
        $console = Ioc::get(Console::class);
        $console->run($argv);
    }
}
