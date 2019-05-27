<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/27 1:46 PM
 */

namespace rap\log;


use rap\swoole\Context;

class LogProcessor {

    public function process($record) {
        $record[ 'extra' ][ 'user_id' ] = Context::userId();
        $request = request();
        if($request){
            $record[ 'extra' ][ 'session_id' ] = $request->session()->sessionId();
        }
        return $record;
    }

}