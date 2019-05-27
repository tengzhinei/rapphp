<?php
namespace rap\exception\handler;
use rap\exception\ErrorException;
use rap\web\Request;
use rap\web\Response;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:28
 */
class PageExceptionReport implements ExceptionHandler{

    function handler(Request $request, Response $response, \Exception $exception){
        if($exception instanceof ErrorException){
            $exception=$exception->error;
        }
        $file= $exception->getFile();
        $msg =$exception->getMessage();
        $info=str_replace("rap\\exception\\","",get_class($exception))." in ". str_replace(ROOT_PATH,"",$file)." line ".$exception->getLine();
        $trace=$exception->getTrace();
        $traceHtml='<ol start="1" >';
        foreach ($trace as $item) {
            $traceHtml.="<li><code >".$item['class'].$item['type'].$item['function']."(...)</code></li>";
        }
        $traceHtml.='</ol>';
        $lines = file($file);
        $start=$exception->getLine()-10;
        $start=$start>-1?$start:0;
        $end=$exception->getLine()+30;
        $end= $end>count($lines)?count($lines):$end;
        $code='<ol start="'.($start+1).'>" >';
        for ($i=$start;$i<$end;$i++){
            if($i+1==$exception->getLine()){
                $code.="<li style='background: bisque;'><code >$lines[$i]</code></li>";
            }else{
                $code.="<li><code >$lines[$i]</code></li>";
            }
        }
        $code.='</ol>';
        $html=file_get_contents(__DIR__.'/report.html');
        $html = str_replace('{$msg}',$msg,$html);
        $html = str_replace('{$info}',$info,$html);
        $html = str_replace('{$code}',$code,$html);
        $html = str_replace('{$trace}',$traceHtml,$html);
        $response->setContent($html);
        $response->send();
    }


}