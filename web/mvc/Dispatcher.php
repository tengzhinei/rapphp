<?php
namespace rap\web\mvc;
use rap\ioc\Ioc;
use rap\web\HttpRequest;
use rap\web\HttpResponse;
use rap\web\mvc\view\View;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:44
 */
class Dispatcher{

    /**
     * @var array
     */
    private $handlerMappings=[];

    public function addHandlerMapping(HandlerMapping $handlerMapping){
        $this->handlerMappings[]=$handlerMapping;
    }

    public function doDispatch(HttpRequest $request, HttpResponse $response){
        $adapters=[];
        /* @var $handlerMapping HandlerMapping  */
        foreach ($this->handlerMappings as $handlerMapping) {
            $adapter = $handlerMapping->map($request,$response);
            if($adapter){
                $adapters[]=$adapter;
            }
        }
        /* @var $adapter HandlerAdapter  */
        $adapter=$adapters[0];
        $value=$adapter->handle($request,$response);
        if(is_string($value)){
            if(strpos($value,'redirect:')===0){
                $value=substr($value,strlen('redirect:'));
                http_response_code(200);
                header("location: $value");
                return;
            }
            /* @var View $view  */
            $view=Ioc::get(View::class);
            $view->assign($response->data());
           $po=strpos($value,DIRECTORY_SEPARATOR);
           if($po===0){
               $value=substr($value,1);
           }else{
               $value=$adapter->viewBase().$value;
           }
            $value=$view->fetch($value);
            $response->setContent($value);
        }else if($value){
            $response->contentType("application/json");
            $value=json_encode($value);
            $response->setContent($value);
        }
        $response->send();
    }

}