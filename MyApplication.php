<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/8
 * Time: 下午3:31
 */

namespace rap;


use rap\app\mag\circle\forum\controller\ForumController;
use rap\app\mag\controller\Article;
use rap\cache\Cache;
use rap\cache\CacheInterface;
use rap\cache\FileCache;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\config\ConfigInterface;
use rap\config\DBFileConfig;
use rap\db\Connection;
use rap\db\MySqlConnection;
use rap\ioc\Ioc;
use rap\log\FileLog;
use rap\log\LogInterface;
use rap\log\RedisLog;
use rap\web\Application;
use rap\web\HttpRequest;
use rap\web\HttpResponse;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Router;
use rap\web\mvc\view\SmartyView;
use rap\web\mvc\view\View;

class MyApplication extends Application{
    public function init(HttpRequest $request, HttpResponse $response, AutoFindHandlerMapping $autoMapping, Router $router){
        $autoMapping->prefix("/mag","/rap/app/mag");
//        $autoMapping->prefix("/forum","/rap/app/mag/circle/forum");
        $autoMapping->prefix("/mag/a","/rap/app/zdController");
        $autoMapping->prefix("/test","/rap/app/Test");
        Ioc::bind(Connection::class,MySqlConnection::class,function(Connection $connection){
            $connection->config([
                    'dsn'=>"mysql:dbname=magapp-x;host=localhost",
                    'username'=>"root",
                    'password'=>"root"
            ]);
        });
        Ioc::bind(View::class,SmartyView::class);
        Ioc::bind(CacheInterface::class,RedisCache::class,function(RedisCache $cache){

        });

        Cache::set("a",'a');
       echo Cache::get('a');
die;

        Ioc::bind(ConfigInterface::class,DBFileConfig::class,function(DBFileConfig $config){
            $config->config([
                "file_path"=>"",//配置文件地址
                "first_query"=>"file",//先查询的类型 file,DB
                "db_table"=>"config",
                "db_module_field"=>"module",
                "db_value_field"=>"content",
            ]);
        });

        Ioc::bind(LogInterface::class,FileLog::class,function(FileLog $log){
                $log->config([

                ]);
        });


        $router->intVarContain("id")->lettersVar("name");
        $router->group("forum")->then(function(Router $router){
//          $router->when("/cat/list")->post()->bindCtr(ForumController::class,"c/atList");
            $router->when("cat/:id/add")->toDo(function(){
                return [];
            });
                $router->when("cat/:id/save")->get()->toDo(function($id,$name,Article $article,HttpResponse $response){
                    $response->assign("article",$article);
                    $response->assign("name",$name);
                  return '/index.tpl';
                });

                $router->whenMiss(function(){
                    echo 12;
                });

        });
        $router->whenMiss(function(){
            echo 112;
        });


    }

    public function aop(){
    }


}