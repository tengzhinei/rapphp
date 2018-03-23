<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:37
 */

namespace rap\web;

/**
 * Class Session
 * @package rap\web
 */
class Session {

    /**
     * @var bool
     */
    private static $start=false;


    public static function registerHandler(\SessionHandler $sessionHandler){
        session_set_save_handler($sessionHandler);
    }

    public static function sessionId(){
        self::start();
        return session_id();
    }

    public static function start(){
        if(!self::$start){
            session_start();
            self::$start=true;
        }
    }

    public static  function pause(){
        session_write_close();
        self::$start=false;
    }

    public static function set($key,$value){
        self::start();
        $_SESSION[$key]=$value;
    }

    public static function get($key){
        self::start();
        return $_SESSION[$key];
    }

    public static function del($key){
        self::start();
        unset($_SESSION[$key]);
    }

    public static function clear(){
        self::start();
        $_SESSION = [];
    }

}