<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/8/24
 * Time: 下午1:44
 */

namespace rap\swoole\task;


interface TaskConfig{

    public function getTaskInitConfig();

    public function setTaskInit($config);

}