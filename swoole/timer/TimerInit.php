<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/6/21
 * Time: 下午2:42
 */

namespace rap\swoole\timer;


use rap\aop\Event;
use rap\cache\CacheInterface;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\Init;
use rap\ioc\Ioc;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Router;

class TimerInit implements Init {

    public function appInit(AutoFindHandlerMapping $autoMapping, Router $router) {
        $config = Config::getFileConfig();
        $item = $config[ "queue" ][ 'redis' ];
        Ioc::bind(CacheInterface::class, RedisCache::class, function(RedisCache $redisCache) use ($item) {
            $redisCache->config($item);
        });
        $autoMapping->prefix('/timer', TimerController::class);
        Event::add('onServerStart', TimerService::class, 'start');
        return true;
    }

}