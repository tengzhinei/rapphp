<?php
namespace rap\swoole\websocket;

use rap\aop\Event;
use rap\cache\Cache;
use rap\config\Config;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\ServerEvent;
use rap\swoole\Context;
use rap\swoole\ServerWatch;
use rap\swoole\task\TaskConfig;
use rap\swoole\web\SwooleRequest;
use rap\swoole\web\SwooleResponse;
use rap\util\http\Http;
use rap\web\Application;
use rap\swoole\CoContext;
use Swoole\Runtime;

/**
 * 功能暂未完善 请勿使用 暂时只有公司内部使用
 * User: jinghao@duohuo.net
 * Date: 18/6/9
 * Time: 下午11:45
 */
class WebSocketServer extends Command
{

    private $config = ['ip' => '0.0.0.0',
                       'port' => '9501',
                       'service' => '需要继承rap\swoole\websocket\WebSocketService',
                       'secret' => 'Nz4bYrr2paoE6YaH',
                       'worker_num' => 1,
                       'task_worker_num' => 0,
                       'coroutine'=>true,
                       'max_request' => 0,
                       'task_max_request' => 0,
                       'buffer_output_size'=>32 * 1024 * 1024, //必须为数字
    ];
    /**
     * @var \swoole_websocket_server
     */
    public $server;
    public $host_name;
    public $secret;


    public function run()
    {
        $host_name='0.0.0.0';
        $ips = swoole_get_local_ip();
        if ($ips[ "eth0" ]) {
            $host_name = $ips[ "eth0" ];
        }
        $this->config = array_merge($this->config, Config::getFileConfig()[ 'websocket' ]);
        $this->host_name = $host_name;
        $this->server = new \swoole_websocket_server($this->config[ 'ip' ], $this->config[ 'port' ]);
        $service_config=$this->config;
        unset($service_config['ip']);
        unset($service_config['port']);
        unset($service_config['service']);
        unset($service_config['secret']);
        unset($service_config['coroutine']);
        $this->server->set($service_config);
        $this->server->on('workerstart', [$this, 'onWorkStart']);
        $this->server->on('workerstop', [$this, 'onWorkerStop']);
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
        $this->writeln("websocket服务启动成功". $this->host_name);
        if ($this->config[ 'coroutine' ]) {
            //mysql redis 协程化
            Runtime::enableCoroutine();
        }
        Event::trigger(ServerEvent::ON_BEFORE_SERVER_START, $this->server);
        $this->server->start();
    }

    public function onStart(\swoole_server $server)
    {
        Log::notice('swoole webSocket start: webSocket服务启动');
        $application = Ioc::get(Application::class);
        $application->server = $server;
        Event::trigger(ServerEvent::ON_SERVER_START, $server);
        if ($this->config[ 'auto_reload' ] && Config::get('app')[ 'debug' ]) {
            $this->writeln("自动加载");
            $reload = new ServerWatch();
            $reload->init($server);
        }
    }
    public function onShutdown($server)
    {
        Log::notice('swoole webSocket shutdown : webSocket服务停止');
        Event::trigger(ServerEvent::ON_SERVER_SHUTDOWN, $server);
    }


