<?php
namespace rap\web\mvc;

use rap\config\Config;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\swoole\CoContext;
use rap\swoole\Context;
use rap\web\mvc\view\TwigView;
use rap\web\Request;
use rap\web\Response;
use rap\web\mvc\view\View;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:44
 */
class Dispatcher {

    /**
     * @var array
     */
    private $handlerMappings = [];

    public function addHandlerMapping(HandlerMapping $handlerMapping) {
        $this->handlerMappings[] = $handlerMapping;
    }

    public function doDispatch(Request $request, Response $response) {
        $adapters = [];
        /* @var $handlerMapping HandlerMapping */
        foreach ($this->handlerMappings as $handlerMapping) {
            $adapter = $handlerMapping->map($request, $response);
            if ($adapter) {
                $adapters[] = $adapter;
            }
        }
        /* @var $adapter HandlerAdapter */
        if (count($adapters) < 1) {
            throw new MsgException("对应的路径不存在");
        }
        $adapter = $adapters[ 0 ];
        $value = $adapter->handle($request, $response);
        Log::save();
        if (is_string($value)) {
            if (strpos($value, 'redirect:') === 0) {
                $value = substr($value, strlen('redirect:'));
                $response->code(302);
                if(strpos($value,'/')==0){
                    $base_url=Config::get('app','url_base');
                    $value=$base_url.$value;
                }
                $response->header("location", $value);
                $response->send();
                $response->redirect($value, 302);
                return;
            } elseif (strpos($value, 'downloadFile:') === 0) {
                $value = substr($value, strlen('downloadFile:'));
                $response->sendFile($value);
                return;
            } elseif (strpos($value, 'body:') === 0) {
                $value = substr($value, strlen('body:'));
                $response->setContent($value);
            } else {
                if (strpos($value, 'twig:') === 0) {
                    $value = substr($value, strlen('twig:'));
                    $view = new TwigView();
                } else {
                    /* @var View $view */
                    $view = Ioc::get(View::class);
                }
                $view->assign($response->data());
                $po = strpos($value, '/');
                if ($po === 0) {

                    $base = Config::get('view')[ 'template_base' ];
                    $value = $base . $value;
                } else {
                    $value = $adapter->viewBase() . $value;
                }
                $value = $view->fetch($value);
                $response->setContent($value);
            }
        } else if ($value !== null) {
            $response->contentType("application/json");
            $value = json_encode($value);
            $response->setContent($value);
        }
        $start_time=$request->server('request_time_float');
        $response->header('rap-time', getMillisecond() - (int)($start_time*1000));
        $response->send();
    }

}