<?php


namespace rap\rpc\client;

use rap\ioc\scope\RequestScope;

/**
 * 可以为Rpc添加请求头
 * @author: 藤之内
 */
class RpcHeader implements RequestScope
{

    private $headers = [];




    /**
     * 添加请求头
     *
     * @param string $key key
     * @param string $value 内容
     */
    public function add($key, $value)
    {
        if ($key === null || $value === null) {
            return;
        }
        $this->headers[ $key ] = $value;
    }


    /**
     * 获取需要向后传递的请求头
     * @return array
     */
    public function header()
    {
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
        return array_merge($header, $this->headers);
    }


}
