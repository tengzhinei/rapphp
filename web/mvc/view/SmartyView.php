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
        $this->smarty->setTemplateDir('.' . DIRECTORY_SEPARATOR.'tpl');
        $this->smarty
            ->setCompileDir('.' . DIRECTORY_SEPARATOR . 'runtime/templates_x' . DIRECTORY_SEPARATOR);
    }
    public function assign($array){
        $this->smarty ->assign($array);
    }

    public function fetch($tpl){
        
      return  $this->smarty->fetch($tpl);
    }

}