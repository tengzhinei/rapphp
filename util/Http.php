<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午11:13
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;


use Swoole\Coroutine\Http\Client;

class Http {

    private static function parseUrl($url) {
        $port=80;
        if (strpos($url, 'http://') == 0) {
            $url = str_replace('http://', '', $url);
        }elseif (strpos($url, 'https://') == 0) {
            $url = str_replace('https://', '', $url);
            $port=443;
        }
        $po = strpos($url, '/');
        if ($po) {
            $host = substr($url, 0, $po);
            $path = substr($url, $po);
        } else {
            $host = $url;
            $path = '/';
        }
        if (strpos($host, ':') == 0) {
            $hp=  explode(':',$host);
            $host=$hp[0];
            $port=$hp[1];
        }
        return [$host, $path,$port];
    }

    public static function get($url, $header = []) {
        if (IS_SWOOLE_HTTP && \Co::getuid()) {
            $hostPath = self::parseUrl($url);
            $cli = new Client($hostPath[ 0 ], $hostPath[ 2 ]);
            if ($header) {
                $cli->setHeaders($header);
            }
            $cli->get($hostPath[ 1 ]);
            $cli->headers;
            return $cli->body;
        } else {
            return \Requests::get($url, $header)->body;
        }
    }

    public static function post($url, $header = [], $data = []) {
        if (IS_SWOOLE_HTTP && \Co::getuid()) {
            $hostPath = self::parseUrl($url);
            $cli = new Client($hostPath[ 0 ], $hostPath[ 2 ]);
            if ($header) {
                $cli->setHeaders($header);
            }
            $cli->post($hostPath[ 1 ], $data);
            $cli->headers;
            return $cli->body;
        } else {
            return \Requests::post($url, $header, $data)->body;
        }

    }

    public static function put($url, $header = [], $data = [],$newco=false) {
        //在 swoole 协程环境
        if (IS_SWOOLE_HTTP && \Co::getuid()) {
            $hostPath = self::parseUrl($url);
            $cli = new Client($hostPath[ 0 ], $hostPath[ 2 ]);
            if ($header) {
                $cli->setHeaders($header);
            }
            if ($data && is_string($data)) {
                $cli->post($hostPath[ 1 ], $data);
            } else {
                $cli->post($hostPath[ 1 ], json_encode($data));
            }
            $cli->headers;
            return $cli->body;
        } else {
            return \Requests::put($url, $header, $data)->body;
        }
    }


}