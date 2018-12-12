<?php
namespace rap\swoole\web;


use rap\aop\Event;
use rap\config\Config;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\swoole\task\TaskConfig;
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
                       'document_root' => "",
                       'enable_static_handler' => false,
                       'task_worker_num' => 3,
                       'worker_num' => 1,
                       'task_max_request' => 1000,
                       'coroutine'=>true];


    public function run() {
        $this->config = array_merge($this->config, Config::get('swoole_http'));
        $http = new \swoole_http_server($this->config[ 'ip' ], $this->config[ 'port' ]);
        $http->set(['buffer_output_size' => 32 * 1024 * 1024, //必须为数字
                    'document_root' => $this->config[ 'document_root' ],
                    'enable_static_handler' => $this->config[ 'enable_static_handler' ],
                    'worker_num' => $this->config[ 'worker_num' ],
                    'task_worker_num' => $this->config[ 'task_worker_num' ],
                    'task_max_request' => $this->config[ 'task_max_request' ],
                    'open_http2_protocol' => true]);
        $http->on('workerstart', [$this, 'onWorkStart']);
        $http->on('workerstop', [$this, 'onWorkerStop']);
        $http->on('start', [$this, 'onStart']);
        $http->on('task', [$this, 'onTask']);
        $http->on('finish', [$this, 'onFinish']);
        $http->on('request', [$this, 'onRequest']);
        $this->writeln("http服务启动成功");
        if ($this->config[ 'coroutine' ]) {
            //mysql redis 协程化
            Runtime::enableCoroutine();
        }
        $http->start();

    }

    public function onStart($server) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        Event::trigger('onServerStart', '');
    }

    public function onWorkStart($server, $id) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger('onServerWorkStart', '');
    }

    public function onWorkerStop($server, $id) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger('onServerWorkerStop', '');
    }

    public function onTask($serv, $task_id, $from_id, $data) {
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
            $application->start($req, $rep);
            //释放协程里的变量和
            CoContext::getContext()->release();
        } catch (\Exception $exception) {
            $response->end($exception->getMessage());
            return;
        } catch (\Error $e) {
            $response->end($e->getMessage());
            return;
        }
    }

    public function configure() {
        $this->name('http')->asName("swoole http服务器")->des("启动swoole http 服务器 需要安装 swoole 拓展");
    }


}