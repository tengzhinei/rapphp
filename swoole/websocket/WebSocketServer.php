<?php
namespace rap\swoole\websocket;
use rap\cache\Cache;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\swoole\web\SwooleRequest;
use rap\swoole\web\SwooleResponse;
use rap\web\Application;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/6/9
 * Time: 下午11:45
 */
class WebSocketServer extends Command{
    private $confog=[
        'ip'=>'0.0.0.0',
        'port'=>9501
    ];

    /**
     * @var \Redis
     */
    public $redis;
    public $server;
    /**
     * @var WebSocketService
     */
    public $service;
    public function _initialize(){
        $this->redis=Cache::redis();
        $this->service=Ioc::get( \rap\config\Config::get("websocket",'service'));
    }


    public function run(){
        $this->confog=array_merge($this->confog,Config::getFileConfig()['swoole_websocket']);
        $this->server = new \swoole_websocket_server('0.0.0.0', 9501);
        $this->server->on('open', [$this,'onOpen']);
        $this->server->on('message', [$this,'onMessage']);
        $this->server->on('close', [$this,'onClose']);
        $this->server->on('request', [$this,'onRequest'] );
        $this->writeln("websocket服务启动成功");
        $this->service->server=$this;
        $this->server->start();

    }

    public function onRequest($request, $response){
        try{
            if($request->server['request_uri']=='/favicon.ico'){
                $response->end();
                return;
            }
            $time=getMillisecond();
            /* @var $application Application  */
            $application=Ioc::get(Application::class);
            $rep=new SwooleResponse();
            $req=new SwooleRequest($rep);
            $req->swoole($request);
            $rep->swoole($req,$response);
            //redis 20s ping 一次
            static $last_redis_ping_time=0;
            if(time()-$last_redis_ping_time>20){
                $this->last_redis_ping_time=time();
                $cache=Ioc::get(CacheInterface::class);
                if($cache instanceof RedisCache){
                    $cache->ping();
                }
            }
            //生成 session
            $rep->session()->sessionId();
            $application->start($req,$rep);
        }catch (\Exception $exception){
            $response->end("");
            return;
        }
        catch(\Error $e){
            $response->end("");
            return;
        }
    }

    public function onOpen($server, $request){
        $user_id=$this->service->tokenToUserId($request->get);
        if(!$user_id){
            $server->push($request->fd,json_encode(['msg_type'=>'error','code'=>'10010','msg'=>'用户信息错误']));
            $server->close($request->fd);
            return;
        }
        $this->redis->hSet('user_id_#_fid',$user_id,$request->fd);
        $this->redis->hSet('fid_#_user_id',$request->fd,$user_id);
    }

    public function onMessage($server, $frame){
        $data=json_decode($frame->data,true);
        $method=$data['method'];
        unset($data['method']);
        $user_id=$this->fidToUserId($frame->fd);
        $this->service->$method($user_id,$data);
    }

    public function onClose($ser, $fd){
        $user_id=$this->fidToUserId($fd);
        $this->redis->hDel('user_id_#_fid',$user_id);
        $this->redis->hDel('fid_#_user_id',$fd);
    }


    public function userIdToFid($uid){
        return $this->redis->hGet('user_id_#_fid',$uid);
    }

    public function fidToUserId($fid){
        return $this->redis->hGet('fid_#_user_id',$fid);
    }

    public function sendToUser($user_id,array $msg){
        $fid= $this->userIdToFid($user_id);
        if(!$fid)return false;
        if(!$this->server->exist($fid)){
            //断开
            $this->server->close($fid);
            //fid 不存在说明掉线
            $this->redis->hDel('user_id_#_fid',$user_id);
            $this->redis->hDel('fid_#_user_id',$fid);
            return false;
        }
        return $this->server->push($fid,json_encode($msg));
    }

    public function configure(){
        $this->name('websocket')
            ->asName("swoole websocket服务")
            ->des("启动swoole websocket服务 需要安装 swoole 拓展");
    }


}