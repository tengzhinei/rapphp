<?php
namespace rap\web\mvc\view;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/12
 * Time: 下午12:35
 */
class SmartyView implements View{

    private $smarty;


    public function __construct(){
        $this->smarty = new \Smarty();
      //  $this->smarty->setTemplateDir(ROOT_PATH . DS.'tpl');
        $this->smarty
            ->setTemplateDir([''])
            ->setCompileDir(RUNTIME.'templates_x' . DS);
    }

    public function config($config){

    }

    public function assign($array){
        $this->smarty ->assign($array);
    }

    public function fetch($tpl){
      return  $this->smarty->fetch($tpl.".html");
    }

}