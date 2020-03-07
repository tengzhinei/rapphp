<?php
namespace rap\web\mvc;

use rap\config\Config;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\util\http\Http;
use rap\web\mvc\view\TwigView;
use rap\web\Request;
use rap\web\Response;
use rap\web\mvc\view\View;
use rap\web\response\Html;
use rap\web\response\JSONBody;
use rap\web\response\PlainBody;
use rap\web\response\ResponseBody;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:44
 */
class Dispatcher
{

    /**
     * @var array
     */
    private $handlerMappings = [];

    public function addHandlerMapping(HandlerMapping $handlerMapping)
    {
        $this->handlerMappings[] = $handlerMapping;
    }

    public function doDispatch(Request $request, Response $response)
    {
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
        if ($value instanceof ResponseBody) {
            $value->beforeSend($response);
        } elseif (is_string($value)) {
            $json = new Html($value);
            $json->beforeSend($response);
        } if ($value!=null) {
            $json = new JSONBody($value);
            $json->beforeSend($response);
        }
        $response->send();
    }
}
