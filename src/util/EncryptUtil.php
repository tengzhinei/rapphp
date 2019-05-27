<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/10/15
 * Time: 下午8:46
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;

/**
 * 一个简单的加密解密工具
 */
class EncryptUtil {

    public static function decrypt($value,$sign) {
        $m=self::getBytes($sign);
        $bytes=self::getBytes(base64_decode($value));
        $b=[];
        for($i = 0; $i < count($bytes); $i++ ){
            $b[] = $bytes[$i]^$m[$i % count($m)];
        }
        $value= self::toStr($b);
        return $value;
    }

    public static function encrypt($value,$sign){
        $m=self::getBytes($sign);
        $bytes=[];
        for($i = 0; $i < strlen($value); $i++ ){
            $bytes[] = ord($value[$i])^$m[$i % count($m)];
        }
        return base64_encode( self::toStr($bytes));

    }

    public static  function getBytes($string) {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++ ){
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    public static  function toStr($bytes) {
        $str = '';
        foreach($bytes as $ch) {
            $str .= chr($ch);
        }
        return $str;
    }

}