## RapPHP  为效率而生的PHP 框架


#### RapPHP 是什么

* * * * *


RapPHP 框架提供了全面的 IOC,AOP的底层支持,架构设计简洁而有扩展性,开发灵活而有设计感,RapPHP提供了完整的 Web开发需要的核心组件;同时 RapPHP支持 SWOOLE和传统(lamp,lnmp)双部署方案,可以通过 SWOOLE 提供常驻内存的高性能 php 运行环境;

**官网** [http://rapphp.com/](http://rapphp.com/)

**在线文档** [http://doc.rapphp.com/](http://doc.rapphp.com/)

**github** [https://github.com/tengzhinei/rapphp](https://github.com/tengzhinei/rapphp)

**在线qq交流群** 677411689

#### 主要特性:

* * * * *
* 高性能:支持传统lamp(lnmp)部署方案,同时支持rapphp+Swoole引擎部署方案;
* IOC:真正的依赖注入,控制反转,开始更高级的设计思想,让你的代码更优雅,可控;
* AOP:面向切面编程,代码低耦合;
* MVC 架构:简单好用的 MVC 架构,配置简单,程序可读性更高;
* ORM:独特SQL 构造方法,Record 数据库模型,二级缓存机制,数据库操作更加简单,高效;
* SWOOLE:不修改代码的情况下一键启用 SWOOLE,PHP运行性能全面提升;
* 上手快:框架居然使用了一些比较高级的设计思想,但是再框架内使用都特别简单
* 支持异步任务,定时任务,websocket等高级功能
* 包含缓存, 文件存储,日志等多钟常用功能;


> 有了 IOC,AOP,SWOOLE神器,可以拉近和编译性语言(JAVA等)的性能;



### 安利
* * * * *
SWOOLE https://swoole.com/




#### IOC

对象依赖注入,系统内对象绝对单例
* * * * *
~~~
class ToolController{

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var TenantService
     */
    private $tenantService;

    public function _initialize(Connection $connection,TenantService $tenantService){
        $this->connection=$connection;
        $this->tenantService=$tenantService;
    }
}
~~~

#### AOP
* * * * *
前置切面,后置切面,环绕切面, AOP 支持完整可控
~~~
在UserLogic调用saveUser,delUser方法前调用UserLogicTestAop的testBefore方法
   AopBuild::before(UserLogic::class)
            ->methods(["saveUser","delUser"])
            ->wave(UserLogicTestAop::class)
            ->using("testBefore")
            ->addPoint();

//在UserLogic调用方法以save或del开头的方法前调用UserLogicTestAop的testAfter方法
        AopBuild::after(UserLogic::class)
            ->methodsStart(["save","del"])
            ->wave(UserLogicTestAop::class)
            ->using("testAfter")
            ->addPoint();
~~~

### MVC
* * * * *
MVC 路径自动查找,参数自动绑定,返回(页面, json)自动解析

~~~

class IndexController 
{
    public function index($name, Response $response)
    {	
    	$response->assign('name',$name)
        return 'index';
    }
      public function json($name)
    {
        return ['success'=>true,'data'=>$name];
    }
}
~~~

### ORM
* * * * *
数据模型,增删改查,二级缓存机制,数据库操作轻松搞定
 ~~~
 $select = Good::select("g.*") -> order("rank desc");
 $select -> join("good_tag gt",'gt.good_id=g.id') -> where("tag_id",$tag);
 $data = $select -> cache() -> page($page,$step);
DB::runInTrans(function() {
               $user = User::getLock(1);
          		$user -> name = 'tengzhinei';
          		$user -> save();
        });
~~~ 
### SWOOLE
* * * * *
一键启动 swoole 服务器,异步任务,定时任务,websocket 轻松搞定
 ~~~
 'swoole_http'=>[
               'ip'=>'0.0.0.0', //正常不需要修改
                'port'=>9501,  //默认使用9501端口
                'document_root'=>ROOT_PATH, 
                'enable_static_handler'=>false, //是否可以访问文件 正常不可以
                'worker_num'=>20,				  //默认开启多少worker进程
                'task_worker_num'=>4,          //默认开启几个 task 进程
                'task_max_request'=>0		  //访问多少次释放worker进程
        ],
//启动服务   
php index.php http    
//异步任务
Task::deliver(MyTaskService::class,'task',['key'=>100,'name'=>'test']);
//定时任务
Timer::after('/test/a',['a'=>'1'],10,['tent-header'=>'test']);
  ~~~
  
   
### 数据库连接池
 ~~~

    ,"db"=>[
        'type'=>'mysql',
        'dsn'=>"mysql:dbname=doc;host=db;charset=utf8",
        'username'=>"root",
        'password'=>"root",
        'pool'=>['min'=>1,
                 'max'=>10,
                 'check'=>30,
                 'idle'=>30
        ],
    ],
 ~~~
### Redis 连接池
 ~~~
   'cache'=>[
          'type'=>'redis',
          'host'       => 'redis',
          'port'       => 6379,
          'password'   => '',
          'select'     => 1,
          'timeout'    => 0,
          'expire'     => -1,
          'persistent' => false,
          'pool'=>['min'=>1,
                   'max'=>10,
                   'check'=>30,
                   'idle'=>30
          ],
      ]
 ~~~
### Rpc 远程调用

 ~~~ 
  'rpc_service'=>[
        'token'=>'123',
    ],
    'rpc'=>[
       'cloud'=>['register'=>\app\rpc\RPcTestRegister::class,
                 'host' => 'lingxianghui.magshop.sapp.magcloud.net',
                 'port'=>80,
                 'token' => '123',
                 'timeout'=>5,
                 'fuse_time'=>30,//熔断器熔断后多久进入半开状态
                 'fuse_fail_count'=>20,//连续失败多少次开启熔断
                 'pool'=>['min'=>1,
                          'max'=>10,
                          'check'=>30,
                          'idle'=>30
                 ],
       ]
    ]
  ~~~
 
### 安利
* * * * *
SWOOLE https://swoole.com/


