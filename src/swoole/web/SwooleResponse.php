<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:35
 */

namespace rap\swoole\web;

use rap\session\RedisSession;
use rap\session\Session;
use rap\web\Response;

class SwooleResponse extends Response
{

    private $request;
    private $swooleResponse;

    public function swoole($request, $response)
    {
        $this->swooleResponse = $response;
        $this->request = $request;
    }

    public function send()
    {
        if ($this->hasSend) {
            return;
        }
        $this->hasSend = true;
        // 发送状态码
        $this->swooleResponse->status($this->code);
        $this->header['Content-Type'] = $this->contentType . '; charset=' . $this->charset;
        if (!empty($this->header)) {
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                $this->swooleResponse->header($name, $val, $f);
            }
        }
        // $this->swooleResponse->  gzip(1);
        $this->swooleResponse->end($this->content);
    }

    public function cookie(
        $key,
        $value = '',
        $expire = 0,
        $path = '/',
        $domain = '',
        $secure = false,
        $httponly = false
    )
    {
        $this->swooleResponse->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     *
     * @return Session
     */
    public function session()
    {
        if (!$this->session) {
            $this->session = new RedisSession($this->request, $this);
        }
        return $this->session;
    }

    /**
     * 发送文件
     * @param $file
     * @param $file_name
     */
    public function sendFile($file, $file_name = '')
    {
        $this->hasSend = true;
        $this->swooleResponse->status($this->code);
        $this->fileToContentType($file, $file_name);
        if (!empty($this->header)) {
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                $this->swooleResponse->header($name, $val);
            }
        }
        $this->swooleResponse->sendfile($file);
    }
}
