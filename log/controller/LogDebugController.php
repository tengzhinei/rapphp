<?php
namespace rap\log;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/3/12
 * Time: 上午9:24
 */
class LogDebugController{

    public function logSession(){
        Log::debugSession();
        return ['success'=>true];
    }


    public function logMsg(){

    }


}