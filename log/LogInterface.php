<?php
namespace rap\log;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/3
 * Time: 下午7:32
 */
interface LogInterface{

    /**
     * 写入单条日志
     * @param $level
     * @param $message
     * @return mixed
     */
    public function writeLog($level,$message);

    /**
     * 批量写入日志
     * @param array $logs [['time'=>'时间戳','level'=>'日志等级','message'=>'错误日志']]
     */
    public function writeLogs(array $logs);


}