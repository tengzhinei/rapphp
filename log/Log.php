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
use rap\web\Session;

class Log {

    /**
     * @var bool 自动保存
     */
    private static  $autoSave=false;
    private static  $logs=[];

    /**
     * 记录同一 session 下的debug日志
     */
    public static function debugSession(){
        Cache::set(md5('Log.debugSession'),Session::sessionId(),60);
    }

    /**
     * 日志记录 等级debug
     * @param $message
     */
    public static function debug($message){
        $session_id=Cache::get(md5('Log.debugSession'));
        if($session_id==Session::sessionId()){
           $msg=[
               'time'=>time(),
               'msg'=>$message
           ];
          $msgs=Cache::get(md5("Log.debugMsg"));
          if($msgs){
              $msgs=json_decode($msgs,true);
          }else{
              $msgs=[];
          }
          $msgs[]=$msg;
            Cache::set(md5("Log.debugMsg"),$msgs,60);
        }
    }

    /**
     * 获取debug日志
     * @return array|mixed
     */
    public static function  debugMsg(){
        $msgs=Cache::get(md5("Log.debugMsg"));
        if($msgs){
            $msgs=json_decode($msgs,true);
        }else{
            $msgs=[];
        }
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
     * 日志记录 等级debug
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
     * 自动保存
     * @param bool $autoSave
     */
    public static function autoSave($autoSave=true){
        static::$autoSave=$autoSave;
        if($autoSave){
            self::save();
        }
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