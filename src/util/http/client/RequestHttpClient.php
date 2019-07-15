<?php


namespace rap\util\http\client;


use rap\util\http\HttpClient;
use rap\util\http\HttpResponse;

class RequestHttpClient implements HttpClient {
    public function get($url, $header = [], $timeout = 0.5) {
        \Unirest\Request::timeout($timeout);
        $response = \Unirest\Request::get($url, $header);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function post($url, $header = [], $data = [], $timeout = 0.5) {
        $data = \Unirest\Request\Body::Form($data);
        \Unirest\Request::timeout($timeout);
        $response = \Unirest\Request::post($url, $header, $data);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function put($url, $header = [], $data = [], $timeout = 0.5) {
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        \Unirest\Request::timeout($timeout);
        $response = \Unirest\Request::post($url, $header, $data);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function upload($url, $header = [], $data = [], $files = [], $timeout = 5) {
        $body = \Unirest\Request\Body::Multipart($data, $files);
        \Unirest\Request::timeout($timeout);
        $response = \Unirest\Request::post($url, $header, $body);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }


}