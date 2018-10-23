<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午10:56
 */

namespace rap\web\mvc;


class RouterGroup extends Router{

    private $group;

    /**
     * RouterGroup constructor.
     * @param $group
     */
    public function __construct($group){
        $this->group = $group;
    }

    public function then(\Closure $closure){
        $closure($this);
    }


}