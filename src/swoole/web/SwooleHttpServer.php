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
use Swoole\Atomic;
use Swoole\Runtime;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/4
 * Time: 下午1:55
 */
class SwooleHttpServer extends Command
{
    /**
     * @var Atomic
     */
    private $workerAtomic;
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


    public function run()
    {
        $this->workerAtomic = new Atomic(0);
        $this->workerAtomic->add(1);
        $this->worker_version = $this->workerAtomic->get();
        $this->config = array_merge($this->config, Config::get('swoole_http'));
        //mysql redis 协程化
        Runtime::enableCoroutine();
        $document_root = '';

        if ($this->config['enable_static_handler'] && !Config::get('app')['debug']) {
            //开启静态
            foreach ($this->config['static_handler_locations'] as $dir) {
                FileUtil::copy(ROOT_PATH . $dir, ROOT_PATH . '.rap_static_file' . '/' . $dir);
            }
            $document_root = '.rap_static_file';
        }

        $config = array_merge($this->config, ['open_http2_protocol' => $this->config['http2'],
                                              'document_root' => ROOT_PATH . $document_root,
                                              'buffer_output_size' => 32 * 1024 * 1024]);
        $http = new \swoole_http_server($this->config['ip'], $this->config['port']);
        $http->set($config);
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

    public function onStart($server)
    {
        Log::notice('swoole http start : http服务启动');
        $application = Ioc::get(Application::class);
        $application->server = $server;

        Event::trigger(ServerEvent::onServerStart, $server);
        if ($this->config['auto_reload'] && Config::get('app')['debug']) {
            $this->writeln("自动加载");
            $reload = new ServerWatch();
            $reload->init($server);
        }
    }

    public function onShutdown($server)
    {
        Log::notice('swoole http shutdown : http服务停止');
        Event::trigger(ServerEvent::onServerShutdown, $server);
        if ($this->config['enable_static_handler']) {
            FileUtil::delete(ROOT_PATH . '.rap_static_file');
        }
    }

    public function onWorkStart($server, $id)
    {
        Log::info('swoole worker start:' . $id);
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        $application->workerAtomic = $this->workerAtomic;
        Event::trigger(ServerEvent::onServerWorkStart, $server, $id);
    }

    public function onWorkerStop($server, $id)
    {
        Log::info('swoole worker stop:' . $id);
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger(ServerEvent::onServerWorkerStop, $server, $id);
    }

    public function onTask($serv, $task_id, $from_id, $data)
    {
        Log::info('swoole task start:' . $task_id);
        $clazz = $data['clazz'];
        $method = $data['method'];
        $params = $data['params'];
        $config = $data['config'];
        /* @var $deliver TaskConfig */
        $deliver = Ioc::get(TaskConfig::class);
        $deliver->setTaskInit($config);
        $bean = Ioc::get($clazz);
        $method = new \ReflectionMethod($clazz, $method);
        $method->invokeArgs($bean, $params);
    }


    public function onFinish()
    {
    }

    private $worker_version = 0;

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        try {
            if ($request->server['request_uri'] == '/favicon.ico') {
                $response->end();
                return;
            }
            /* @var $application Application */
            $application = Ioc::get(Application::class);
            $version = $this->workerAtomic->get();
            if ($this->worker_version != $this->workerAtomic->get()) {
                Ioc::workerScopeClear();
                $this->worker_version = $version;
            }
            $rep = new SwooleResponse();
            $req = new SwooleRequest($rep);
            $req->swoole($request);
            $rep->swoole($req, $response);
            //生成 session
            $rep->session()->sessionId();
            CoContext::getContext()->setRequest($req);
            Log::info('http request start', ['url' => $req->url(), 'session_id' => $req->session()->sessionId()]);
            //swoole  4.2.9
            defer(function () use ($req) {
                try {
                    Event::trigger(ServerEvent::onRequestDefer);
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
            $msg = str_replace(
                "rap\\exception\\",
                "",
                get_class($exception)
            ) . " in " .
                str_replace(ROOT_PATH, "", $exception->getFile()) . " line " . $exception->getLine();
            Log::error('http request error :' . $exception->getCode() . ' : ' . $msg);
            return;
        } catch (\Error $e) {
            $response->end($e->getMessage());
            $msg = str_replace(
                "rap\\exception\\",
                "",
                get_class($e)
            ) . " in " .
                str_replace(ROOT_PATH, "", $e->getFile()) . " line " . $e->getLine();
            Log::error('http request error :' . $e->getCode() . ' : ' . $msg);
            return;
        }
    }

    public function configure()
    {
        $this->name('http')->asName("swoole http服务器")->des("启动swoole http 服务器 需要安装 swoole 拓展");
    }
}
