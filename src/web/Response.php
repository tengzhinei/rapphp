<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:34
 */

namespace rap\web;


use rap\aop\Event;
use rap\config\Config;
use rap\ServerEvent;
use rap\session\RedisSession;
use rap\session\HttpSession;
use rap\session\Session;
use rap\swoole\Context;

class Response {
    // 当前的contentType
    protected $contentType = 'text/html';

    // 字符集
    protected $charset = 'utf-8';

    //状态
    protected $code = 200;

    protected $content;

    protected $data = [];
    // header参数
    protected $header = [];

    public $hasSend = false;

    private $request;

    public function setRequest(Request $request) {
        $this->request = $request;
    }

    public function send() {
        if ($this->hasSend) {
            return;
        }

        $this->hasSend = true;
        $this->header[ 'Content-Type' ] = $this->contentType . '; charset=' . $this->charset;
        if (!headers_sent() && !empty($this->header)) {
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . ':' . $val);
            }
        }
        echo $this->content;
        if (function_exists('fastcgi_finish_request')) {
            // 提高页面响应
            fastcgi_finish_request();
        }
        if(!IS_SWOOLE){
            Event::trigger(ServerEvent::onRequestDefer);
            Context::release();
        }
    }

    public function setContent($content) {
        $this->content = $content;
    }


    public function redirect($url, $code = 302) {
        $this->code($code);
        $this->header("location", $url);
        $this->send();
    }

    /**
     * 获取头部信息
     *
     * @param string $name 头部名称
     *
     * @return mixed
     */
    public function getHeader($name = '') {
        return !empty($name) ? $this->header[ $name ] : $this->header;
    }

    /**
     * 页面输出类型
     *
     * @param string $contentType 输出类型
     * @param string $charset     输出编码
     *
     * @return $this
     */
    public function contentType($contentType, $charset = 'utf-8') {
        $this->contentType = $contentType;
        $this->charset = $charset;
        return $this;
    }


    /**
     * 设置响应头
     * @access public
     *
     * @param string|array $name  参数名
     * @param string       $value 参数值
     *
     * @return $this
     */
    public function header($name, $value = null) {
        if (is_array($name)) {
            $this->header = array_merge($this->header, $name);
        } else {
            $this->header[ $name ] = $value;
        }
        return $this;
    }

    /**
     * 发送HTTP状态
     *
     * @param integer $code 状态码
     *
     * @return $this
     */
    public function code($code) {
        $this->code = $code;
        return $this;
    }

    public function assign($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[ $key ] = $value;
        }
    }

    public function data($key = "") {
        if ($key) {
            return $this->data[ $key ];
        }
        return $this->data;
    }

    public function cookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false) {
        setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @var Session
     */
    protected $session;

    /**
     * @return Session
     */
    public function session() {

        if (!$this->session) {
            if (Config::getFileConfig()['session']['type'] == 'redis') {
                $this->session = new RedisSession($this->request, $this);
            } else {
                $this->session = new HttpSession();
            }
        }
        return $this->session;
    }

    public function sendFile($file, $file_name = '') {
        header("Accept-Ranges: bytes");
        $fp = fopen($file, 'rb');//只读方式打开
        $filesize = filesize($file);//文件大小
        header("Accept-Length: $filesize");
        $this->fileToContentType($file, $file_name);
        if (!headers_sent() && !empty($this->header)) {
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . ':' . $val);
            }
        }
        //清除缓存
        ob_clean();
        flush();
        //设置分流
        $buffer = 4096;
        //来个文件字节计数器
        $count = 0;
        while (!feof($fp) && ($filesize - $count > 0)) {
            //设置文件最长执行时间
            set_time_limit(0);
            $data = fread($fp, $buffer);
            $count += $data;//计数
            echo $data;//传数据给浏览器端
        }

        Event::trigger(ServerEvent::onRequestDefer);
        die;
    }

    protected function fileToContentType($file, $file_name = '') {
        //等于默认值说明没有设置
        if ($this->contentType == 'text/html') {
            $items = explode('.', $file);
            $length = count($items);
            if ($length > 1) {
                $type = $items[ $length - 1 ];
            } else {
                $type = "";
            }
            $types = ['png' => 'image/png',
                      'jpg' => 'image/jpeg',
                      'gif' => 'image/*'];
            if (key_exists($type, $types)) {

                $this->header[ 'Content-Type' ] = $types[ $type ];
            } else {
                $this->header[ 'Content-Type' ] = "application/octet-stream";
                $this->header[ "Accept-Length" ] = filesize($file);
                if (!$file_name) {
                    $file_name = substr($file, strrpos($file, DS) + 1);
                }
                $this->header[ 'Content-Disposition' ] = " attachment; filename=" . $file_name;
            }
        } else {
            $this->header[ 'Content-Type' ] = $this->contentType;
        }

    }

}