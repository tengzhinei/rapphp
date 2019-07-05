<?php
namespace rap\swoole\web;


use rap\aop\Event;
use rap\config\Config;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\log\Log;
use rap\ServerEvent;
use rap\swoole\ServerWatch;
use rap\swoole\task\TaskConfig;
use rap\util\FileUtil;
use rap\web\Application;
use rap\swoole\CoContext;
use Swoole\Runtime;


/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/4
 * Time: 下午1:55
 */
class SwooleHttpServer extends Command {

    private $config = ['ip' => '0.0.0.0',
                       'port' => 9501,
                       'static_handler_locations' => [],
                       'enable_static_handler' => false,
                       'task_worker_num' => 3,
                       'worker_num' => 1,
                       'max_request' => 0,
                       'task_max_request' => 1000,
                       'coroutine' => true,
                       'http2' => true,
                       'auto_reload' => false];


    public function run() {
        $this->config = array_merge($this->config, Config::get('swoole_http'));
        if ($this->config[ 'coroutine' ]) {
            //mysql redis 协程化
            Runtime::enableCoroutine();
        }
        $document_root = '';

        if ($this->config[ 'enable_static_handler' ] && !Config::get('app')[ 'debug' ]) {
            //开启静态
            foreach ($this->config[ 'static_handler_locations' ] as $dir) {
                FileUtil::copy(ROOT_PATH . $dir, ROOT_PATH . '.rap_static_file' . '/' . $dir);
            }
            $document_root = '.rap_static_file';
        }

        $http = new \swoole_http_server($this->config[ 'ip' ], $this->config[ 'port' ]);
        $http->set(['buffer_output_size' => 32 * 1024 * 1024, //必须为数字
                    'document_root' => ROOT_PATH . $document_root,
                    'enable_static_handler' => $this->config[ 'enable_static_handler' ],
                    'worker_num' => $this->config[ 'worker_num' ],
                    'max_request' => $this->config[ 'max_request' ],
                    'task_worker_num' => $this->config[ 'task_worker_num' ],
                    'task_max_request' => $this->config[ 'task_max_request' ],
                    'open_http2_protocol' => $this->config[ 'http2' ]]);
        $http->on('workerstart', [$this, 'onWorkStart']);
        $http->on('workerstop', [$this, 'onWorkerStop']);
        $http->on('start', [$this, 'onStart']);
        $http->on('shutdown', [$this, 'onShutdown']);
        $http->on('task', [$this, 'onTask']);
        $http->on('finish', [$this, 'onFinish']);
        $http->on('request', [$this, 'onRequest']);
        $this->writeln("http服务启动成功");
        Event::trigger(ServerEvent::onBeforeServerStart, $http);
        $http->start();

    }

    public function onStart($server) {
        Log::notice('http服务启动');
        $application = Ioc::get(Application::class);
        $application->server = $server;
        Event::trigger(ServerEvent::onServerStart, $server);
        if ($this->config[ 'auto_reload' ] && Config::get('app')[ 'debug' ]) {
            $this->writeln("自动加载");
            $reload = new ServerWatch();
            $reload->init($server);
        }

    }

    public function onShutdown($server) {
        Log::notice('http服务停止');
        Event::trigger(ServerEvent::onServerShutdown, $server);
        if ($this->config[ 'enable_static_handler' ]) {
            FileUtil::delete(ROOT_PATH . '.rap_static_file');
        }
    }

    public function onWorkStart($server, $id) {
        Log::info('worker进程启动:' . $id);
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger(ServerEvent::onServerWorkStart, $server, $id);
    }

    public function onWorkerStop($server, $id) {
        Log::info('worker进程停止:' . $id);
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger(ServerEvent::onServerWorkerStop, $server, $id);
    }

    public function onTask($serv, $task_id, $from_id, $data) {
        Log::info('Task开始:' . $task_id);
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


    public function onFinish() {

    }

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
            CoContext::getContext()->setRequest($req);
            Log::info('请求开始', ['url' => $req->url(), 'session_id' => $req->session()->sessionId()]);
            //swoole  4.2.9
            defer(function() use ($req) {
                CoContext::getContext()->release();
                Event::trigger(ServerEvent::onRequestDefer);
                Log::info('请求结束', ['url' => $req->url(), 'session_id' => $req->session()->sessionId()]);
            });
            $application->start($req, $rep);
            //释放协程里的变量和
        } catch (\Exception $exception) {
            $response->end($exception->getMessage());
            $msg = str_replace("rap\\exception\\", "", get_class($exception)) . " in " . str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
            Log::error('服务异常 :' . $exception->getCode() . ' : ' . $msg);
            return;
        } catch (\Error $e) {
            $response->end($e->getMessage());
            $msg = str_replace("rap\\exception\\", "", get_class($e)) . " in " . str_replace(ROOT_PATH, "", $e->getFile()) . " line " . $e->getLine();
            Log::error('服务异常 :' . $e->getCode() . ' : ' . $msg);
            return;
        }
    }

    public function configure() {
        $this->name('http')->asName("swoole http服务器")->des("启动swoole http 服务器 需要安装 swoole 拓展");
    }


}