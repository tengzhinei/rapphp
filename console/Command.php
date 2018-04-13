<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/5
 * Time: 下午10:18
 */

namespace rap\console;


abstract class Command{
    var $name="";
    var $asName="";
    var $des="";
    var $params=[];

    public function name($name=""){
        if(!$name)return $this->name;
        $this->name=$name;
        return $this;
    }


    public function asName($name){
        $this->asName=$name;
        return $this;
    }
    public function des($des){
        $this->des=$des;
        return $this;
    }

    public function param($name,$opt,$des,$default){
        $param=[
            'name'=>$name,
            'opt'=>$opt,
            'des'=>$des,
            'default'=>$default
        ];
        $this->params[]=$param;
        return $this;
    }

    public abstract function configure();

    public function help(){
        $this->writeln("");
        $this->writeln($this->name."  ".$this->asName);
        $this->writeln("参数说明");
        foreach ($this->params as $param) {
            $this->writeln("     -".$param['name'].' '.$param['des'].' '.($param['opt']?'可选':'必选').($param['default']?(' 默认:'.$param['default']):''));
        }
        $this->writeln("描述");
        $this->writeln($this->des);
        $this->writeln("");
    }


    protected function writeln($msg){
        echo "  ".$msg;
        echo "\n";
    }

}