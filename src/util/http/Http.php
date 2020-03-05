<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午11:13
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util\http;

use rap\ioc\Ioc;
use rap\util\http\client\CoroutineHttpClient;
use rap\util\http\client\RequestHttpClient;
use Swoole\Coroutine\Http\Client;

class Http
{

    /**
     * @return HttpClient
     */
    private static function client()
    {
        if (IS_SWOOLE && \Co::getuid() !== -1) {
            return Ioc::get(CoroutineHttpClient::class);
        } else {
            return Ioc::get(RequestHttpClient::class);
        }
    }

    /**
     * get 请求
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public static function get($url, $header = [], $timeout = 0.5)
    {
        return self::client()->get($url, $header, $timeout);
    }

    /**
     * post请求
     * 表单形式提交
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public static function post($url, $header = [], $data = [], $timeout = 0.5)
    {
        return self::client()->post($url, $header, $data, $timeout);
    }

    /**
     * put请求
     * data 将已 json 放到 body里
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public static function put($url, $header = [], $data = [], $timeout = 0.5)
    {
        return self::client()->put($url, $header, $data, $timeout);
    }

    /**
     * 文件上传
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param array  $files   文件
     * @param int    $timeout 过期时间
     *
     * @return HttpResponse
     */
    public static function upload($url, $header = [], $data = [], $files = [], $timeout = 5)
    {
        return self::client()->upload($url, $header, $data, $files, $timeout);
    }
}
