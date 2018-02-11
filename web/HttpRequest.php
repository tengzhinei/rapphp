<?php
namespace rap\web;
use rap\ioc\Ioc;
use rap\storage\File;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:33
 */
class HttpRequest{

    /**
     * @var ValueFilter
     */
    private $valueFilter;


    /**
     * @var array
     */
    private $server;

    private $domain;

    private $baseFile;

    private $url;

    private $put;

    private $body;

    private $header;

    public function _initialize(ValueFilter $valueFilter){
        $this->valueFilter=$valueFilter;
    }

    /**
     * 当前的请求类型
     * @access public
     * @return string
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }


    /**
     * 是否为PUT请求
     * @access public
     * @return bool
     */
    public function isPut()
    {
        return $this->method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @access public
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @access public
     * @return bool
     */
    public function isHead()
    {
        return $this->method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @access public
     * @return bool
     */
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @access public
     * @return bool
     */
    public function isOptions()
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * 获取GET参数
     * @access public
     * @param string|array  $name 变量名
     * @param mixed         $default 默认值
     * @param string|array  $filter 过滤方法
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = null){
        if(!$name){
            $value=$_GET;
        }else{
            $value=$_GET[$name];
        }
        return $this->value($value,$default,$filter);
    }

    /**
     * 获取POST参数
     * @param string $name
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public function post($name = '', $default = null, $filter = null){
        if(!$name){
            $value=$_POST;
        }else{
            $value=$_POST[$name];
        }
       return $this->value($value,$default,$filter);
    }


    /**
     * 获取PUT参数
     * @param string $name
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public function put($name = '', $default = null, $filter = null){
        if (is_null($this->put)) {
            $content = $this->body();
            if (strpos($content, '":')) {
                $this->put = json_decode($content, true);
            } else {
                parse_str($content, $this->put);
            }
        }
        if(!$name){
            $value=$this->put;
        }else{
            $value=$this->put[$name];
        }
        return $this->value($value,$default,$filter);
    }

    /**
     * 获取请求body
     * @return string
     */
    public function body(){
        if(!isset($this->body)){
            $this->body = file_get_contents('php://input');
        }
        return $this->body;
    }
    /**
     * 获取DELETE参数
     * @access public
     * @param string|array      $name 变量名
     * @param mixed             $default 默认值
     * @param string|array      $filter 过滤方法
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = null)
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * 获取PATCH参数
     * @access public
     * @param string|array      $name 变量名
     * @param mixed             $default 默认值
     * @param string|array      $filter 过滤方法
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = null)
    {
        return $this->put($name, $default, $filter);
    }


    /**
     * 获取$_SERVER的信息
     * @param $name
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public function server($name, $default = null, $filter = null)
    {
        if (empty($this->server)) {
            $this->server = $_SERVER;
        }
        if(!$name){
            $value=$this->server;
        }else{
            $value=$this->server[$name];
        }
        return $this->value($value,$default,$filter);
    }

    /**
     * 设置或者获取当前的Header
     * @access public
     * @param string|array  $name header名称
     * @param string        $default 默认值
     * @param string        $filter 过滤器
     * @return string
     */
    public function header($name = '', $default = null, $filter = null)
    {
        if (empty($this->header)) {
            $header = [];
            if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
                $header = $result;
            } else {
                $server = $this->server ?: $_SERVER;
                foreach ($server as $key => $val) {
                    if (0 === strpos($key, 'HTTP_')) {
                        $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                        $header[$key] = $val;
                    }
                }
                if (isset($server['CONTENT_TYPE'])) {
                    $header['content-type'] = $server['CONTENT_TYPE'];
                }
                if (isset($server['CONTENT_LENGTH'])) {
                    $header['content-length'] = $server['CONTENT_LENGTH'];
                }
            }
            $this->header = array_change_key_case($header);
        }
        $name = str_replace('_', '-', strtolower($name));
        if(!$name){
            $value=$this->header;
        }else{
            $value=$this->header[$name];
        }
        return $this->value($value,$default,$filter);
    }


    /**
     * 获取当前包含协议的域名
     * @access public
     * @param string $domain 域名
     * @return string
     */
    public function domain($domain = null)
    {
        if (!is_null($domain)) {
            $this->domain = $domain;
            return $this;
        } elseif (!$this->domain) {
            $this->domain = $this->scheme() . '://' . $this->host();
        }
        return $this->domain;
    }

    /**
     * 获取当前完整URL 包括QUERY_STRING
     * @access public
     * @return string
     */
    public function url()
    {
        if (!$this->url) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $this->url = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
                $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            } else {
                $this->url = '';
            }
        }
        return  $this->url;
    }


    /**
     * 获取当前执行的文件 SCRIPT_NAME
     * @access public
     * @return string
     */
    public function baseFile()
    {
        if (!$this->baseFile) {
            $url = '';
            $script_name = basename($_SERVER['SCRIPT_FILENAME']);
            if (basename($_SERVER['SCRIPT_NAME']) === $script_name) {
                $url = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $script_name) {
                $url = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $script_name) {
                $url = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $script_name)) !== false) {
                $url = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
            } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
                $url = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
            }
            $this->baseFile = $url;
        }
        return $this->baseFile;
    }




    /**
     * 当前URL地址中的scheme参数
     * @access public
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }
    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl()
    {
        $server = array_merge($_SERVER, $this->server);
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
            return true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
            return true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
            return true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前包含协议的域名
     * @access public
     * @return string
     */
    public function host(){
        return self::server('HTTP_HOST');
    }



    /**
     * 获取当前请求URL的PATH_INFO信息（含URL后缀）
     * @access public
     * @return string
     */
    public function pathInfo()
    {
        $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) ?
            substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER['REQUEST_URI'];
        return self::server('PATH_INFO');
    }


    /**
     * 获取当前请求URL的PATH_INFO信息(不含URL后缀)
     * @access public
     * @return string
     */
    public function path()
    {
        return self::server('REDIRECT_URL');
    }

    public function param($name,$default=null,$filter=null){
        $value=$this->get($name);
        if(!isset($value)){
            $value=$this->post($name);
        }
        if(!isset($value)){
            $value=$this->put($name);
        }
        return $this->value($value,$default,$filter);
    }

    /**
     * 当前URL的访问后缀
     * @access public
     * @return string
     */
    public function ext()
    {
        return pathinfo($this->pathInfo(), PATHINFO_EXTENSION);
    }

    /**
     * 获取当前请求的时间
     * @access public
     * @param bool $float 是否使用浮点类型
     * @return integer|float
     */
    public function time($float = false)
    {
        return $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
    }

    /**
     * 字段过滤
     * @param  array $value
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    private function value($value,$default = null,$filter = null){
        if(is_array($value)){
            $data=array();
            foreach ($value as $key=>$val) {
                 $data[$key]=$this->valueFilter->filter($val);
            }
            return $data;
        }
        if(!isset($value)){
            $value=$default;
        }
        $value=$this->valueFilter->filter($value,$filter);
        return $value;
    }

    public function file($name){
        return  File::fromRequest($name);
    }


    /**
     * 获取session
     * @return Session
     */
    public function session(){
        return Ioc::get(Session::class);

    }



}