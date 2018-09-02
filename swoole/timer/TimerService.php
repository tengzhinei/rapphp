<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午3:02
 */

namespace rap\swoole\timer;

use rap\cache\Cache;

class TimerService {

    const TIMER_QUEUE      = 'swoole_timer_queue';
    const TIMER_TIME_QUEUE = 'swoole_timer_time_queue';
    const SWOOLE_TASK_INCR = 'swoole_timer_incr';
    const SWOOLE_TASK_WAIT = 'swoole_timer_wait_queue';

    /**
     * 加入任务
     *
     * @param $task
     *
     * @return string
     */
    public function addTask($task) {
        $redis = Cache::redis();
        $task_id = md5('gg' . $redis->incr('swoole_task_incr') . 'df');
        $redis->hSet(self::TIMER_QUEUE, $task_id, json_encode($task));
        $after = $task[ 'time' ] - time();
        $after = $after * 1000;
        if ($after <= 0) {
            $after = 1000;
        }
        //定时时间大于一天的
        if ($after > 86400000) {
            $redis->hSet(self::SWOOLE_TASK_WAIT, $task_id, json_encode($task));
        } else {
            $this->addTaskToQueue($after, $task_id);
        }
        return $task_id;
    }


    /**
     * 任务加入队列
     * @param $after
     * @param $task_id
     */
    public function addTaskToQueue($after, $task_id) {
        $timer_id = swoole_timer_after($after, function() use ($task_id) {
            $redis = Cache::redis();
            $task = $redis->hGet(self::TIMER_QUEUE, $task_id);
            $redis->hDel(self::TIMER_QUEUE, $task_id);
            $redis->hDel(self::TIMER_TIME_QUEUE, $task_id);
            if (!$task) {
                return;
            }
            $task = json_decode($task, true);
            $url = $task[ 'url' ];
            $timeout = $task[ 'params' ][ 'timeout' ];
            if (!$timeout) {
                $timeout = 10;
            }
            try {
                $task[ 'header' ]['from_rap_timer']=true;
                \Requests::put($url, $task[ 'header' ], $task[ 'params' ], ['timeout' => $timeout]);
            } catch (\Exception $e) {

            } catch (\Error $e) {

            }
        });
        $redis = Cache::redis();
        $redis->hSet(self::TIMER_TIME_QUEUE, $task_id, $timer_id);
    }


    /**
     * 取消任务
     *
     * @param $task_id
     */
    public function cancelTask($task_id) {
        $redis = Cache::redis();
        $timer_id = $redis->hGet(self::TIMER_TIME_QUEUE, $task_id);
        $redis->hDel(self::TIMER_QUEUE, $task_id);
        $redis->hDel(self::TIMER_TIME_QUEUE, $task_id);
        $redis->hDel(self::SWOOLE_TASK_WAIT, $task_id);
        if ($timer_id) {
            swoole_timer_clear($timer_id);
        }
    }

    /**
     * 历史任务装入任务队列
     */
    public function start() {
        $redis = Cache::redis();
        $it = NULL;
        $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        $i = 0;
        while ($arr_keys = $redis->hScan(self::TIMER_QUEUE, $it, '', 10)) {
            foreach ($arr_keys as $task_id => $task) {
                $task = json_decode($task, true);
                $after = $task[ 'time' ] - time();
                $after = $after * 1000;
                if ($after <= 0) {
                    $after = 10;
                }
                $i++;
                if ($after > 86400000) {
                    $redis->hSet(self::SWOOLE_TASK_WAIT, $task_id, json_encode($task));
                } else {
                    $this->addTaskToQueue($after, $task_id);
                }
            }
        }
        //因为延后任务只能做到一天之内的 这里定时添加任务
        swoole_timer_tick(3600000, function() {
            $redis = Cache::redis();
            $it = NULL;
            $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
            $i = 0;
            while ($arr_keys = $redis->hScan(self::SWOOLE_TASK_WAIT, $it, '', 10)) {
                foreach ($arr_keys as $task_id => $task) {
                    $task = json_decode($task, true);
                    $after = $task[ 'time' ] - time();
                    $after = $after * 1000;
                    if ($after <= 0) {
                        $after = 10;
                    }
                    $i++;
                    if ($after > 86400000) {
                        $redis->hSet(self::SWOOLE_TASK_WAIT, $task_id, json_encode($task));
                    } else {
                        $redis->hDel(self::SWOOLE_TASK_WAIT, $task_id);
                        $this->addTaskToQueue($after, $task_id);
                    }
                }
            }
        });
    }

}