<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/8
 * Time: 下午4:17
 */

namespace rap\ioc;


class BeanDefine{
    /**
     * @var string 类名
     */
    public $ClassName;

    /**
     * @var \Closure
     */
    public $closure;

    /**
     * BeanDefine constructor.
     * @param string $ClassName
     * @param \Closure $closure
     */
    public function __construct($ClassName, \Closure $closure=null){
        $this->ClassName = $ClassName;
        $this->closure = $closure;
    }


}