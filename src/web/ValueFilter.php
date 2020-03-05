<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午10:41
 */

namespace rap\web;

/**
 * Class ValueFilter
 * @package rap\web
 */
class ValueFilter
{


    /**
     * 默认直接返回
     * @param $value
     * @param $filter
     * @return mixed
     */
    public function filter($value, $filter = '')
    {
        return $value;
    }
}
