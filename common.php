<?php

function success($msg=""){
    return ['success'=>true,'msg'=>$msg];
}
function fail($msg=""){
    return ['success'=>false,'msg'=>$msg];
}

/**
 * 重定向
 * @param $url
 * @return string
 */
function redirect($url){
    return 'redirect:'.$url;
}

function body($body){
    return 'body:'.$body;
}

function cache($key,$value = '',$expire=0){
    if($value==''){
        return  \rap\cache\Cache::getCache()->get($key,'');
    }elseif (is_null($value)) {
        // 删除缓存
        return \rap\cache\Cache::getCache()->remove($key);
    }else{
        return  \rap\cache\Cache::getCache()->set($key,$value,$expire);
    }
}

function exception($msg,$code=100000,$data=null){
    throw new \rap\exception\MsgException($msg,$code,$data);
}

function getMillisecond(){
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}
