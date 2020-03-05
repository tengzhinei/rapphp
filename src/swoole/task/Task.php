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

class Task
{

    /**
     * 异步任务
     *
     * @param string $clazz
     * @param string $method
     * @param array  $params
     * @param int    $task_id
     */
    public static function deliver($clazz, $method = "run", $params = null, $task_id = -1)
    {
        $app = Ioc::get(Application::class);
        /* @var $deliver TaskConfig */
        $deliver = Ioc::get(TaskConfig::class);
        $bean = ['clazz' => $clazz,
                 'method' => $method,
                 'params' => $params,
                 'config' => $deliver->getTaskInitConfig()];
        if (IS_SWOOLE) {
            $app->server->task($bean, $task_id);
        }
    }
}
