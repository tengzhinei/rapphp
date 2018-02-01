<?php
namespace rap\web\mvc;

use rap\ioc\Ioc;
use rap\web\HttpRequest;
use rap\web\HttpResponse;


/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:47
 */
abstract class HandlerAdapter{
    private $pattern;
    private $header;
    private $method;
    private $params;
    public abstract function viewBase();

    /**
     * 设置或获取匹配的路径规则
     * @param string $pattern
     * @return string
     */
    public function pattern($pattern){
        if($pattern){
            $this->pattern=$pattern;
        }
        return  $this->pattern;
    }

    /**
     * 设置或获取匹配的请求头
     * @param array  $header
     * @return array
     */
    public function header($header){
        if($header){
            $this->header=$header;
        }
        return  $this->header;
    }

    /**
     * 设置或获取匹配的方法
     * @param array $method
     * @return array
     */
    public function method($method){
        if($method){
            $this->method=$method;
        }
        return  $this->method;
    }

    public abstract function handle(HttpRequest $request,HttpResponse $response);

    public function addParam($key,$value){
        $this->params[$key]=$value;
    }

    /**
     * 调用方法 并绑定对象
     * @param $obj mixed 对象
     * @param $method string 方法名
     * @param $request HttpRequest 方法名
     * @return mixed
     */
    public static function invokeRequest($obj, $method,HttpRequest $request,HttpResponse $response)
    {
        $method =   new \ReflectionMethod(get_class($obj), $method);
        $args=[];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            /* @var $param \ReflectionParameter  */
            foreach ($params as $param) {
                $name  = $param->getName();
                $default=null;
                if($param->isDefaultValueAvailable()){
                    $default =  $param->getDefaultValue();
                }
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    if($className == HttpRequest::class){
                        $args[]=$request;
                    }else if($className == HttpResponse::class){
                        $args[]=$response;
                    }else{
                        $className = $class->getName();
                        $bean = method_exists($className, 'instance') ? $className::instance() : new $className();
                        $properties=$class->getProperties();
                        foreach ($properties as $property) {
                            $name=$property->getName();
                            $val= $request->param($name);
                            if(isset($val)){
                                $bean->$name=$val;
                            }
                        }
                        $args[]=$bean;
                    }
                }else{
                    $args[]=$request->param($name,$default);
                }
            }
        }
        $val= $method->invokeArgs($obj,$args);
        return $val;
    }



    public function invokeClosure(\Closure $closure,HttpRequest $request,HttpResponse $response){
        $method = new \ReflectionFunction($closure);
        $args=[];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            /* @var $param \ReflectionParameter  */
            foreach ($params as $param) {
                $name  = $param->getName();
                $default=null;
                if($param->isDefaultValueAvailable()){
                    $default =  $param->getDefaultValue();
                }
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    if($className == HttpRequest::class){
                        $args[]=$request;
                    }else if($className == HttpResponse::class){
                        $args[]=$response;
                    }else{
                        $bean = method_exists($className, 'instance') ? $className::instance() : new $className();
                        $properties=$class->getProperties();
                        foreach ($properties as $property) {
                            $name=$property->getName();
                            $val= $request->param($name);
                            if(isset($val)){
                                $bean->$name=$val;
                            }
                        }
                        $args[$name]=$bean;
                    }
                }else{
                    if(key_exists($name,$this->params)){
                        $args[$name]=$this->params[$name];
                    }else{
                        $args[$name]=$request->param($name,$default);
                    }
                }
            }
        }
        $result = call_user_func_array($closure, $args);
        return $result;
    }

}