<?php

namespace rap\rpc;


/**
 * 为 rpc 提供配置服务
 * 可以覆盖该类为每个方法的调用配置 请求头 重试次数 超时时间等
 * @author: 藤之内
 */
class RpcConfigProvide {


    public function header($interface, $method, $data) {
        $header = [];
        $request = request();
        if ($request) {
            $header = $request->header();
            unset($header[ 'host' ]);
            unset($header[ 'content-type' ]);
            unset($header[ 'content-length' ]);
            unset($header[ 'connection' ]);
            unset($header[ 'pragma' ]);
            unset($header[ 'cache-control' ]);
            unset($header[ 'upgrade-insecure-requests' ]);
            unset($header[ 'sec-fetch-mode' ]);
            unset($header[ 'sec-fetch-user' ]);
            unset($header[ 'accept' ]);
            unset($header[ 'sec-fetch-site' ]);
            unset($header[ 'accept-encoding' ]);
            unset($header[ 'accept-language' ]);
            $header[ 'x-session-id' ] = $request->session()->sessionId();
        }
        return $header;
    }


    /**
     * 获取重试次数
     * 默认不重试
     *
     * @param string $interface 接口
     * @param string $method    方法
     * @param array  $data      需要传递的数据
     *
     * @return int
     */
    public function retryCount($interface, $method, $data) {
        return 1;
    }


    /**
     * 获取超时时间
     * 默认 -1 按 config 配置走
     *
     * @param string $interface 接口
     * @param string $method    方法
     * @param array  $data      需要传递的数据
     *
     * @return int
     */
    public function timeout($interface, $method, $data) {
        return -1;
    }
}