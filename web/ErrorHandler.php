<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午6:09
 */

namespace rap\web;


class ErrorHandler{

    /**
     * 注册异常处理
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL& ~E_NOTICE);
        set_error_handler([__CLASS__, 'onError']);
        set_exception_handler([__CLASS__, 'onException']);
        register_shutdown_function([__CLASS__, 'onShutdown']);
    }

    /**
     * Exception Handler
     * @param  \Exception|\Throwable $e
     */
    public static function onException($e)
    {
    }

    /**
     * Error Handler
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @param array    $errcontext
     */
    public static function onError($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
    {

    }

    /**
     * Shutdown Handler
     */
    public static function onShutdown()
    {
    }

}