    public function onTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        if ($data[ 'fid' ]&&$data[ 'msg' ]) {
            $this->server->push($data[ 'fid' ], $data[ 'msg' ]);
        } else {
            $clazz = $data[ 'clazz' ];
            $method = $data[ 'method' ];
            $params = $data[ 'params' ];
            $config = $data[ 'config' ];
            /* @var $deliver TaskConfig */
            $deliver = Ioc::get(TaskConfig::class);
            $deliver->setTaskInit($config);
            $bean = Ioc::get($clazz);
            $method = new \ReflectionMethod($clazz, $method);
            $method->invokeArgs($bean, $params);
        }
    }

    public function onFinish()
    {
    }

    /**
     * http 入口
     *
     * @param $request
     * @param $response
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        try {
            if ($request->server['request_uri'] == '/favicon.ico') {
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
            CoContext::getContext()->setRequest($req);
            //swoole  4.2.9
            defer(function () use ($req) {
                try {
                    Event::trigger(ServerEvent::ON_REQUEST_DEFER);
                } catch (\Throwable $throwable) {
                    Log::error('http request error', ['message' => $throwable->getMessage()]);
                } catch (\Error $throwable) {
                    Log::error('http request error', ['message' => $throwable->getMessage()]);
                } finally {
                    Log::info('http request end', ['url' => $req->url(), 'session_id' => $req->session()->sessionId()]);
                    CoContext::getContext()->release();
                }
            });
            $application->start($req, $rep);
            //释放协程里的变量和
        } catch (\Exception $exception) {
            $response->end($exception->getMessage());
            $msg = str_replace("rap\\exception\\", "", get_class($exception))
                . " in " . str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
            Log::error('http request error handler ,' ,['code'=>$exception->getCode(),
                                                        'msg'=>$msg,
                                                        'trace'=>$exception->getTraceAsString()]);
            return;
        } catch (\Error $e) {
            $response->end($e->getMessage());
            $msg = str_replace("rap\\exception\\", "", get_class($e))
                . " in " . str_replace(ROOT_PATH, "", $e->getFile()) . " line " . $e->getLine();
            Log::error('http request error handler ,' ,['code'=>$e->getCode(),
                                                        'msg'=>$msg,
                                                        'trace'=>$e->getTraceAsString()]);
            return;
        }
    }

    public function onWorkStart($server, $id)
    {
        /* @var $service WebSocketService */
        $service = Ioc::get($this->config[ 'service' ]);
        $service->server = $this;
        Log::info('swoole worker start' ,['id'=>$id] );
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger(ServerEvent::ON_SERVER_WORK_START, $server, $id);
        CoContext::getContext()->release();
    }

    public function onWorkerStop(\swoole_server $server, $id)
    {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger(ServerEvent::ON_SERVER_WORKER_STOP, $server, $id);
        CoContext::getContext()->release();
    }


    /**
     * 连接开始回调
     *
     * @param $server
     * @param $request
     */
    public function onOpen(\swoole_websocket_server $server, $request)
    {
        /* @var $service WebSocketService */
        $service = Ioc::get($this->config[ 'service' ]);
        $user_id = $service->tokenToUserId($request->get);
        if (!$user_id) {
            $this->server->push($request->fd, json_encode(['msg_type' => 'error',
                                                           'code' => '10010',
                                                           'msg' => '用户信息错误']));
            $server->close($request->fd);
            return;
        } else {
            $this->server->push($request->fd, json_encode(['msg_type' => 'login_success',
                                                          'code' => '10000',
                                                          'msg' => '登录成功']));
        }
        //将当前用户在其他连接断掉
        $old_fid = $this->userIdToFid($user_id);
        if ($old_fid) {
            $old = explode("@", $old_fid);
            if ($old[ 0 ] == $this->host_name) {
                $this->server->push($old[ 1 ], json_encode(['msg_type' => 'error',
                                                           'code' => '10011',
                                                           'msg' => '用户已在其他地方登录']));
                $server->close($old[ 1 ]);
            } else {
                try {
                    //通过集群中的其他服务器推送
                    $url='http://' . $old[ 0 ] . ':' . $this->config[ 'port' ] . '/open/close';
                    Http::put($url, [], ['fid' => $old[ 1 ],
                                         'secret' => $this->config[ 'secret' ]]);
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
        Cache::release();
        //1s后发送未读消息
        if (!$request->get['no_push']) {
            $service = Ioc::get($this->config[ 'service' ]);
            $service->onOpen($user_id);
        }
        /* @var $service WebSocketService */
        Context::release();
    }

    /**
     * @param $server
     * @param $frame
     */
    public function onMessage($server, $frame)
    {
        $data = json_decode($frame->data, true);
        $method = $data[ 'method' ];
        if (!$method) {
            return;
        }
        unset($data[ 'method' ]);
        $user_id = $this->fidToUserId($frame->fd);
        /* @var $service WebSocketService */
        $service = Ioc::get($this->config[ 'service' ]);
        $service->$method($user_id, $data);
        CoContext::getContext()->release();
    }


    /**
     * 关闭回调
     *
     * @param \swoole_websocket_server $server
     * @param string                   $fd
     */
    public function onClose(\swoole_websocket_server $server, $fd)
    {
        $user_id = $this->fidToUserId($fd);
        $fid = $this->userIdToFid($user_id);
        Cache::redis()->hDel('user_id_#_fid', $user_id);
        Cache::redis()->hDel('fid_#_user_id', $fid);
        Cache::release();
        CoContext::getContext()->release();
    }


    /**
     * 用户转发连接
     *
     * @param $uid
     *
     * @return string
     */
    public function userIdToFid($uid)
    {
        $value = Cache::redis()->hGet('user_id_#_fid', $uid);
        Cache::release();
        return $value;
    }

    /**
     * 连接转化用户
     *
     * @param $fid
     *
     * @return string
     */
    public function fidToUserId($fid)
    {
        $value = Cache::redis()->hGet('fid_#_user_id', $this->host_name . '@' . $fid);
        Cache::release();
        return $value;
    }


    /**
     * 发送消息给用户
     *
     * @param       $user_id
     * @param array $msg
     *
     * @return bool|null
     */
    public function sendToUser($user_id, array $msg)
    {
        //检查redis ping 防止掉线
        $fid = $this->userIdToFid($user_id);
        if (!$fid) {
            return null;
        }
        $fid_server = explode('@', $fid);
        $host_name = $fid_server[ 0 ];
        $fid = $fid_server[ 1 ];
        $m=substr($this->host_name, 0, 6);
        if ($host_name == $this->host_name) {
            if (!$this->server->exist($fid)) {
                //断开
                $this->server->close($fid);
                $redis = Cache::redis();
                $redis->hDel('user_id_#_fid', $user_id);
                $redis->hDel('fid_#_user_id', $fid);
                Cache::release();
                return false;
            }
            $this->server->push($fid, json_encode($msg));
        } elseif (strpos($host_name, $m)===0) {
            try {
                //通过集群中的其他服务器推送
                Http::put('http://' . $host_name . ':' . $this->config[ 'port' ] . '/open/push', [], ['fid' => $fid,
                                                                                                      'msg' => json_encode($msg),
                                                                                                      'secret' => $this->config[ 'secret' ]]);
            } catch (\Exception $exception) {
            } catch (\Error $exception) {
            }
        }
        return null;
    }

    public function configure()
    {
        $this->name('websocket')->asName("swoole websocket服务")->des("启动swoole websocket服务 需要安装 swoole 拓展");
    }
}
