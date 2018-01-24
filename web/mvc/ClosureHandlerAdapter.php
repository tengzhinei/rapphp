<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午9:58
 */

namespace rap\web\mvc;


use rap\web\HttpRequest;
use rap\web\HttpResponse;

class ClosureHandlerAdapter  extends HandlerAdapter{

    /**
     * @var \Closure
     */
    private $closure;


    /**
     * ClosureHandlerAdapter constructor.
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure){
        $this->closure=$closure;
    }

    public function handle(HttpRequest $request, HttpResponse $response){

        $closure=$this->closure;
        $value=$this->invokeClosure($closure,$request,$response);
        return $value;
    }

    public function viewBase(){
    }


}