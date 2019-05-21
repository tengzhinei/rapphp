<?php
namespace rap\web;

use rap\config\Config;
use rap\ioc\Ioc;
use rap\session\Session;
use rap\storage\File;
use rap\swoole\CoContext;
use rap\swoole\Context;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:33
 */
class Request {


    /**
     * @var array
     */
    protected $server;

    protected $domain;

    protected $url;

    protected $put;

    protected $header;

    /**
     * @var Response
     */
    public $response;

    /**
     * HttpRequest constructor.
     *
     * @param $response
     */
    public function __construct($response) {
        $this->response = $response;
        $this->response->setRequest($this);
    }


    /**
     * 当前的请求类型
     * @access public
     * @return string
     */
    public function method() {
        return $_SERVER[ 'REQUEST_METHOD' ];
    }


    /**
     * 是否为PUT请求
     * @access public
     * @return bool
     */
    public function isPut() {
        return $this->method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @access public
     * @return bool
     */
    public function isDelete() {
        return $this->method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @access public
     * @return bool
     */
    public function isHead() {
        return $this->method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @access public
     * @return bool
     */
    public function isPatch() {
        return $this->method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @access public
     * @return bool
     */
    public function isOptions() {
        return $this->method() == 'OPTIONS';
    }

    /**
     * 获取GET参数
     * @access public
     *
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     *
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = null) {
        $value = $_GET;
        if ($name) {
            $value = $value[ $name ];
        }
        return $this->value($value, $default, $filter);
    }

    /**
     * 获取POST参数
     *
     * @param string $name
     * @param null   $default
     * @param null   $filter
     *
     * @return mixed|null
     */
    public function post($name = '', $default = null, $filter = null) {
        $value = $_POST;
        if ($name) {
            $value = $value[ $name ];
        }
        return $this->value($value, $default, $filter);
    }


    /**
     * 获取PUT参数
     *
     * @param string $name
     * @param null   $default
     * @param null   $filter
     *
     * @return mixed|null
     */
    public function put($name = '', $default = null, $filter = null) {
        if (is_null($this->put)) {
            $content = $this->body();
            if (strpos($content, '":')) {
                $this->put = json_decode($content, true);
            } else {
                parse_str($content, $this->put);
            }
        }
        if (!$name) {
            $value = $this->put;
        } else {
            $value = $this->put[ $name ];
        }
        return $this->value($value, $default, $filter);
    }

    /**
     * 获取请求body
     * @return string
     */
    public function body() {
        return file_get_contents('php://input');
    }

    /**
     * 获取DELETE参数
     * @access public
     *
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     *
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = null) {
        return $this->put($name, $default, $filter);
    }

    /**
     * 获取PATCH参数
     * @access public
     *
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     *
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = null) {
        return $this->put($name, $default, $filter);
    }


    /**
     * 获取$_SERVER的信息
     *
     * @param      $name
     * @param null $default
     * @param null $filter
     *
     * @return mixed|null
     */
    public function server($name = "", $default = null, $filter = null) {
        if (empty($this->server)) {
            $this->server = array_change_key_case($_SERVER);
        }
        if (!$name) {
            $value = $this->server;
        } else {
            $value = $this->server[ $name ];
        }
        return $this->value($value, $default, $filter);
    }

    /**
     * 设置或者获取当前的Header
     * @access public
     *
     * @param string|array $name    header名称
     * @param string       $default 默认值
     * @param string       $filter  过滤器
     *
     * @return string
     */
    public function header($name = '', $default = null, $filter = null) {
        if (empty($this->header)) {
            $header = [];
            if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
                $header = $result;
            } else {
                $server = $this->server ? : $_SERVER;
                foreach ($server as $key => $val) {
                    if (0 === strpos($key, 'HTTP_')) {
                        $key = str_replace('_', '-', strtolower(substr($key, 5)));
                        $header[ $key ] = $val;
                    }
                }
                if (isset($server[ 'CONTENT_TYPE' ])) {
                    $header[ 'content-type' ] = $server[ 'CONTENT_TYPE' ];
                }
                if (isset($server[ 'CONTENT_LENGTH' ])) {
                    $header[ 'content-length' ] = $server[ 'CONTENT_LENGTH' ];
                }
            }
            $this->header = array_change_key_case($header);
        }
        $name = str_replace('_', '-', strtolower($name));
        if (!$name) {
            $value = $this->header;
        } else {
            $value = $this->header[ $name ];
        }
        return $this->value($value, $default, $filter);
    }


    /**
     * 获取当前包含协议的域名
     * @access public
     *
     * @param string $domain 域名
     *
     * @return string
     */
    public function domain($domain = null) {
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
    public function url() {
        if (!$this->url) {

            if (isset($_SERVER[ 'HTTP_X_REWRITE_URL' ])) {
                $this->url = $_SERVER[ 'HTTP_X_REWRITE_URL' ];
            } elseif (isset($_SERVER[ 'REQUEST_URI' ])) {
                $this->url = $_SERVER[ 'REQUEST_URI' ];
            } elseif (isset($_SERVER[ 'ORIG_PATH_INFO' ])) {
                $this->url = $_SERVER[ 'ORIG_PATH_INFO' ] . (!empty($_SERVER[ 'QUERY_STRING' ]) ? '?' . $_SERVER[ 'QUERY_STRING' ] : '');
            } else {
                $this->url = '';
            }

        }

        return $this->url;
    }


    /**
     * 当前URL地址中的scheme参数
     * @access public
     * @return string
     */
    public function scheme() {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl() {
        if ($this->header('x-forwarded-proto') === 'https') {
            return true;
        }
        if (strpos($this->server('server_protocol'), 'HTTPS') === 0) {
            return true;
        }
        if ($this->server('https') === 1 || $this->server('https') === 'on') {
            return true;
        }
        if ($this->server('request_scheme') === 'https') {
            return true;
        }
        return false;
    }

    protected $host;

    /**
     * @param string $host
     *
     * @return mixed|null|string
     */
    public function host($host = '') {
        if ($host) {
            $this->host = $host;
            return null;
        }
        $this->host = $this->header('x-forwarded-host');
        if (!$this->host) {
            $this->host = $this->header('host');
        }
        return $this->host;
    }


    /**
     * 获取当前请求URL的PATH_INFO信息（含URL后缀）
     * @access public
     * @return string
     */
    public function pathInfo() {
        $url = $this->url();
        $index = strpos($url, "?");
        if ($index) {
            $url = substr($url, 0, $index);
        }

        return $url;
    }

    public function routerPath() {
        $path = $this->path();
        $base = Config::get('app', 'url_base');
        if (strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        if ($path == '') {
            $path = '/';
        }
        return $path;
    }


    /**
     * 获取当前请求URL的PATH_INFO信息(不含URL后缀)
     * @access public
     * @return string
     */
    public function path() {
        $path = $this->pathInfo();
        $index = strpos($path, ".");
        if ($index) {
            $path = substr($path, 0, $index);
        }
        return $path;
    }

    public function param($name, $default = null, $filter = null) {
        $value = $this->get($name);
        if (!isset($value)) {
            $value = $this->post($name);
        }
        if (!isset($value)) {
            $value = $this->put($name);
        }
        if ($value === 'false') {
            $value = false;
        }
        if ($value === 'true') {
            $value = true;
        }
        return $this->value($value, $default, $filter);
    }

    /**
     * 当前URL的访问后缀
     * @access public
     * @return string
     */
    public function ext() {
        return pathinfo($this->pathInfo(), PATHINFO_EXTENSION);
    }

    /**
     * 获取当前请求的时间
     * @access public
     *
     * @param bool $float 是否使用浮点类型
     *
     * @return integer|float
     */
    public function time($float = false) {
        return $float ? $_SERVER[ 'REQUEST_TIME_FLOAT' ] : $_SERVER[ 'REQUEST_TIME' ];
    }


    public function file($name) {
        $upload_file = $_FILES[ "$name" ];
        return File::fromRequest($upload_file);
    }

    public function files($name) {
        $upload_file = $_FILES[ "$name" ];
        $files = [];
        foreach ($upload_file[ 'name' ] as $index => $name) {
            $error = $upload_file[ 'error' ][ $index ];
            $size = $upload_file[ 'size' ][ $index ];
            $type = $upload_file[ 'type' ][ $index ];
            $tmp_name = $upload_file[ 'tmp_name' ][ $index ];
            $files[] = File::fromRequest(['name' => $name,
                                          'tmp_name' => $tmp_name,
                                          'type' => $type,
                                          'error' => $error,
                                          'size' => $size]);
        }
        return $files;
    }

    /**
     * @var ValueFilter
     */
    private $valueFilter;

    /**
     * 字段过滤
     *
     * @param  array $value
     * @param null   $default
     * @param null   $filter
     *
     * @return mixed|null
     */
    protected function value($value, $default = null, $filter = null) {
        if ($this->valueFilter == null) {
            $this->valueFilter = Ioc::get(ValueFilter::class);
        }
        if (is_array($value)) {
            $data = array();
            foreach ($value as $key => $val) {
                $data[ $key ] = $this->valueFilter->filter($val);
            }
            return $data;
        }
        if (!isset($value)) {
            $value = $default;
        }
        $value = $this->valueFilter->filter($value, $filter);
        return $value;
    }

    public function cookie($name = "", $default = '') {
        if (!$name) {
            return $_COOKIE;
        }
        $value = $_COOKIE[ $name ];
        if (!$value) {
            $value = $default;
        }
        return $value;
    }


    public function response() {
        return $this->response;
    }

    /**
     * @return Session
     */
    public function session($key = null, $value = null) {
        if ($key) {
            if ($value) {
                return $this->response->session()->set($key, $value);
            } else {
                return $this->response->session()->get($key);
            }

        }
        return $this->response->session();
    }

    private $holders = [];

    /**
     * @param      $name
     * @param null $value
     *
     * @return mixed|null
     */
    public function holder($name = null, $value = null) {
        if ($name === null && $value === null) {
            return $this->holders[ 'default' ];
        }
        if ($value === null) {
            $value = $name;
            $name = 'default';
        }
        $this->holders[ $name ] = $value;
        return null;
    }

    public function holderGet($name = 'default') {
        return $this->holders[ $name ];
    }


    /**
     * 如果不使用 session 请使用 Context 和拦截器做用户信息
     *
     * @param null $user_id
     *
     * @return null|Session
     */
    public function userId($user_id = null) {
        if ($user_id == null) {
            return $this->session('user_id');
        }
        $this->session('user_id', $user_id);
        return null;
    }

    /**
     * 判断是否ajax
     * @return bool
     */
    public function isAjax() {
        $value = $this->server('HTTP_X_REQUESTED_WITH');
        $result = ('xmlhttprequest' == strtolower($value)) ? true : false;
        return $result;
    }

    private $search = [];

    /**
     * 设置或获取搜索
     *
     * @param null $search
     *
     * @return array|null
     */
    public function search($search = null) {
        if ($search) {
            $this->search = $search;
        }
        return $this->search;
    }

    /**
     * 获取 ip 地址
     *
     * @param string $http_remote_ip ip 所在的 server 名称 默认取的 REMOTE_ADDR
     *
     * @return mixed
     */
    public function ip($http_remote_ip = '') {
        $ip = $this->header('x-real-ip');

        if (!$ip && !$http_remote_ip) {
            $http_remote_ip = Config::get('app', 'http_remote_ip');

        }
        if ($http_remote_ip) {
            $ip = $this->header($http_remote_ip);
        }

        if (!$ip) {
            $ip = $this->server('remote_addr');
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[ 0 ];
    }

    public function isWeixin() {
        $ua = request()->header('user-agent');
        return strpos($ua, 'MicroMessenger') !== false;
    }


}