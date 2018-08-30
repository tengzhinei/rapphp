<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/29
 * Time: 下午10:36
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc\view;

/**
 * 不使用模板引擎,直接使用 php 做显示
 */
class PhpView implements View {

    private $data;

    private $config = [
        "postfix"=>'php'
    ];

    public function config($config) {
        $this->config = array_merge($this->config, $config);
    }

    public function assign($array) {
        $this->data = $array;
    }

    public function fetch($tpl) {
        extract($this->data, EXTR_OVERWRITE);
        ob_start();
        ob_implicit_flush(0);
        include $tpl.'.'.$this->config['postfix'];
        $content = ob_get_clean();
        return $content;
    }

}