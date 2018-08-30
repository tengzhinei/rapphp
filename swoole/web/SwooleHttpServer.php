<?php
namespace rap\swoole\web;


use rap\aop\AopBuild;
use rap\aop\Event;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\console\Command;
use rap\console\command\AopFileBuild;
use rap\db\Connection;
use rap\ioc\Ioc;
use rap\RapApplication;
use rap\session\Session;
use rap\swoole\task\TaskConfig;
use rap\web\Application;
use rap\web\mvc\RequestHolder;


/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/4
 * Time: 下午1:55
 */
class SwooleHttpServer extends Command{

    private $config =[
        'ip'=>'0.0.0.0',
        'port'=>9501,
        'document_root'=>"",
        'enable_static_handler'=>true,
        'task_worker_num'=>100,
        'worker_num'=>20,
        'task_max_request'=>0
    ];



    public function run(){
        $this->config=array_merge($this->config,Config::get('swoole_http'));
        $http = new \swoole_http_server($this->config['ip'], $this->config['port']);
        $http->set([
            'buffer_output_size' => 32 * 1024 *1024, //必须为数字
            'worker_num'=>1,
            'document_root' => $this->config['document_root'],
            'enable_static_handler' => $this->config['enable_static_handler'],
            'worker_num' => $this->config['worker_num'],
            'task_worker_num' => $this->config['task_worker_num'],
            'task_max_request'=>$this->config['task_max_request'],
        ]);
        $http->on('workerstart',[$this,'onWorkStart'] );
        $http->on('start',[$this,'onStart'] );
        $http->on('task', [$this,'onTask']);
        $http->on('finish', [$this,'onFinish']);
        $http->on('request', [$this,'onRequest'] );
        $this->writeln("http服务启动成功");
        $http->start();

    }

    public function onStart($serv) {
        $application= Ioc::get(Application::class);
        $application->server=$serv;
        Event::trigger('onRapHttpStart','');
    }

    public function onWorkStart($serv, $id) {
        $application= Ioc::get(Application::class);
        $application->server=$serv;
        $application->task_id=$id;
        Event::trigger('onHttpWorkStart','');
    }

    public function onTask($serv, $task_id, $from_id, $data){
        $clazz=$data['clazz'];
        $method=$data['method'];
        $params=$data['params'];
        $config=$data['config'];
        /* @var $deliver TaskConfig  */
        $deliver = Ioc::get(TaskConfig::class);
        $deliver->setTaskInit($config);
        $bean=Ioc::get($clazz);
        $method =   new \ReflectionMethod($clazz, $method);
        $method->invokeArgs($bean,$params);
    }


    public function onFinish(){

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
            $req->holder('rap-start-time',$time);
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
            RequestHolder::setRequest($req);
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

    public function configure(){
        $this->name('http')
            ->asName("swoole http服务器")
            ->des("启动swoole http 服务器 需要安装 swoole 拓展");
    }



}