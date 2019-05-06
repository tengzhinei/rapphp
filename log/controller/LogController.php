<?php
namespace rap\log\controller;
use rap\cache\Cache;
use rap\config\Config;
use rap\exception\MsgException;
use rap\log\Log;
use rap\util\Utils;
use rap\web\Request;

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
            if(!$debug_secret){
                response()->assign('tip','调试功能已关闭,请配置密钥');
            }elseif($secret!=$debug_secret){
                response()->assign('tip','调试密钥错误');
            }else{
                Log::debugSession($name);
                Log::debug($name.'进入调试');
                return redirect('/log/page');
            }
        }
        return twig("login");
    }

    public function autoIn($parent_id,$qr){
        $session_id=request()->session()->sessionId();
        if($parent_id&&$parent_id!=$session_id){
            $session_ids = Cache::get(md5('Log.debugSession'));
            if (key_exists($session_id, $session_ids)) {
                return body("欢迎参与调试");
            }
            if (key_exists($parent_id, $session_ids)) {
                $name = $session_ids[ $parent_id ];
                $name = '来自'.$name.'邀请'.substr(time(),7);
                Log::debugSession($name);
                Log::debug($name.'进入调试');
                return body("欢迎参与调试");
            }
            return body("调试二维码已失效");
        }else{
            $referer= request()->scheme()."://".request()->host().request()->path()."?parent_id=$session_id";
            if($qr){
                $file = Utils::getQrcode($referer);
                return downloadFile($file);
            }else{
                $qrPath=request()->scheme()."://".request()->host().request()->path()."?qr=1";
                return body("复制下面链接让调试设备打开<br/> $referer</br><img src='$qrPath'/>");
            }
        }
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
            return twig("log");
        }
        return redirect('/log/index');
    }

    public function qrCode(Request $request){
        $referer= $request->header('referer');
        $file = Utils::getQrcode($referer);
        return downloadFile($file);
    }

    public function logout(){
        $sessionIds = Cache::get(md5('Log.debugSession'), []);
        $session_id = request()->session()->sessionId();
        unset($sessionIds[ $session_id ] );
        Cache::set(md5('Log.debugSession'), $sessionIds);
        exception('退出成功');
    }

    public function logoutAll(){
        Log::debugMsg();
        Cache::remove(md5('Log.debugSession'));
        exception('全部退出成功');
    }
}