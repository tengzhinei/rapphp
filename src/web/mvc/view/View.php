<?php
namespace rap\web\mvc\view;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/1/12
 * Time: 下午12:34
 */
interface View
{

    public function config($config);

    public function assign($array);

    public function fetch($tpl);
}
