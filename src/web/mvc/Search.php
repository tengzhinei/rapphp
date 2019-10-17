<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/9/1
 * Time: 上午10:26
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc;


class Search {

    private $value;

    /**
     * Search __construct.
     *
     * @param $value
     */
    public function __construct($value) {
        $this->value = (int)$value;
    }


    /**
     *
     * 获取搜索的值
     * @return int
     */
    public function value(){
        return $this->value;
    }



}