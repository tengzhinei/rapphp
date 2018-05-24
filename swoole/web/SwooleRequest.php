<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:21
 */

namespace rap\swoole\web;


use rap\storage\File;
use rap\web\Request;

class SwooleRequest extends Request{

    private $swooleRequest;

    public function swoole($request){
        $this->swooleRequest=$request;
    }


    public function method(){
        return $this->swooleRequest->server['request_method'];
    }



    public function get($name = '', $default = null, $filter = null){
        $value=$this->swooleRequest->get;
        if($name){
            $value=$value[$name];
        }
        return $this->value($value,$default,$filter);
    }

    /**
     * 获取POST参数
     * @param string $name
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public function post($name = '', $default = null, $filter = null){
        $value=$this->swooleRequest->post;
        if($name){
            $value=$value[$name];
        }
        return $this->value($value,$default,$filter);
    }

    public function body(){
        return $this->swooleRequest->rawContent();
    }

    /**
     * 获取$_SERVER的信息
     * @param $name
     * @param null $default
     * @param null $filter
     * @return mixed|null
     */
    public function server($name="", $default = null, $filter = null)
    {
        if (empty($this->server)) {
          $this->server = $this->swooleRequest->server;
        }
        if(!$name){
            $value=$this->server;
        }else{
            $value=$this->server[$name];
        }
        return $this->value($value,$default,$filter);
    }


    public function header($name = '', $default = null, $filter = null)
    {
        if (empty($this->header)) {
            $header= array_change_key_case($this->swooleRequest->header);
            $this->header = array_change_key_case($header);
        }
        $name = str_replace('_', '-', strtolower($name));
        if(!$name){
            $value=$this->header;
        }else{
            $value=$this->header[$name];
        }
        return $this->value($value,$default,$filter);
    }



    public function url()
    {
        if (!$this->url) {
            $this->url=$this->swooleRequest->server['request_uri'];
        }
        return  $this->url;
    }



    public function time($float = false){
        return  $float ?$this->swooleRequest->server['request_time']:$this->swooleRequest->server['request_time_float'];
    }

    public function file($name){
        $upload_file=$this->swooleRequest->files[$name];
        return  File::fromRequest($upload_file);
    }


    public function cookie($name="",$default=''){
        if(!$name){
            return $this->swooleRequest->cookie;
        }
        $value= $this->swooleRequest->cookie[$name];
        if(!$value){
            $value=$default;
        }
        return $value;
    }




}