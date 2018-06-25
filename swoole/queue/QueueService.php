<?php
namespace rap\swoole\queue;
use rap\cache\Cache;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午3:02
 */
class QueueService{

    const task_queue         = 'swoole_task_queue';
    const task_time_queue    = 'swoole_task_time_queue';
    const swoole_task_incr   = 'swoole_task_incr';
    /**
     *加入任务
     */
    public function addTask($task){
        $redis=Cache::redis();
        $task_id= md5('gg'.$redis->incr('swoole_task_incr').'df');
        $redis->hSet(self::task_queue,$task_id,json_encode($task));
        $after=$task['time']-time();
        $after=$after*1000;
        if($after<=0){
            $after=10;
        }
        $this->addTaskToQueue($after,$task_id);
        return $task_id;
    }

    /**
     * 任务加入队列
     * @param $after
     * @param $task_id
     */
    public function addTaskToQueue($after,$task_id){
       $timer_id=swoole_timer_after($after,function() use($task_id){
            $redis=Cache::redis();
            $task=$redis->hGet(self::task_queue,$task_id);
            $redis->hDel(self::task_queue,$task_id);
           $redis->hDel(self::task_time_queue,$task_id);
            if(!$task)return;
            $task=json_decode($task,true);
           $url=$task['url'];
           \Requests::put($url, $task['header'], $task['params']);
        });
        $redis=Cache::redis();
        $redis->hSet(self::task_time_queue,$task_id,$timer_id);
    }

    /**
     * 取消任务
     * @param $task_id
     */
    public function cancelTask($task_id){
        $redis=Cache::redis();
        $timer_id=$redis->hGet(self::task_time_queue,$task_id);
        $redis->hDel(self::task_queue,$task_id);
        $redis->hDel(self::task_time_queue,$task_id);
        swoole_timer_clear($timer_id);
    }

    /**
     * 历史任务装入任务队列
     */
    public function start(){
        $redis=Cache::redis();
        $it = NULL;
        $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        $i=0;
        while($arr_keys = $redis->hScan(self::task_queue, $it,'',10)) {
            foreach($arr_keys as $task_id => $task) {
                $task=json_decode($task,true);
                $after=$task['time']-time();
                $after=$after*1000;
                if($after<=0){
                    $after=10;
                }
                $i++;
                $this->addTaskToQueue($after,$task_id);
            }
        }
        echo "task  queue start load $i task";

    }

}