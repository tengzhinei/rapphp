<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/29
 * Time: 下午10:56
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc\view;

use rap\config\Config;
use rap\util\Lang;

/**
 * 推荐的模板引擎
 */
class TwigView implements View {

    private $data;

    private $twig;
    private $config = ["postfix" => 'html'];

    public function config($config) {
        $this->config = array_merge($this->config, $config);
        $loader = new \Twig_Loader_Filesystem(ROOT_PATH);
        $this->twig = new \Twig_Environment($loader, $this->config);
    }

    public function assign($array) {
        $this->data = $array;
    }

    public function fetch($tpl) {
        if (!$this->twig) {
            $debug = Config::get('app')[ 'debug' ];
            $config = [];
            if (!$debug) {
                $config = ['cache' => RUNTIME . '/template'];
            }
            $this->config($config);
        }
        return $this->twig->render($tpl . '.' . $this->config[ 'postfix' ], $this->data);
    }
}