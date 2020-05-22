<?php


namespace rap\util\http\hmac;


use rap\util\http\Http;
use rap\util\http\HttpClient;
use rap\util\http\HttpResponse;
use Unirest\Request\Body;

/**
 * 支持 Hmac 认证的 http请求服务
 * @author: 藤之内
 */
class HmacHttp implements HttpClient {

    /**
     * 认证信息 header 位置
     * 支持 Authorization和 Proxy-Authorization
     * @var string
     */
    private $authorizationName = "Authorization";

    /**
     * 需要签名的请求头
     * date          会自动生成 GTM 格式的时间戳
     * digest        会对 body 进行签名
     * request-line  会对请求方式和请求链接签名
     * host          对 host进行签名
     * 其他的按自己实际情况进行添加
     * @var array
     */
    private $sign_header = ['date', 'digest', 'request-line', 'host'];


    /**
     * 签名算法
     * 支持sha1 sha256 等
     * @var string
     */
    private $sign_algo = "sha256";


    /**
     * AaccessKey
     * @var string
     */
    private $accessKey = '';

    /**
     * SecretKey
     * @var string
     */
    private $secretKey = '';


    /**
     * 设置认证的请求头 默认Authorization
     *
     * @param string $authorizationName
     */
    public function setAuthorizationName($authorizationName) {
        $this->authorizationName = $authorizationName;
    }

    /**
     * 设置需要签名的请求头 默认 ['date', 'digest', 'request-line', 'host']
     *
     * @param array $sign_header
     */
    public function setSignHeader($sign_header) {
        $this->sign_header = $sign_header;
    }

    /**
     * 设置签名算法
     *
     * @param string $sign_algo 签名算法 默认sha256
     */
    public function setSignAlgo($sign_algo) {
        $this->sign_algo = $sign_algo;
    }

    /**
     * 设置密钥对
     *
     * @param string $accessKey AccessKey
     * @param string $secretKey AecretKey
     */
    public function setAccessSecretKey($accessKey, $secretKey) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    /**
     * get 请求
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param int|float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function get($url, $header = [], $timeout = 5) {
        $header = $this->hmacHeader("GET", $url, $header);
        $response = Http::get($url, $header, $timeout);
        return $this->responseCheck($response);
    }

    /**
     * post请求
     * 以 form 表单形式提交
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param int|float  $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function form($url, $header = [], $data = [], $timeout = 5) {
        $body = Body::Form($data);
        $header = $this->hmacHeader("POST", $url, $header, $body);
        $response = Http::post($url, $header, $body, $timeout);
        return $this->responseCheck($response);
    }


    /**
     * post 请求
     * 如果 data 不是字符串, 将会 json_encode
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param int|float        $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function post($url, $header = [], $data = [], $timeout = 5) {
        $header = $this->hmacHeader("POST", $url, $header, $data);
        $response = Http::post($url, $header, $data, $timeout);
        return $this->responseCheck($response);
    }
    /**
     * put请求
     * 如果 data 不是字符串, 将会 json_encode
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param int|float        $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function put($url, $header = [], $data = [], $timeout = 5) {
        $header = $this->hmacHeader("PUT", $url, $header, $data);
        $response = Http::put($url, $header, $data, $timeout);
        return $this->responseCheck($response);
    }

    /**
     * 文件上传
     *
     * @param string $url     路径
     * @param array  $header  请求头
     * @param array  $data    数据
     * @param array  $files   文件
     * @param int|float    $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function upload($url, $header = [], $data = [], $files = [], $timeout = 60) {
        $header = $this->hmacHeader("POST", $url, $header, $data);
        $response = Http::upload($url, $header, $data, $timeout);
        return $this->responseCheck($response);
    }

    /**
     * delete 删除请求
     *
     * @param string       $url     路径
     * @param array        $header  请求头
     * @param array|string $data    数据
     * @param int|float        $timeout 过期时间
     *
     * @return HttpResponse
     */
    public function delete($url, $header = [], $data = [], $timeout = 5) {
        $header = $this->hmacHeader("DELETE", $url, $header, $data);
        $response = Http::delete($url, $header, $data, $timeout);
        return $this->responseCheck($response);
    }


    /**
     * 处理hmac需要传递的请求头
     *
     * @param string $method 方法
     * @param string $url    路径
     * @param array  $header 原始请求头
     * @param string $body   内容
     *
     * @return array
     */
    private function hmacHeader($method, $url, $header = [], $body = '') {
        if (!$this->accessKey || !$this->secretKey) {
            throw new UnauthorizedException('accessKey and secretKey can not null');
        }
        if ($body && !is_string($body)) {
            $body = json_encode($body);
        }
        $hostPath = $this->parseUrl($url);
        $date = gmdate("D, d M Y H:i:s T");
        $header[ 'date' ] = $date;
        if (in_array('digest', $this->sign_header)) {
            $base64_digest = base64_encode(hash('sha256', $body, true));
            $header[ 'digest' ] = "SHA-256=$base64_digest";
        }
        $message = '';
        foreach ($this->sign_header as $key) {
            $key = strtolower($key);
            if ($message) {
                $message .= "\n";
            }
            if ($key == 'request-line') {
                $message .= strtoupper($method) . " " . $hostPath[ 1 ] . " HTTP/1.1";
                continue;
            }
            $value = $header[ $key ];
            if ($key == 'host') {
                $value = in_array('host', $header) ? $header[ 'host' ] : $hostPath[ 0 ];
            }
            $message .= $key . ': ' . $value;
        }
        $sign = base64_encode(hash_hmac($this->sign_algo, $message, $this->secretKey, true));
        $sign_header = implode(' ', $this->sign_header);
        $header[ $this->authorizationName ] = 'hmac  username="' . $this->accessKey . '", algorithm="hmac-' . $this->sign_algo . '", headers="' . $sign_header . '", signature="' . $sign . '"';
        return $header;
    }

    /**
     * 处理url,返回 host path 和端口数组
     *
     * @param string $url
     *
     * @return array
     */
    private function parseUrl($url) {
        $port = 80;
        if (strpos($url, 'http://') === 0) {
            $url = str_replace('http://', '', $url);
        } elseif (strpos($url, 'https://') === 0) {
            $url = str_replace('https://', '', $url);
            $port = 443;
        }
        $po = strpos($url, '/');
        if ($po) {
            $host = substr($url, 0, $po);
            $path = substr($url, $po);
        } else {
            $host = $url;
            $path = '/';
        }
        if (strpos($host, ':') > 0) {
            $hp = explode(':', $host);
            $host = $hp[ 0 ];
            $port = $hp[ 1 ];
        }
        return [$host, $path, $port];
    }


    /**
     * 检查结果是否有权限
     *
     * @param HttpResponse $response
     *
     * @return HttpResponse
     */
    private function responseCheck(HttpResponse $response) {
        if ($response->status_code >= 400) {
            throw new UnauthorizedException($response->json()[ 'message' ]);
        }
        return $response;
    }
}