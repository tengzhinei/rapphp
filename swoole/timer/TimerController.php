<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午2:19
 */
namespace rap\swoole\timer;

use rap\web\Request;


class TimerController {

    /**
     * @var TimerService
     */
    private $queueService;

    public function _initialize(TimerService $queueService) {
        $this->queueService = $queueService;
    }

    /**
     * 添加任务
     *
     * @param Request $request
     *
     * @return array
     */
    public function add(Request $request,$secret) {


        $task = $request->put();
        $task_id = $this->queueService->addTask($task);
        return ['success' => true, 'task_id' => $task_id];
    }

    /**
     * 取消任务
     *
     * @param $task_id
     *
     * @return array
     */
    public function cancel($task_id,$secret) {
        $this->queueService->cancelTask($task_id);
        return ['success' => true];
    }


}