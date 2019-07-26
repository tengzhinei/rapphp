<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/4
 * Time: 上午11:00
 */

namespace rap\log;


use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use rap\config\Config;
use rap\ioc\Ioc;
use rap\swoole\Context;
/**
 * 日志服务
 * @author: 藤之内
 */
class Log {


    /**
     *
     * @param string $name
     *
     * @return LoggerInterface
     */
    public static function getLog($name = LoggerInterface::class) {
        /* @var LoggerInterface */
        $logger = Ioc::getInstance($name);
        if (!$logger) {
            $log_config = Config::get('log');
            $log_config = array_merge(['max' => 10, 'level' => 'notice','channel'=>"rap.log"], $log_config);
            $level = $log_config[ 'level' ];
            $logger = new Logger("rap.log");
            $level = strtoupper($level);
            $log_config[ 'level' ] = constant(Logger::class . "::" . $level);
            $handler = new RotatingFileHandler(RUNTIME . 'log/log.log', $log_config[ 'max' ],$log_config[ 'level' ]);
            $handler->setFormatter(new JsonFormatter());
            $logger->pushHandler($handler);
            $handler->pushProcessor(function($record) {
                $record[ 'extra' ][ 'user_id' ] = Context::userId();
                $request = request();
                if ($request) {
                    $record[ 'extra' ][ 'session_id' ] = $request->session()->sessionId();
                }
                return $record;
            });
            Ioc::instance($name, $logger);
            return $logger;
        }
        return $logger;
    }


    /**
     * 日志记录 等级debug
     * Detailed debug information
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function debug($message, array $context = array()) {
        self::getLog()->debug($message, $context);
    }


    /**
     * 日志记录 等级info
     * Interesting events
     * Examples: User logs in, SQL logs.
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function info($message, array $context = array()) {
        self::getLog()->info($message, $context);
    }

    /**
     * 日志记录 等级notice
     * Uncommon events
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function notice($message, array $context = array()) {
        self::getLog()->notice($message, $context);
    }

    /**
     * 日志记录 等级warning
     * Exceptional occurrences that are not errors
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function warning($message, array $context = array()) {
        self::getLog()->warning($message, $context);
    }

    /**
     * 日志记录 等级error
     *  Runtime errors
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function error($message, array $context = array()) {
        self::getLog()->error($message, $context);
    }

    /**
     * 日志记录 等级critical
     * Critical conditions
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function critical($message, array $context = array()) {
        self::getLog()->critical($message, $context);
    }

    /**
     * 日志记录 等级alert
     * Action must be taken immediately
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     *
     * @param string $message 日志内容
     * @param array  $context 上下文
     */
    public static function alert($message, array $context = array()) {
        self::getLog()->alert($message, $context);
    }

    /**
     * 日志记录 等级emergency
     * Urgent alert.
     *
     * @param $message
     * @param array  $context 上下文
     */
    public static function emergency($message, array $context = array()) {
        self::getLog()->emergency($message, $context);
    }

}