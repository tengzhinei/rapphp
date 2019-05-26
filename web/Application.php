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
use rap\exception\handler\ApiExceptionReport;
use rap\exception\handler\ExceptionHandler;
use rap\exception\handler\PageExceptionHandler;
use rap\exception\handler\PageExceptionReport;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\rpc\Rpc;
use rap\ServerEvent;
use rap\util\Lang;
use rap\web\interceptor\Interceptor;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Dispatcher;
use rap\web\mvc\Router;
use rap\web\mvc\RouterHandlerMapping;

abstract class Application {

    /**
     * swoole_server_http
     */
    public $server;
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    private $interceptors        = [];
    private $interceptors_except = [];

    public function _initialize(Dispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
        include_once __DIR__ . "/../" . 'common.php';
        $interceptors = Config::getFileConfig()[ 'interceptors' ];
        if ($interceptors) {
            foreach ($interceptors as $interceptor) {
                $this->interceptors[$interceptor] = 100;
            }
        }
        $interceptors_except = Config::getFileConfig()[ 'interceptors_except' ];
        if ($interceptors_except) {
            $this->interceptors_except = $interceptors;
        }
    }

    public function _prepared() {
        //注射rpc功能
        Rpc::register();
        $this->addHandlerMapping();
    }

    /**
     * 添加拦截器
     *
     * @param $clazz
     * @param int $priority 优先
     */
    public function addInterceptor($clazz,$priority) {
        $this->interceptors[$clazz] =$priority;
    }


    public function addHandlerMapping() {
        $autoMapping = new AutoFindHandlerMapping();
        $this->dispatcher->addHandlerMapping($autoMapping);
        $router = new Router();
        $routerMapping = new RouterHandlerMapping($router);
        $this->dispatcher->addHandlerMapping($routerMapping);
        $this->init($autoMapping, $router);
        asort($this->interceptors);
    }


    public function start(Request $request, Response $response) {
        try {
            if(!IS_SWOOLE){
                Event::trigger(ServerEvent::onServerWorkStart,null,0);
            }
            //加载语言包
            Lang::loadLand($request);
            if ($this->interceptors) {
                /* @var $interceptor Interceptor */
                $url = $request->url();
                $is_interceptor = true;
                foreach ($this->interceptors_except as $item) {
                    if (strpos($url, $item) === 0) {
                        $is_interceptor = false;
                        break;
                    }
                }
                if ($is_interceptor) {
                    foreach ($this->interceptors as $interceptor=>$priority) {
                        $interceptor = Ioc::get($interceptor);
                        $value = $interceptor->handler($request, $response);
                        if ($value) {
                            return;
                        }
                    }
                }
            }
            $this->dispatcher->doDispatch($request, $response);
        } catch (\Exception $exception) {
            $this->handlerException($request, $response, $exception);
        } catch (\Error $error) {
            $this->handlerException($request, $response, new ErrorException($error));
        }
    }

    public function handlerException(Request $request, Response $response, \Exception $exception) {
        Log::save();
        $ext = $request->ext();
        $debug = Config::getFileConfig()[ "app" ][ "debug" ];
        //没有后缀的或者后缀为 json 的认定返回类型为api的
        if (!($exception instanceof MsgException) && $debug) {
            if (!$ext || $ext == 'json') {
                $handler = Ioc::get(ApiExceptionReport::class);
            } else {
                $handler = Ioc::get(PageExceptionReport::class);
            }
        } else {
            /* @var ExceptionHandler */
            $handler = Ioc::get((!$ext || $ext == 'json') ? ApiExceptionHandler::class : PageExceptionHandler::class);
        }
        $handler->handler($request, $response, $exception);
    }

    public abstract function init(AutoFindHandlerMapping $autoMapping, Router $router);

    public function console($argv) {
        /* @var $console Console */
        $console = Ioc::get(Console::class);
        $console->run($argv);
    }




}