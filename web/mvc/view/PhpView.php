<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/29
 * Time: 下午10:36
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc\view;


class PhpView implements View {

    public function config($config){

    }

    public function assign($array) {

    }

    public function fetch($tpl) {
        extract(['a'=>'a'], EXTR_OVERWRITE);
        ob_start();
        ob_implicit_flush(0);
        include $tpl.'.php';
        $content = ob_get_clean();
        return $content;
    }

}