<?php

/**
 * 通用成功返回 json
 *
 * @param string $msg
 *
 * @return array
 */
function success($msg = "") {
    return ['success' => true, 'msg' => $msg];
}

/**
 * 通用失败返回数据
 *
 * @param string $msg
 *
 * @return array
 */
function fail($msg = "") {
    return ['success' => false, 'msg' => $msg];
}

/**
 * 重定向
 *
 * @param $url
 *
 * @return string
 */
function redirect($url) {
    return new \rap\web\response\Redirect($url);
}


/**
 * 内容直接输出
 *
 * @param $body
 *
 * @return string
 */
function body($body) {
    return new \rap\web\response\PlainBody($body);
}

function twig($body) {
    return 'twig:' . $body;
}

function downloadFile($file,$file_name='') {
    return new \rap\web\response\Download($file,$file_name);
}


function download($file,$file_name='') {
    return new \rap\web\response\Download($file,$file_name);
}


/**
 * 缓存快捷方法
 *
 * @param        $key
 * @param string $value
 * @param int    $expire
 *
 * @return mixed
 */
function cache($key, $value = '', $expire = 0) {
    if ($value === '') {
        return \rap\cache\Cache::getCache()->get($key, '');
    } elseif (is_null($value)) {
        // 删除缓存
        return \rap\cache\Cache::getCache()->remove($key);
    } else {
        return \rap\cache\Cache::getCache()->set($key, $value, $expire);
    }
}

/**
 * 用于显示的错误异常
 *
 * @param      $msg
 * @param int  $code
 * @param null $data
 *
 * @throws \rap\exception\MsgException
 */
function exception($msg, $code = 100000, $data = null) {
    throw new \rap\exception\MsgException($msg, $code, $data);
}

/**
 * @return float
 */
function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}



/**
 * 检查参数
 *
 * @param string $value
 * @param string $as_name
 * @param bool   $throw
 *
 * @return \rap\web\validate\Validate
 */
function validate($value, $as_name = '', $throw = true) {
    return \rap\web\validate\Validate::param($value, $as_name, $throw);
}


/**
 * @param      $value
 * @param bool $throw
 *
 * @return \rap\web\validate\Validate
 */
function validateRole($value, $throw = true) {
    return validate($value, '', $throw)->msg('role');
}


/**
 * 检查 request 里的参数
 *
 * @param string $name
 * @param string $as_name
 * @param bool   $throw
 *
 * @return \rap\web\validate\Validate
 */
function validateParam($name, $as_name = '', $throw = true) {
    return \rap\web\validate\Validate::request($name, $as_name, $throw);
}

function lang($moudle, $key, $vars = []) {
    return \rap\util\Lang::get($moudle, $key, $vars);
}

/**
 * @param      $value
 * @param      $as_name
 * @param bool $throw
 *
 * @return \rap\web\validate\Validate
 */
function validateParamRole($value, $as_name, $throw = true) {
    return validateParam($value, $as_name, $throw)->msg('role');
}

function timer_after($time, Closure $closure) {
    $id = 0;
    if (IS_SWOOLE) {
        $id = swoole_timer_after($time, function() use ($closure) {
            $closure();
            \rap\swoole\Context::release();
        });
    }
    return $id;
}

function timer_tick($time, Closure $closure) {
    $id = 0;
    if (IS_SWOOLE) {
        $id = swoole_timer_tick($time, function() use ($closure) {
            $closure();
            \rap\swoole\Context::release();
        });
    }
    return $id;
}

/**
 * 获取request
 * 只能在主进程使用,不可以在异步或Task中使用
 * @return \rap\web\Request
 */
function request() {
    return \rap\swoole\CoContext::getContext()->getRequest();
}

/**
 * 获取response
 * 只能在主进程使用,不可以在异步或Task中使用
 * @return \rap\web\Response
 */
function response() {
    return \rap\swoole\CoContext::getContext()->getResponse();
}

/**
 * worker进程内的容器重新加载
 */
function worker_scope_change()
{
    $application = \rap\ioc\Ioc::get(\rap\web\Application::class);
    /* @var $workerAtomic \Swoole\Atomic  */
    $workerAtomic = $application->workerAtomic;
    if($workerAtomic){
        $workerAtomic->add(1);
    }
    \rap\ioc\Ioc::workerScopeClear();
}