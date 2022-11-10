<?php
namespace rap\util\http\client;

use rap\util\http\HttpClient;
use rap\util\http\HttpResponse;
use Swoole\Coroutine\Http\Client;

class CoroutineHttpClient implements HttpClient
{
    private static function parseUrl($url)
    {
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
            $host = $hp[0];
            $port = $hp[1];
        }
        return [$host, $path, $port];
    }

    public function get($url, $header = [], $timeout = 5)
    {
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        $cli->get($hostPath[1]);
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->get($cli->headers['location'],$header,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }


    public function form($url, $header = [], $data = [], $timeout = 5){
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        $cli->post($hostPath[1], $data);
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->form($cli->headers['location'],$header,$data,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }


    public function post($url, $header = [], $data = [], $timeout = 5)
    {
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        if ($data && is_string($data)) {
            $cli->post($hostPath[1], $data);
        } else {
            $cli->post($hostPath[1], json_encode($data));
        };
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->post($cli->headers['location'],$header,$data,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }




    public function put($url, $header = [], $data = [], $timeout = 5)
    {
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        if ($data && is_string($data)) {
            $cli->setData($data);
        } else {
            $cli->setData(json_encode($data));
        };
        $cli->setMethod('PUT');
        $cli->execute($hostPath[1]);
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->put($cli->headers['location'],$header,$data,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }

    public function upload($url, $header = [], $data = [], $files = [], $timeout = 60)
    {
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        foreach ($files as $file => $name) {
            $cli->addFile($file, $name);
        }
        $cli->post($hostPath[1], $data);
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->upload($cli->headers['location'],$header,$data,$files,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }

    public function delete($url, $header = [], $data = [], $timeout = 5){
        $hostPath = self::parseUrl($url);
        if (!$hostPath[0]) {
            return new HttpResponse(-1, [], '');
        }
        $cli = new Client($hostPath[0], $hostPath[2], $hostPath[2] == 443);
        $cli->set(['timeout' => $timeout, 'ssl_verify_peer'=>false]);
        if ($header) {
            $cli->setHeaders($header);
        }
        $cli->setMethod('DELETE');
        if($data){
            $cli->setData($data);
        }
        $cli->execute($hostPath[1]);
        $code = $cli->statusCode;
        $body = $cli->body;
        if ($cli->statusCode <0) {
            $code = $code = $cli->errCode;
            $body = socket_strerror($code);
        }
        if($code==301||$code==302){
            return $this->delete($cli->headers['location'],$header,$data,$timeout);
        }
        $response = new HttpResponse($code, $cli->headers, $body);
        $cli->close();
        return $response;
    }
}
