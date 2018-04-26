<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/6
 * Time: 下午9:27
 */

namespace rap\swoole\task;

use rap\ioc\Ioc;
use rap\web\Application;

class Task{

    /**
     * 异步任务
     * @param string $clazz
     * @param string $method
     * @param array $params
     * @param int $task_id
     */
    public function doTask( $clazz,$method="run",array $params=[],$task_id=-1){
        $app=Ioc::get(Application::class);
        $bean=[
            'clazz'=>$clazz,
            'method'=>$method,
            'params'=>$params
        ];
        if(IS_SWOOLE_HTTP){
            $app->server->task($bean,$task_id);
        }
    }

    /**
     * 延迟执行
     * @param $fun
     * @param $time
     */
    public function delay($fun,$time){
        if(IS_SWOOLE_HTTP){
            swoole_timer_after($time,$fun);
        }
    }

}