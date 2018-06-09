<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/4
 * Time: 上午11:00
 */

namespace rap\log;


use rap\cache\Cache;
use rap\ioc\Ioc;

class Log {

    /**
     * @var bool 自动保存
     */
    private static  $autoSave=false;
    private static  $logs=[];

    /**
     * 记录同一 session 下的debug日志
     * @param string $name
     */
    public static function debugSession($name=""){
        if(!IS_DEV)return;
        session_start();
        $sessionIds=Cache::get(md5('Log.debugSession'),[]);
        $sessionIds[session_id()]=$name;
        Cache::set(md5('Log.debugSession'),$sessionIds);
    }

    /**
     * 日志记录 等级debug
     * @param $message string 消息
     * @param string $type   类型
     * @param bool $force    是否强制记录
     */
    public static function debug($message,$type='user',$force=false){
        if(!IS_DEV)return;
        if(!(is_string($message)||is_int($message))){
            $message=json_decode($message);
        }
        $session_ids=Cache::get(md5('Log.debugSession'));
        session_start();
        if(key_exists(session_id(),$session_ids)||$force){
            $name=$session_ids[session_id()];
           $msg=[
               'name'=>$name,
               'session'=>session_id(),
               'type'=>$type,
               'time'=>date("H:i",time()),
               'msg'=>$message
           ];
            $msgs=Cache::get(md5("Log.debugMsg"),[]);
            $msgs[]=$msg;
            Cache::set(md5("Log.debugMsg"),$msgs,60);
        }
        self::log('debug',$message);

    }

    /**
     * 获取debug日志
     * @return array|mixed
     */
    public static function  debugMsg(){
        $msgs=Cache::get(md5("Log.debugMsg"),[]);
        Cache::remove(md5("Log.debugMsg"));
        return $msgs;
    }

    public static function log($level,$message){
        if(static::$autoSave){
            /* @var $log LogInterface */
            $log=Ioc::get(LogInterface::class);
            $log->writeLog($level,$message);
        }else{
            $logs[]=[
                'time'=>time(),
                'level'=>$level,
                'message'=>$message
            ];
        }
    }

    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function info($message){
        self::log('info',$message);
    }
    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function notice($message){
        self::log('notice',$message);
    }
    /**
     * 日志记录 等级warning
     * @param $message
     */
    public static function warning($message){
        self::log('warning',$message);
    }
    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function error($message){
        self::log('error',$message);
    }
    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function critical($message){
        self::log('critical',$message);
    }
    /**
     * 日志记录 等级debug
     * @param $message

     */
    public static function alert($message){
        self::log('alert',$message);
    }
    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function emergency($message){
        self::log('emergency',$message);
    }


    /**
     * 保存所有未保存的日志
     */
    public static function save(){
        /* @var $log LogInterface */
        if(static::$logs){
            $log=Ioc::get(LogInterface::class);
            $log->writeLogs(static::$logs);
            static::$logs=[];
        }
    }
}