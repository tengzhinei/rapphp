<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午10:19
 */

namespace rap\web\mvc;


use rap\web\Request;

class RouterPattern{

    private $method=[];
    private $header=[];
    private $url;
    private $ext=[];
    private $extDeny=[];
    private $https=false;
    /**
     * @var HandlerAdapter
     */
    private $handlerAdapter;

    /**
     * RouterPattern constructor.
     * @param $url
     */
    public function __construct($url){
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param $pathArray
     * @return null|HandlerAdapter
     */
    public function match(Request $request, $pathArray){

        $urlArray=explode('/',$this->url);
        if(count($urlArray)!=count($pathArray))return null;
        /**
         * 检查路径
         */
        $paramsKey=[];
        $c=count($urlArray);
        for ($i=0;$i<$c;$i++){
            $url = $urlArray[$i];
            if(strpos($url,':')===0){
                $paramsKey[substr($url,1)]=$pathArray[$i];
                continue;
            }
            $path = $pathArray[$i];
            if($path!=$url)return null;
        }
        //检查方法
        if(count($this->method)>0&& !in_array($request->method(),$this->method))return null;
        //检查请求头
        if(count($this->header)>0){
            foreach ($this->header as $head=>$value) {
               if($request->header($head)!=$value)return null;
            }
        }

        //后缀检查
        if(count($this->ext)>0&&!in_array($request->ext(),$this->ext))return null;
        if(count($this->extDeny)>0&&in_array($request->ext(),$this->extDeny))return null;
        if($this->https&&!$request->isSsl())return null;

        foreach ($paramsKey as $param=>$value) {
            $this->handlerAdapter->addParam($param,$value);
        }



        return $this->handlerAdapter;
    }

    public function get(){
        $this->method[]='GET';
        return $this;
    }

    /**
     * 绑定控制器
     * @param $ctr
     * @param $method
     */
    public function bindCtr($ctr,$method){
        $this->handlerAdapter=new ControllerHandlerAdapter($ctr,$method);

    }

    /**
     * 绑定方法
     * @param \Closure $closure
     */
    public function toDo(\Closure $closure){
        $this->handlerAdapter=new ClosureHandlerAdapter($closure);
    }

    public function ext($ext){
        $this->ext[]=$ext;
        return $this;
    }

    public function extDeny($ext){
        $this->extDeny[]=$ext;
        return $this;
    }

    public function https($https=true){
        $this->https=$https;
        return $this;
    }
    public function post(){
        $this->method[]="POST";
        return $this;
    }
    public function put(){
        $this->method[]="PUT";
        return $this;
    }

    public function delete(){
        $this->method[]="DELETE";
        return $this;
    }
    public function patch(){
        $this->method[]="PATCH";
        return $this;
    }

    public function header($key,$value){
        $this->header[$key]=$value;
        return $this;
    }

    public function cache(){
        return $this;
    }

    public function int($key){
        return $this;
    }

    public function letters($key){
        return $this;
    }

    public function pattern($key,$pattern){
        return $this;
    }


}