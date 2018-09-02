<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午5:34
 */
namespace rap\swoole\timer;

use rap\config\Config;
use rap\web\Request;

/**
 * 定时器
 */
class Timer {

    /**
     * 发送异步任务
     *
     * @param string $url
     * @param array  $params
     * @param int    $after
     * @param array  $header
     *
     * @return int
     */
    public static function after($url, $params = [], $after = -1, $header = []) {
        $time = time() + $after;
        $queue = Config::getFileConfig()[ 'timer' ];
        $local_url = $queue[ 'local' ];
        $server_url = $queue[ 'server' ];
        $secret = $queue[ 'secret' ];
        $data = ['url' => $local_url . $url,
                 'header' => $header,
                 'method' => 'put',
                 'params' => $params,
                 'time' => $time];
        $body = \Requests::put($server_url . '/timer/add', ['timer_secret'=>$secret], $data)->body;
        $result = json_decode($body, true);
        if ($result[ 'success' ]) {
            return $result[ 'task_id' ];
        }
        return 0;
    }

    public function cancel($task_id) {
        $queue = Config::getFileConfig()[ 'timer' ];
        $server_url = $queue[ 'server' ];
        $secret = $queue[ 'secret' ];
        $data = ['task_id' => $task_id
                 ];
        $body = \Requests::put($server_url . '/timer/cancel', ['timer_secret' => $secret], $data)
        ->body;
        $result = json_decode($body, true);
        return $result[ 'success' ];
    }


    public static function checkSign(Request $request){
        $secret=$request->header('timer_secret');
        $queue = Config::getFileConfig()[ 'timer' ];
        if($secret != $queue[ 'secret' ]){
            exception("签名错误,你没有权限调用");
        }
    }


}