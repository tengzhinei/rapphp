<?php


namespace rap\util\http\client;

use rap\util\http\HttpClient;
use rap\util\http\HttpResponse;
use Unirest\Request;
use Unirest\Request\Body;

class RequestHttpClient implements HttpClient
{
    public function get($url, $header = [], $timeout = 0.5)
    {
        Request::timeout($timeout);
        $response = Request::get($url, $header);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function post($url, $header = [], $data = [], $timeout = 0.5)
    {
        $data = Body::Form($data);
        Request::timeout($timeout);
        $response = Request::post($url, $header, $data);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function put($url, $header = [], $data = [], $timeout = 0.5)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        Request::timeout($timeout);
        $response = Request::post($url, $header, $data);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function upload($url, $header = [], $data = [], $files = [], $timeout = 5)
    {
        $body = Body::Multipart($data, $files);
        Request::timeout($timeout);
        $response = Request::post($url, $header, $body);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

    public function delete($url, $header = [], $data = [], $timeout = 0.5){
        Request::timeout($timeout);
        $response = Request::delete($url, $header, $data);
        return new HttpResponse($response->code, $response->headers, $response->raw_body);
    }

}
