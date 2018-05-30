<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/11
 * Time: 上午11:13
 */

namespace rap\exception;


/**
 * 希望返回到页面的异常
 * Class MsgException
 * @package rap\exception
 */
class MsgException extends \Exception{

    public $data;

    public function __construct($message, $code=0, $data  = null) {
        parent::__construct($message, $code );
        $this->data=$data;
        
    }
}