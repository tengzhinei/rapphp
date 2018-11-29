<?php
namespace rap\swoole\websocket;

use rap\aop\Event;
use rap\cache\Cache;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\swoole\pool\CoHolder;
use rap\swoole\web\SwooleRequest;
use rap\swoole\web\SwooleResponse;
use rap\web\Application;
use rap\web\mvc\RequestHolder;
use rap\util\Http;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/6/9
 * Time: 下午11:45
 */
class WebSocketServer extends Command {

    private $config = ['ip' => '0.0.0.0',
                       'port' => '9501',
                       'service' => '需要继承rap\swoole\websocket\WebSocketService',
                       'secret' => 'Nz4bYrr2paoE6YaH',
                       'worker_num' => 1];

    /**
     * @var \swoole_websocket_server
     */
    public $server;
    public $host_name;
    public $secret;

    public function _initialize() {
        $this->config = array_merge($this->config, Config::getFileConfig()[ 'websocket' ]);
    }


    /**
     * websocket启动入口
     *
     * @param $host_name
     */
    public function run($host_name) {
        $this->host_name = $host_name;
        $this->server = new \swoole_websocket_server($this->config[ 'ip' ], $this->config[ 'port' ]);
        $this->server->set(['buffer_output_size' => 32 * 1024 * 1024, //必须为数字
                            'worker_num' => $this->config[ 'worker_num' ],
                            'max_connection' => 1000000]);
        $this->server->on('workerstart', [$this, 'onWorkStart']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('request', [$this, 'onRequest']);
        $this->writeln("websocket服务启动成功");
        $this->server->start();
    }

    public function onWorkStart($server, $id) {
        /* @var $service WebSocketService */
        $service = Ioc::get($this->config[ 'service' ]);
        $service->server = $this;
        Event::trigger('onHttpWorkStart', '');
    }

    /**
     * http 入口
     *
     * @param $request
     * @param $response
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
        try {
            if ($request->server[ 'request_uri' ] == '/favicon.ico') {
                $response->end();
                return;
            }
            /* @var $application Application */
            $application = Ioc::get(Application::class);
            $rep = new SwooleResponse();
            $req = new SwooleRequest($rep);
            $req->swoole($request);
            $rep->swoole($req, $response);
            //生成 session
            $rep->session()->sessionId();
            go(function() use ($application, $req, $rep) {
                RequestHolder::setRequest($req);
                $cache = Ioc::get(CacheInterface::class);
                if ($cache instanceof RedisCache) {
                    $cache->ping();
                }
                $application->start($req, $rep);
                //释放协程里的变量和
                CoHolder::getHolder()->release();
            });
        } catch (\Exception $exception) {
            $response->end("");
            return;
        } catch (\Error $e) {
            $response->end("");
            return;
        }
    }


    /**
     * 连接开始回调
     *
     * @param $server
     * @param $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request) {
        go(function() use ($server, $request) {
            /* @var $service WebSocketService */
            $service = Ioc::get($this->config[ 'service' ]);
            $user_id = $service->tokenToUserId($request->get);
            if (!$user_id) {
                $server->push($request->fd, json_encode(['msg_type' => 'error', 'code' => '10010', 'msg' => '用户信息错误']));
                $server->close($request->fd);
                return;
            }
            //将当前用户在其他连接断掉
            $old_fid = $this->userIdToFid($user_id);
            if ($old_fid) {
                $old = explode("@", $old_fid);
                if ($old[ 0 ] == $this->host_name) {
                    $server->push($old[ 1 ], json_encode(['msg_type' => 'error',
                                                          'code' => '10011',
                                                          'msg' => '用户已在其他地方登录']));
                    $server->close($old[ 1 ]);
                } else {
                    try {
                        $data = ['fid' => $old[ 1 ],
                                 'secret' => $this->config[ 'secret' ]];
                        Http::put('http://' . $old[ 0 ] . ':' . $this->config[ 'port' ] . '/open/close', [], $data);
                    } catch (\Exception $exception) {
                    } catch (\Error $exception) {
                    }
                }
                Cache::redis()->hDel('fid_#_user_id', $old_fid);
            }
            //清除占用连接的老用户
            $old_user = $this->fidToUserId($this->host_name . "@" . $request->fd);
            Cache::redis()->hDel('user_id_#_fid', $old_user);

            //设置新用户
            Cache::redis()->hSet('user_id_#_fid', $user_id, $this->host_name . "@" . $request->fd);
            Cache::redis()->hSet('fid_#_user_id', $this->host_name . "@" . $request->fd, $user_id);
            //1s后发送未读消息
            swoole_timer_after(1000, function() use ($user_id) {
                /* @var $service WebSocketService */
                $service = Ioc::get($this->config[ 'service' ]);
                $service->onOpen($user_id);
            });
        });

    }

    /**
     * 接受消息回调
     *
     * @param  $server
     * @param  $frame
     */
    public function onMessage($server, $frame) {
        go(function()use($server, $frame){
            $data = json_decode($frame->data, true);
            $method = $data[ 'method' ];
            unset($data[ 'method' ]);
            $user_id = $this->fidToUserId($frame->fd);
            /* @var $service WebSocketService */
            $service = Ioc::get($this->config[ 'service' ]);
            $service->$method($user_id, $data);
        });
    }


    /**
     * 关闭回调
     *
     * @param $ser
     * @param $fd
     */
    public function onClose($ser, $fd) {
        go(function()use($ser, $fd){
            $user_id = $this->fidToUserId($fd);
            $fid = $this->userIdToFid($user_id);
            Cache::redis()->hDel('user_id_#_fid', $user_id);
            Cache::redis()->hDel('fid_#_user_id', $fid);
        });

    }


    /**
     * 用户转发连接
     *
     * @param $uid
     *
     * @return string
     */
    public function userIdToFid($uid) {
        return Cache::redis()->hGet('user_id_#_fid', $uid);
    }

    /**
     * 连接转化用户
     *
     * @param $fid
     *
     * @return string
     */
    public function fidToUserId($fid) {
        return Cache::redis()->hGet('fid_#_user_id', $this->host_name . '@' . $fid);
    }


    /**
     * 发送消息给用户
     *
     * @param       $user_id
     * @param array $msg
     *
     * @return bool|null
     */
    public function sendToUser($user_id, array $msg) {
        //检查redis ping 防止掉线
        $fid = $this->userIdToFid($user_id);
        if (!$fid) {
            return null;
        }
        $fid_server = explode('@', $fid);
        $host_name = $fid_server[ 0 ];
        $fid = $fid_server[ 1 ];
        if ($host_name == $this->host_name) {
            if (!$this->server->exist($fid)) {
                //断开
                $this->server->close($fid);
                Cache::redis()->hDel('user_id_#_fid', $user_id);
                Cache::redis()->hDel('fid_#_user_id', $fid);
                return false;
            }
            $this->server->push($fid, json_encode($msg));
        } else {
            try {
                $data = ['fid' => $fid,
                         'msg' => json_encode($msg),
                         'secret' => $this->config[ 'secret' ]];
                Http::put('http://' . $host_name . ':' . $this->config[ 'port' ] . '/open/push', [], $data);
                //通过集群中的其他服务器推送
            } catch (\Exception $exception) {
            } catch (\Error $exception) {
            }
        }
        return null;
    }

    public function configure() {
        $this->name('websocket')->asName("swoole websocket服务")->des("启动swoole websocket服务 需要安装 swoole 拓展");
    }


}