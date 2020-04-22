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
     * 以 form 表单形式提交
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param float  $timeout 过期时间
     *
     * @return mixed
     */
    public function form($url, $header = [], $data = [], $timeout = 0.5);

    /**
     * post 请求
     * 如果 data 不是字符串, 将会 json_encode
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param float        $timeout 过期时间
     *
     * @return mixed
     */
    public function post($url, $header = [], $data = [], $timeout = 0.5);


    /**
     * put请求
     * 如果 data 不是字符串, 将会 json_encode
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param float        $timeout 过期时间
     *
     * @return mixed
     */
    public function put($url, $header = [], $data = [], $timeout = 0.5);

    /**
     * 文件上传
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param array        $files   文件
     * @param int          $timeout 过期时间
     *
     * @return mixed
     */
    public function upload($url, $header = [], $data = [], $files = [], $timeout = 5);


    /**
     * delete 删除请求
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param float        $timeout 过期时间
     *
     * @return mixed
     */
    public function delete($url, $header = [], $data = [], $timeout = 0.5);

}
