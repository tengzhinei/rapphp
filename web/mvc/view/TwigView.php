<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/29
 * Time: 下午10:56
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc\view;


class TwigView implements View {

    private $data;

    public function config($config){

    }

    public function assign($array) {
        $this->data=$array;
    }

    public function fetch($tpl) {
        $loader = new \Twig_Loader_Filesystem(ROOT_PATH);
        $twig = new \Twig_Environment($loader, array(
            'cache' => RUNTIME.'template',
        ));
        return $twig->render($tpl.'.html', $this->data);
    }
}