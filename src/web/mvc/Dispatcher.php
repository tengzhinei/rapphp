<?php
namespace rap\web\mvc;

use rap\exception\MsgException;
use rap\web\Request;
use rap\web\Response;
use rap\web\response\Html;
use rap\web\response\JSONBody;
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
        if (is_string($value)) {
            /* @var $html Html  */
            $value =  new  Html($value);
        } else if ($value!=null&&!($value instanceof ResponseBody)) {
            $value = new JSONBody($value);
        }
        if($value instanceof ResponseBody){
            $value->beforeSend($response);
            $response->send();
        }

    }
}
