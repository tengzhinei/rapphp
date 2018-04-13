<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/12
 * Time: 下午12:20
 */

namespace rap\console\command;


use rap\aop\Aop;
use rap\console\Command;

class AopFileBuild extends Command{

    public function configure(){
        $this->name('aop')
            ->asName("生成AOP需要的文件")
            ->param("-d",true,'删除文件',false)
            ->des("会根据search去查数据库生成所有表前缀为search的record模型文件,
  生成的类文件前缀去除prefix
  生成的文件在 runtime/model
            ");
    }
    public function run($d){
        if($d){
            Aop::clear();
            $this->writeln("AOP文件已删除成功,需要时可以重新生成");
        }else{
            Aop::buildProxy();
            $this->writeln("AOP文件生成成功,文件在".RUNTIME.'aop下');
        }

    }

}