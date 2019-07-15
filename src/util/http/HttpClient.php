<?php


namespace rap\util\http;


interface HttpClient {
    /**
     * get 请求
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function get($url, $header = [], $timeout = 0.5);

    /**
     * post请求
     * 表单形式提交
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param float  $timeout 过期时间
     *
     * @return mixed
     */
    public function post($url, $header = [], $data = [], $timeout = 0.5);

    /**
     * put请求
     * data 将已 json 放到 body里
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param float  $timeout 过期时间
     *
     * @return mixed
     */
    public function put($url, $header = [], $data = [], $timeout = 0.5);

    /**
     * 文件上传
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param array  $files   文件
     * @param int    $timeout 过期时间
     *
     * @return mixed
     */
    public function upload($url, $header = [], $data = [], $files = [], $timeout = 5);
}