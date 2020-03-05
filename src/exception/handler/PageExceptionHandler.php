<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 下午2:33
 */

namespace rap\exception\handler;

use rap\config\Config;
use rap\exception\ErrorException;
use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\web\mvc\view\TwigView;
use rap\web\mvc\view\View;
use rap\web\Request;
use rap\web\Response;

class PageExceptionHandler implements ExceptionHandler
{
    public function handler(Request $request, Response $response, \Exception $exception)
    {

        if ($exception instanceof ErrorException) {
            $exception = $exception->error;
        }
        $code='101010';
        $msg = $exception->getMessage();
        if (!($exception instanceof MsgException)) {
            $msg .= "  |" . str_replace("rap\\exception\\", "", get_class($exception))
                . " in " . str_replace(ROOT_PATH, "", $exception->getFile())
                . " line " . $exception->getLine();
            Log::error('http request error handler :' . $exception->getCode() . ' : ' . $msg);
        } else {
            $code=$exception->getCode();
        }
        $template_base = Config::get('view', 'template_base');
        $file=$template_base.'/exception';
        if (!is_file(ROOT_PATH.$file.'.html')) {
            $file=str_replace(ROOT_PATH, '', __DIR__).'/exception';
        }
        /* @var $view View  */
        $view=Ioc::get(TwigView::class);
        $view->assign(['msg'=>$msg,'code'=>$code,'exception'=>$exception]);
        $response->setContent($view->fetch($file));
        $response->send();
    }
}
