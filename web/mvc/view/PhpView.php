<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/12
 * Time: 下午2:36
 */

namespace rap\web\mvc\view;


class PhpView implements View{

    private $data=[];
    public function assign($array){
        extract($array, EXTR_OVERWRITE);

    }

    public function fetch($tpl){
        ob_start();
        ob_implicit_flush(0);
        // 读取编译存储
        $this->storage->read($cacheFile, $this->data);
        // 获取并清空缓存
        $content = ob_get_clean();
        include $tpl.'php';
    }

}