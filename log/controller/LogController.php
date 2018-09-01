<?php
namespace rap\log\controller;
use rap\cache\Cache;
use rap\config\Config;
use rap\exception\MsgException;
use rap\log\Log;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/3/12
 * Time: 上午9:24
 */
class LogController{

    public function index($name,$secret){
        if($name&&$secret){
            $debug_secret= Config::get('app','debug_secret');
            if($secret!=$debug_secret){
                response()->assign('tip','调试密钥错误');
            }else{
                Log::debugSession($name);
                Log::debug($name.'进入调试');
                return redirect('page');
            }
        }
        return twig("login");
    }

    public function debug($name,$msg){
        if(!$name){
            $name="我自己";
        }

        if(!$msg){
            $msg="开始调试";
        }

        return body("正在调试程序  <a href='page' target='_blank'>调试输出页面</a>");
    }



    public function logMsg(){
        return Log::debugMsg();
    }

    public function removeAll(){
        Log::debugMsg();
        Cache::remove(md5('Log.debugSession'));
    }

    public function page(){
        $session_ids = Cache::get(md5('Log.debugSession'));
        $session_id = request()->session()->sessionId();
        if (key_exists($session_id, $session_ids) ) {
            return "log";
        }
        return redirect('index');
    }

}