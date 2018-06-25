<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/6/11
 * Time: 下午6:19
 */

namespace rap\swoole\websocket;


use rap\cache\Cache;
use rap\ioc\Ioc;

abstract class WebSocketService{


    /**
     *
     * @var WebSocketServer
     */
    public $server;



    /**
     * 通过get参数获取当前用户
     * @param $get
     * @return mixed
     */
    public abstract function tokenToUserId($get);

    /**
     * 发送消息
     * @param $user_id
     * @param $msg array
     * @return bool
     */
    public function sendToUser($user_id,array $msg){
       return $this->server->sendToUser($user_id,$msg);
    }




}