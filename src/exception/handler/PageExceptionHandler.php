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

class PageExceptionHandler implements ExceptionHandler {

    private $content;

    /**
     * PageExceptionHandler __construct.

     */
    public function __construct() {
        $file = ROOT_PATH . 'template/exception.html';
        if (!is_file($file)) {
            $file = __DIR__ . '/exception.html';
        }
        $this->content = file_get_contents($file);
    }

    public function handler(Request $request, Response $response, \Exception $exception) {
        if ($exception instanceof ErrorException) {
            $exception = $exception->error;
        }
        $code = '101010';
        $msg = $exception->getMessage();
        if (!($exception instanceof MsgException)) {
            $msg .= "  |" . str_replace("rap\\exception\\", "", get_class($exception)) . " in " . str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
            Log::error('http request error handler ,' ,['code'=>$exception->getCode(),
                                                        'msg'=>$msg,
                                                        'trace'=>$exception->getTraceAsString()]);
        } else {
            $code = $exception->getCode();
        }
        $content = str_replace('{{msg}}', $msg, $this->content);
        $content = str_replace('{{code}}', $code, $content);
        $response->setContent($content);
        $response->send();
    }


}
