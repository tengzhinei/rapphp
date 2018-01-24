<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/22
 * Time: 下午4:26
 */

namespace rap\db;

/**
 *
 * Class Comment
 * @package rap\db
 */
trait Comment{

    private $comment="";

    /**
     *  添加注释
     * @param string $comment 注释
     * @return $this
     */
    public function comment($comment=""){
        $this->comment=!empty($comment) ? ' /* ' . $comment . ' */' : '';
        return $this;
    }
}