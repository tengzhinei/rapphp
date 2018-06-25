<?php
namespace rap\swoole\queue;

use rap\config\Config;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/5/23
 * Time: 下午5:34
 */
class Queue{

    /**
     * 发送异步任务
     * @param $url
     * @param array $params
     * @param int $after
     * @param array $header
     * @return int
     */
    public static function after($url,$params=[],$after=-1,$header=[]){
        $time=time()+$after;
        $queue=Config::getFileConfig()['queue'];
        $local_url=$queue['local'];
        $server_url=$queue['server'];
        $data=[
            'url'=>$local_url.$url,
            'header'=>$header,
            'method'=>'put',
            'params'=>$params,
            'time'=>$time
        ];
        $body=\Requests::put($server_url ,$header, $data)->body;
        $result=json_decode($body, true);
        if($result['success']){
            return $result['task_id'];
        }
        return 0;
    }


}