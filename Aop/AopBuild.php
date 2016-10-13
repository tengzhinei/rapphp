<?php
/**
 * 南京灵衍信息科技有限公司
 * User: tengzhinei
 * Date: 16/9/14
 * Time: 下午1:36
 */

namespace rap\Aop;
use rap\Aop;

class AopBuild {
    private  $method;
    private  $rule="all";
    private  $wave;
    private  $waveType="before";
    private  $clazz;
    private  $using="handle";
    private  $call;

    private  function __construct($waveType,$clazz){
        $this->waveType=$waveType;
        $this->clazz=$clazz;
    }
    public function methods($method){
        if(is_string($method)){
            $method=array($method);
        }
        $this->rule="only";
        $this->method=$method;
        return $this;
    }
    public function methodsExcept($method){
        if(is_string($method)){
            $method=array($method);
        }
        $this->rule="except";
        $this->method=$method;
        return $this;
    }

    public function methodsStart($method){
        if(is_string($method)){
            $method=array($method);
        }
        $this->rule="start";
        $this->method=$method;
        return $this;
    }
    public function methodsContains($method){
        if(is_string($method)){
            $method=array($method);
        }
        $this->rule="contains";
        $this->method=$method;
        return $this;
    }

    public function methodsEnd($method){
        if(is_string($method)){
            $method=array($method);
        }
        $this->rule="end";
        $this->method=$method;
        return $this;
    }

    public function methodsAll(){
        $this->method=array("1___");
        return $this;
    }

    public function wave($wave){
        $this->wave=$wave;
        return $this;
    }

    public function using($using){
        $this->using=$using;
        return $this;
    }
    public function call($call){
        $this->call=$call;
        return $this;
    }


    public static function before($clazz){
        return new AopBuild("before",$clazz);
    }
    public static function after($clazz){
        return new AopBuild("after",$clazz);
    }
    public static function around($clazz){
        return new AopBuild("around",$clazz);
    }

    public function addPoint(){
        $type=$this->waveType;
        $action=array("type"=>$this->rule,"methods"=>$this->method);
        Aop::$type($this->clazz,$action,$this->wave,$this->using,$this->call);
    }

}