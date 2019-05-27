<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 下午2:33
 */

namespace rap\exception\handler;


use rap\config\Config;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\web\mvc\view\View;
use rap\web\Request;
use rap\web\Response;

class PageExceptionHandler implements  ExceptionHandler{
    function handler(Request $request, Response $response, \Exception $exception){
        $msg=$exception->getMessage();
        $template_base = Config::get('view','template_base');
        $file=$template_base.'/exception';
        $msg .= "  |" . str_replace("rap\\exception\\", "", get_class($exception)) . " in " . str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
        if(!is_file(ROOT_PATH.$file.'.html')){
            $file=str_replace(ROOT_PATH,'',__DIR__).'/exception';
        }
        /* @var $view View  */
        $view=Ioc::get(View::class);
        $view->assign(['msg'=>$msg,'exception'=>$exception]);
        $response->setContent($view->fetch($file));
        $response->send();

    }
}