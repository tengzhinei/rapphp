<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/4
 * Time: 上午11:00
 */

namespace rap\log;


use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use rap\cache\Cache;
use rap\ioc\Ioc;
use rap\swoole\Context;

class Log {

    /**
     * @var bool 自动保存
     */
    private static $autoSave = false;
    private static $logs     = [];


    /**
     * @param string $name
     *
     * @return LoggerInterface
     */
    private static function getLog($name = LoggerInterface::class) {
        /* @var LoggerInterface */
        $logger = Ioc::getInstance($name);
        if (!$logger) {
            $logger = new Logger("rap.log");
            $handler = new RotatingFileHandler(RUNTIME . 'log/log');
            $logger->pushHandler($handler);
            $handler->pushProcessor(function($record) {
                /* @var $processor LogProcessor */
                $processor = Ioc::get(LogProcessor::class);
                return $processor->process($record);
            });
            Ioc::instance($name, $logger);
            return $logger;
        }
        return $logger;
    }

    /**
     * 记录同一 session 下的debug日志
     *
     * @param string $name
     */
    public static function debugSession($name = "") {
        $session_id = request()->session()->sessionId();
        $sessionIds = Cache::get(md5('Log.debugSession'), []);
        $sessionIds[ $session_id ] = $name;
        Cache::set(md5('Log.debugSession'), $sessionIds);
    }

    /**
     * 日志记录 等级debug
     *
     * @param string $message string 消息
     * @param string $type    类型
     * @param bool   $force   是否强制记录
     */
    public static function debugtest($message, $type = 'user', $force = false) {
        if (!(is_string($message) || is_int($message))) {
            $message = json_decode($message);
        }
        if (!request()) {
            return;
        }
        $session_ids = Cache::get(md5('Log.debugSession'));
        $session_id = request()->session()->sessionId();
        if ($session_ids && key_exists($session_id, $session_ids) || $force) {
            $name = $session_ids[ $session_id ];
            list($usec, $sec) = explode(" ", microtime());
            $time = ((float)$usec + (float)$sec);
            list($usec, $sec) = explode(".", $time);
            $date = date('H:i:s.x', $usec);
            $time = str_replace('x', $sec, $date);

            $msg = ['name' => $name,
                    'session' => $session_id,
                    'type' => $type,
                    'time' => $time,
                    'msg' => $message];
            $msgs = Cache::get(md5("Log.debugMsg"), []);
            $msgs[] = $msg;
            Cache::set(md5("Log.debugMsg"), $msgs, 60);
        }
        //        self::log('debug', $message);

    }

    /**
     * 获取debug日志
     * @return array|mixed
     */
    public static function debugMsg() {
        $msgs = Cache::get(md5("Log.debugMsg"), []);
        Cache::remove(md5("Log.debugMsg"));
        return $msgs;
    }


    public static function debug($message, array $context = array()) {
        self::getLog()->debug($message, $context);
    }


    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function info($message, array $context = array()) {
        self::getLog()->info($message, $context);
    }

    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function notice($message, array $context = array()) {
        self::getLog()->notice($message, $context);
    }

    /**
     * 日志记录 等级warning
     *
     * @param $message
     */
    public static function warning($message, array $context = array()) {
        self::getLog()->warning($message, $context);
    }

    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function error($message, array $context = array()) {
        self::getLog()->error($message, $context);
    }

    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function critical($message, array $context = array()) {
        self::getLog()->critical($message, $context);
    }

    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function alert($message, array $context = array()) {
        self::getLog()->alert($message, $context);
    }

    /**
     * 日志记录 等级debug
     *
     * @param $message
     */
    public static function emergency($message, array $context = array()) {
        self::getLog()->emergency($message, $context);
    }

}