<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:48
 */

namespace rap\exception;


class ErrorException extends \Exception{

    public $error;

    /**
     * ErrorException constructor.
     * @param $error
     */
    public function __construct(\Error $error){
        parent::__construct("系统服务出现错误");
        $this->error = $error;
    }


}