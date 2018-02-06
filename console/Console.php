<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/5
 * Time: 下午10:16
 */

namespace rap\console;


use rap\console\command\RecordBuild;
use rap\ioc\Ioc;

class Console{

    private $defaultCommand=[];


    public function _initialize(){
        $this->addConsole(RecordBuild::class);
    }

    /**
     * @param $class
     */
    public function addConsole($class){
        /* @var $command Command  */
        $command=Ioc::get($class);
        $command->configure();
         $name=$command->name();
        $this->defaultCommand[$name]=$command;
    }

    public function run($argv){
        if(count($argv)==1){
            $this->help();
            return;
        }
        array_shift($argv);
        $command=array_shift($argv);
        $params=[];
        for ($i=0;$i<count($argv);$i+=2){
            $key=$argv[$i];
            $value=$argv[$i+1];
            $params[substr($key,1)]=$value;
        }
        if($command=='-h'){
            $this->help();
            return;
        }
        /* @var $command_obj Command  */
        $command_obj=$this->defaultCommand[$command];
        if(key_exists('h',$params)){
            $command_obj->help();
            return;
        }
        $this->invoke($command_obj,$params);
    }


    public function invoke($command,$command_params){
        $method =   new \ReflectionMethod(get_class($command), 'run');
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
                if(key_exists($name,$command_params)){
                    $args[]=$command_params[$name];
                }else{
                    $args[]=$default;
                }
            }
        }
        $method->invokeArgs($command,$args);
    }

    public function help(){
        $this->writeln("欢迎使用 rap 命令行工具");
        $this->writeln("命令行结构语法  php rap 命令 参数格式(-s xxx -m ssss)");
        $this->writeln("php rap 查看所有命令");
        $this->writeln("php rap 命令 -h 查看命令的帮助");
        $this->writeln("所有命令:");
        /* @var $command Command  */
        foreach ($this->defaultCommand as $command) {
            $this->writeln($command->name().'  '.$command->asName);
        }
    }

    protected function writeln($msg){
        echo $msg;
        echo "\n";
    }
}