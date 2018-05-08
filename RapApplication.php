<?php
namespace rap;
use rap\cache\CacheInterface;
use rap\cache\FileCache;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\db\Connection;
use rap\db\MySqlConnection;
use rap\ioc\Ioc;
use rap\log\FileLog;
use rap\log\LogInterface;
use rap\storage\OssStorage;
use rap\storage\StorageInterface;
use rap\web\Application;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Router;
use rap\web\mvc\view\SmartyView;
use rap\web\mvc\view\View;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/3
 * Time: 下午2:26
 */
class RapApplication extends Application{

    public function init( AutoFindHandlerMapping $autoMapping, Router $router){
        $config=Config::getFileConfig();
        $app= $config['app'];
        if($app['init']){
            Ioc::bind(Init::class,$app['init']);
            /* @var $init Init  */
            $init=Ioc::get(Init::class);
            $ret=$init->appInit($autoMapping,$router);
            if($ret==true){
                return;
            }
        }


        $map=$config["mapping"];
        if($map){
            foreach ($map as $key=>$value) {
                $autoMapping->prefix($key,$value);
            }
        }
        $item=$config["db"];
        if($item){
            if($item['type']=='mysql'){
                unset($item['type']);
                Ioc::bind(Connection::class,MySqlConnection::class,function (MySqlConnection $connection)use($item){
                    $connection->config($item);
                });
            }
        }
        $item=$config["storage"];
        if($item){
            if($item['type']=='oss'){
                unset($item['type']);
                Ioc::bind(StorageInterface::class,OssStorage::class,function(OssStorage $ossStorage) use($item){
                    $ossStorage->config($item);
                });
            }
        }
        $item=$config["view"];
        if($item){
            if($item['type']=='smarty'){
                unset($item['type']);
                Ioc::bind(View::class,SmartyView::class,function(SmartyView $smartyView) use($item){
                    $smartyView->config($item);
                });
            }
        }
        $item=$config["cache"];
        if($item){
            if($item['type']=='file'){
                Ioc::bind(CacheInterface::class,FileCache::class,function(FileCache $fileCache) use($item){
                    $fileCache->config($item);
                });
            }elseif($item['type']=='redis'){
                Ioc::bind(CacheInterface::class,RedisCache::class,function(RedisCache $redisCache )use($item){
                    $redisCache->config($item);
                });
            }
        }

        $item=$config["log"];
        if($item){
            if($item['type']=='file'){
                Ioc::bind(LogInterface::class,FileLog::class,function(FileLog $fileLog )use ($item){
                    $fileLog->config($item);
                });
            }
        }
    }



}