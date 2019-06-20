<?php
error_reporting(E_ALL& ~E_NOTICE& ~E_WARNING&~E_DEPRECATED);
define('RAP_VERSION', '3.2.2');
define('DS', DIRECTORY_SEPARATOR);
ini_set("display_errors", "On");
define('RAP_DIR',__DIR__);
defined('APP_DIR') or define('APP_DIR', 'app');
defined('ROOT_PATH') or define('ROOT_PATH', getcwd() . DS);
defined('APP_PATH') or define('APP_PATH',ROOT_PATH.APP_DIR.DS);
defined('RUNTIME') or define('RUNTIME', ROOT_PATH ."runtime".DS);

// 环境常量
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
defined('IS_WIN') or define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
defined('IS_SWOOLE') or define('IS_SWOOLE', IS_CLI&&$argv[1]=='http'||$argv[1]=='websocket');
$loader = require ROOT_PATH . '/vendor/autoload.php';
$loader->setPsr4(APP_DIR."\\", ROOT_PATH.APP_DIR);
$loader->setPsr4("rap\\aop\\build\\", ROOT_PATH.'aop');
//swoole 模式
if(IS_CLI){
    \rap\ioc\Ioc::bind(\rap\web\Application::class,\rap\RapApplication::class);
    \rap\ioc\Ioc::get(\rap\web\Application::class)->console($argv);
}else{
    //正常模式
    \rap\ioc\Ioc::bind(\rap\web\Application::class,\rap\RapApplication::class);
    $response=new \rap\web\Response();
    $request=new \rap\web\Request($response);
    $session_id = $request->session()->sessionId();
    \rap\swoole\CoContext::setId(md5($session_id.uniqid(mt_rand(), true)));
    \rap\swoole\CoContext::getContext()->setRequest($request);
    \rap\ioc\Ioc::get(\rap\web\Application::class)->start($request,$response);
}