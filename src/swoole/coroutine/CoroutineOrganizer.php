<?php
namespace rap\swoole\coroutine;

use rap\swoole\Context;
use \LogicException;


class CoroutineOrganizer implements ICoroutineOrganizer {

    /**
     * @var WaitGroup
     */
    private $waitGroup;

    public function goWithContext(\Closure $closure){
        $data = Context::data();
        $this->groupAdd();
        go(function()use($data,$closure){
            foreach ($data as $key=>$value) {
                Context::set($key,$value);
            }
            try{
                $closure();
            }finally{
                $this->groupDone();
                Context::release();
            }
        });
        return $this;
    }

    private function groupAdd(){
        if(!$this->waitGroup){
            return;
        }
        $this->waitGroup->add();
    }

    private function groupDone(){
        if(!$this->waitGroup){
            return;
        }
        $this->waitGroup->done();
    }

    public function go(\Closure $closure){
        $this->groupAdd();
        go(function()use($closure){
            try{
                $closure();
            }finally{
                $this->groupDone();
                Context::release();
            }
        });
        return $this;
    }

    public function group(){
       $this->waitGroup=new WaitGroup();
        return $this;
    }

    public function wait(){
        if(!$this->waitGroup){
            throw new LogicException("没有添加group组");
        }
        $this->waitGroup->wait();
    }
}