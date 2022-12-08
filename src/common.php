<?php

use rap\swoole\pool\Pool;
use rap\web\response\WebResult;
use rap\web\response\Redirect;
use rap\web\response\PlainBody;
use rap\web\response\Download;
use rap\cache\Cache;
use rap\exception\MsgException;
use rap\web\validate\Validate;
use rap\swoole\Context;
use rap\web\Request;
use rap\swoole\CoContext;
use rap\ioc\Ioc;
use rap\web\Application;
use Swoole\Atomic;
use rap\web\Response;
use rap\util\Lang;
use rap\swoole\pool\PoolAble;


function onceContext($name,$args,\Closure  $closure){
    if($args){
        if(is_array($args)){
            foreach ($args as $arg) {
                $name.='|'.$arg;
            }
        }else{
            $name.=$args;
        }
    }
    $value = Context::get($name);
    if('null'===$value){
        return null;
    }
    if($value!==null){
        return $value;
    }
    $value = $closure();
    if($value!==null){
        Context::set($name,$value);
    }else{
        Context::set($name,'null');
    }
    return  $value;
}

/**
 * 通用成功返回 json
 *
 * @param string $msg
 * @param mixed $data
 * @return WebResult
 */
function success($msg = "", $data = '')
{
    return new WebResult(true, $msg, $data);
}

/**
 * 通用失败返回数据
 *
 * @param string $msg
 * @param int $code
 * @return WebResult
 */
function fail($msg = "", $code = 0)
{
    return new WebResult(false, $msg, null, $code);
}

/**
 * 重定向
 *
 * @param $url
 *
 * @return Redirect
 */
function redirect($url)
{
    return new Redirect($url);
}


/**
 * 内容直接输出
 *
 * @param $body
 *
 * @return PlainBody
 */
function body($body)
{
    return new PlainBody($body);
}

/**
 *
 * @param $body
 * @return string
 */
function twig($body)
{
    return 'twig:' . $body;
}

function downloadFile($file, $file_name = '')
{
    return download($file, $file_name);
}


function download($file, $file_name = '')
{
    return new Download($file, $file_name);
}


/**
 * 缓存快捷方法
 *
 * @param        $key
 * @param string $value
 * @param int $expire
 *
 * @return mixed
 */
function cache($key, $value = '', $expire = 0)
{
    /**@var Cache|PoolAble $cache * */
    $cache = Cache::getCache();
    try {
        if ($value === '') {
            return $cache->get($key, '');
        } elseif (is_null($value)) {
            // 删除缓存
            $cache->remove($key);
        } else {
            $cache->set($key, $value, $expire);
        }
    } finally {
        Pool::release($cache);
    }

}

/**
 * 用于显示的错误异常
 *
 * @param      $msg
 * @param int $code
 * @param null $data
 *
 * @throws MsgException
 */
function exception($msg, $code = 100000, $data = null)
{
    throw new MsgException($msg, $code, $data);
}

/**
 * @return float
 */
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}


/**
 * 检查参数
 *
 * @param string $value
 * @param string $as_name
 * @param bool $throw
 *
 * @return Validate
 */
function validate($value, $as_name = '', $throw = true)
{
    return Validate::param($value, $as_name, $throw);
}


/**
 * @param      $value
 * @param bool $throw
 *
 * @return Validate
 */
function validateRole($value, $throw = true)
{
    return validate($value, '', $throw)->msg('role');
}


/**
 * 检查 request 里的参数
 *
 * @param string $name
 * @param string $as_name
 * @param bool $throw
 *
 * @return Validate
 */
function validateParam($name, $as_name = '', $throw = true)
{
    return Validate::request($name, $as_name, $throw);
}

function lang($moudle, $key, $vars = [])
{
    return Lang::get($moudle, $key, $vars);
}

/**
 * @param      $value
 * @param      $as_name
 * @param bool $throw
 *
 * @return Validate
 */
function validateParamRole($value, $as_name, $throw = true)
{
    return validateParam($value, $as_name, $throw)->msg('role');
}

function timer_after($time, Closure $closure)
{
    $id = 0;
    if (IS_SWOOLE) {
        $id = swoole_timer_after($time, function () use ($closure) {
            $closure();
            Context::release();
        });
    }
    return $id;
}

function timer_tick($time, Closure $closure)
{
    $id = 0;
    if (IS_SWOOLE) {
        $id = swoole_timer_tick($time, function () use ($closure) {
            $closure();
            Context::release();
        });
    }
    return $id;
}

/**
 * 获取request
 * 只能在主进程使用,不可以在异步或Task中使用
 * @return Request
 */
function request()
{
    return CoContext::getContext()->getRequest();
}

/**
 * 获取response
 * 只能在主进程使用,不可以在异步或Task中使用
 * @return Response
 */
function response()
{
    return CoContext::getContext()->getResponse();
}

/**
 * worker进程内的容器重新加载
 */
function worker_scope_change()
{
    $application = Ioc::get(Application::class);
    /* @var $workerAtomic Atomic */
    $workerAtomic = $application->workerAtomic;
    if ($workerAtomic) {
        $workerAtomic->add(1);
    }
    Ioc::workerScopeClear();
}
