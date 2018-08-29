<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午5:34
 */
namespace rap\swoole\timer;

use rap\config\Config;

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
        $data = ['url' => $local_url . $url, 'header' => $header, 'method' => 'put', 'params' => $params, 'time' => $time];
        $body = \Requests::put($server_url, $header, $data)->body;
        $result = json_decode($body, true);
        if ($result[ 'success' ]) {
            return $result[ 'task_id' ];
        }
        return 0;
    }


}