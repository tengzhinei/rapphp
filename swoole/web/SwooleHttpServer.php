<?php
namespace rap\swoole\web;


use rap\aop\Event;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\config\Seal;
use rap\console\Command;
use rap\ioc\Ioc;
use rap\swoole\Context;
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
                       'static_handler_locations'=>[],
                       'enable_static_handler' => false,
                       'task_worker_num' => 3,
                       'worker_num' => 1,
                       'task_max_request' => 1000,
                       'coroutine' => true,
                       'auto_reload' => false];


    /**
     * @param $host_name   string 对外暴露的host
     * @param $seal_secret string 配置中心密钥
     */
    public function run($host_name, $seal_secret) {
        $this->config = array_merge($this->config, Config::get('swoole_http'));
        if ($this->config[ 'coroutine' ]) {
            //mysql redis 协程化
            Runtime::enableCoroutine();
        }
        $document_root='';

        if($this->config['enable_static_handler']&&!Config::get('app')['debug']){
            //开启静态
            foreach ($this->config['static_handler_locations'] as $dir) {
                FileUtil::copy(ROOT_PATH.$dir,ROOT_PATH.'.rap_static_file'.'/'.$dir);
            }
            $document_root='.rap_static_file';
        }

        $http = new \swoole_http_server($this->config[ 'ip' ], $this->config[ 'port' ]);
        $http->set(['buffer_output_size' => 32 * 1024 * 1024, //必须为数字
                    'document_root' => ROOT_PATH.$document_root,
                    'enable_static_handler' => $this->config[ 'enable_static_handler' ],
                    'worker_num' => $this->config[ 'worker_num' ],
                    'task_worker_num' => $this->config[ 'task_worker_num' ],
                    'task_max_request' => $this->config[ 'task_max_request' ],
                    'open_http2_protocol' => true]);
        $http->on('workerstart', [$this, 'onWorkStart']);
        $http->on('workerstop', [$this, 'onWorkerStop']);
        $http->on('start', [$this, 'onStart']);
        $http->on('shutdown', [$this, 'onShutdown']);
        $http->on('task', [$this, 'onTask']);
        $http->on('finish', [$this, 'onFinish']);
        $http->on('request', [$this, 'onRequest']);
        $this->writeln("http服务启动成功");
        Event::trigger('onBeforeServerStart', $http);
        $http->start();

    }

    public function onStart($server) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        Event::trigger('onServerStart', $server);
        if ($this->config[ 'auto_reload' ] && Config::get('app')[ 'debug' ]) {
            $this->writeln("自动加载");
            $reload = new ServerWatch();
            $reload->init($server);
        }

    }

    public function onShutdown($server) {
        Event::trigger('onServerShutdown', $server);
        if($this->config['enable_static_handler']) {
            FileUtil::delete(ROOT_PATH.'.rap_static_file');
        }
    }

    public function onWorkStart($server, $id) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger('onServerWorkStart', [$server, $id]);
    }

    public function onWorkerStop($server, $id) {
        $application = Ioc::get(Application::class);
        $application->server = $server;
        $application->task_id = $id;
        Event::trigger('onServerWorkerStop', [$server, $id]);
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