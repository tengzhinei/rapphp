<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/29
 * Time: 下午10:56
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc\view;

/**
 * 推荐的模板引擎
 */
class TwigView implements View {

    private $data;

    private $config = [
        "postfix"=>'html'
    ];

    public function config($config) {
//        $this->config[ 'cache' ] = RUNTIME . 'template';
        $this->config = array_merge($this->config, $config);
    }

    public function assign($array) {
        $this->data = $array;
    }

    public function fetch($tpl) {
        $loader = new \Twig_Loader_Filesystem(ROOT_PATH);
        $twig = new \Twig_Environment($loader, $this->config);
        return $twig->render($tpl . '.'.$this->config['postfix'], $this->data);
    }
}