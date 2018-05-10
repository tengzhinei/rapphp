<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:35
 */

namespace rap\swoole\web;


use rap\web\Response;

class SwooleResponse extends Response{


    private $swooleResponse;
    public function swooleResponse($response){
        $this->swooleResponse=$response;
    }


    public function send(){
        // 发送状态码
        $this->swooleResponse->status($this->code);
        $this->header['Content-Type'] = $this->contentType . '; charset=' . $this->charset;
        if (!empty($this->header)) {
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                    $this->swooleResponse->header($name,$val);
            }
        }
       // $this->swooleResponse->  gzip(1);
        $this->swooleResponse->end($this->content);
    }

    public function cookie( $key,  $value = '',  $expire = 0 ,  $path = '/',  $domain = '',  $secure = false ,  $httponly = false){
            $this->swooleResponse->cookie($key,$value,$expire,$path,$domain,$secure,$httponly);
    }


}