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

    public function debug($name,$msg){
        if(Config::get("app","debug")){
            if(!$name){
                $name="我自己";
            }
            Log::debugSession($name);
            if(!$msg){
                $msg="开始调试";
            }
            Log::debug($msg);
            return body("正在调试程序 <form method='get'><div><span>你的名字:</span><input name='name' value='$name'></div><div><span>调试信息:</span><input name='msg' value='$msg'></div><button type='submit'>提交</button></form>  <a href='page' target='_blank'>调试输出页面</a>");
        }

        return body("");
    }

    public function test($a,$b,$c){
        if($a=='1'){
            throw new MsgException("你没有权限查看");
        }

        if($b=='1'){
            throw new MsgException("内容不存在");
        }
        try{
//            逻辑代码
        }catch (\Exception $exception) {
            //能处理就处理
            //不能  throw $exception;
        }
        return ['a'=>$a,'b'=>$b,'c'=>$c];

    }

    public function logMsg(){
        return Log::debugMsg();
    }

    public function removeAll(){
        Cache::remove(md5('Log.debugSession'));
    }

    public function page(){
        return "log";
    }

}