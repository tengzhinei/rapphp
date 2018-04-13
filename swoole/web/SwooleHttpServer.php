<?php
namespace rap\swoole\web;


use rap\aop\AopBuild;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\console\Command;
use rap\console\command\AopFileBuild;
use rap\db\Connection;
use rap\ioc\Ioc;
use rap\RapApplication;
use rap\web\Application;


/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/4
 * Time: 下午1:55
 */
class SwooleHttpServer extends Command{

    private $confog=[
        'ip'=>'0.0.0.0',
        'port'=>9501,
        'document_root'=>"",
        'enable_static_handler'=>true,
        'task_worker_num'=>100,
        'task_max_request'=>0
    ];



    public function run(){
        $this->confog=array_merge($this->confog,Config::get('swoole_http'));
        $http = new \swoole_http_server($this->confog['ip'], $this->confog['port']);
        $http->set([
            'buffer_output_size' => 32 * 1024 *1024, //必须为数字
            'worker_num'=>1,
            'document_root' => $this->confog['document_root'],
            'enable_static_handler' => $this->confog['enable_static_handler'],
            //'task_worker_num' => 1,
            'task_max_request'=>1,
        ]);
        $http->on('workerstart',[$this,'onWorkStart'] );
        $http->on('task', [$this,'onRequest']);
        $http->on('finish', [$this,'onFinish']);
        $http->on('request', [$this,'onRequest'] );
        $this->writeln("http服务启动成功");
        $http->start();

    }

    public function onWorkStart($serv, $id) {
        $application= Ioc::get(Application::class);
        $application->server=$serv;
        $application->task_id=$id;
    }

    public function onTask($serv, $task_id, $from_id, $data){
        $clazz=$data['clazz'];
        $method=$data['method'];
        $params=$data['params'];
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
            /* @var $application Application  */
            $application=Ioc::get(Application::class);
            $rep=new SwooleResponse();
            $req=new SwooleRequest($rep);
            $req->swooleRequest($request);
            $rep->swooleResponse($response);
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

    public function configure(){
        $this->name('http')
            ->asName("swoole http服务器")
            ->des("启动swoole http 服务器 需要安装 swoole 拓展");
    }



}