<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午3:41
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util\http;


class HttpResponse {

    public $status_code;
    public $headers;
    public $body;


    /**
     * Response __construct.
     *
     * @param $status_code
     * @param $headers
     * @param $body
     */
    public function __construct($status_code, $headers, $body) {
        $this->status_code = $status_code;
        $this->headers=[];
        foreach ( $headers as $k=>$v) {
            $this->headers[strtolower($k)]=$v;
        }
        $this->body = $body;
    }


    public function json() {
        return json_decode($this->body, true);
    }


}