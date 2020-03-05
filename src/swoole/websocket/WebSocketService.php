<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/6/11
 * Time: 下午6:19
 */

namespace rap\swoole\websocket;

use rap\cache\Cache;

abstract class WebSocketService
{


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
    abstract public function tokenToUserId($get);

    abstract public function onOpen($user_id);

    /**
     * 发送消息
     * @param $user_id
     * @param $msg array
     * @return bool
     */
    public function sendToUser($user_id, array $msg)
    {
        return $this->server->sendToUser($user_id, $msg);
    }

    /**
     * 直接推送
     * @param string $fid
     * @param string $msg
     */
    public function push($fid, $msg)
    {
        if ($this->server->server->exist($fid)) {
            $this->server->server->push($fid, $msg);
        } else {
            //断开
            $this->server->server->close($fid);
            $redis = Cache::redis();
            $user_id=$redis->hGet('fid_#_user_id', $fid);
            $redis->hDel('user_id_#_fid', $user_id);
            $redis->hDel('fid_#_user_id', $fid);
            Cache::release();
        }
    }

    public function close($fid)
    {
        if ($this->server->server->exist($fid)) {
            $this->server->server->push($fid, json_encode(['msg_type'=>'error','code'=>'10011','msg'=>'用户已在其他地方登录']));
            $this->server->server->close($fid);
        }
    }
}
