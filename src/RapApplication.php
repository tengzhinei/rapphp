<?php
namespace rap;

use rap\aop\Event;
use rap\cache\CacheInterface;
use rap\cache\FileCache;
use rap\cache\RedisCache;
use rap\config\Config;
use rap\config\FileConfig;
use rap\db\Connection;
use rap\db\MySqlConnection;
use rap\db\SqliteConnection;
use rap\ioc\Ioc;
use rap\session\RedisSession;
use rap\storage\LocalFileStorage;
use rap\storage\OssStorage;
use rap\storage\StorageInterface;
use rap\swoole\pool\ResourcePool;
use rap\web\Application;
use rap\web\mvc\AutoFindHandlerMapping;
use rap\web\mvc\Router;
use rap\web\mvc\view\PhpView;
use rap\web\mvc\view\TwigView;
use rap\web\mvc\view\View;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/3
 * Time: 下午2:26
 */
class RapApplication extends Application {

    public function init(AutoFindHandlerMapping $autoMapping, Router $router) {
        $config = Config::getFileConfig();
        $map = $config[ "mapping" ];
        if ($map) {
            foreach ($map as $key => $value) {
                $autoMapping->prefix($key, $value);
            }
        }
        $item = $config[ "view" ];
        if ($item) {
            if ($item[ 'type' ] == 'php') {
                unset($item[ 'type' ]);
                Ioc::bind(View::class, PhpView::class, function(PhpView $view) use ($item) {
                    $view->config($item);
                });
            } else if ($item[ 'type' ] == 'twig') {
                unset($item[ 'type' ]);
                Ioc::bind(View::class, TwigView::class, function(TwigView $view) use ($item) {
                    $view->config($item);
                });
            }
        }

        $app = $config[ 'app' ];
        $init = null;
        Event::add(ServerEvent::onServerWorkStart, Application::class, 'onServerWorkStart');
        Event::trigger(ServerEvent::onAppInit, $autoMapping, $router);
        if ($app[ 'init' ]) {
            Ioc::bind(Init::class, $app[ 'init' ]);
            /* @var $init Init */
            $init = Ioc::get(Init::class);
            $init->appInit($autoMapping, $router);

        }

    }

    public function onServerWorkStart() {
        /* @var $fileConfig  FileConfig*/
        $fileConfig = Ioc::get(FileConfig::class);
        $fileConfig->mergeProvide();
        //合并配置中心的配置
        $config = Config::getFileConfig();
        $item = $config[ "db" ];
        if ($item) {
            if ($item[ 'type' ] == 'mysql') {
                unset($item[ 'type' ]);
                Ioc::bind(Connection::class, MySqlConnection::class, function(MySqlConnection $connection) use ($item) {
                    $connection->config($item);
                });
            }
            if ($item[ 'type' ] == 'sqlite') {
                unset($item[ 'type' ]);
                Ioc::bind(Connection::class, SqliteConnection::class, function(SqliteConnection $connection) use ($item) {
                    $connection->config($item);
                });
            }
        }
        $item = $config[ "storage" ];
        if ($item) {
            if ($item[ 'type' ] == 'oss') {
                unset($item[ 'type' ]);
                Ioc::bind(StorageInterface::class, OssStorage::class, function(OssStorage $ossStorage) use ($item) {
                    $ossStorage->config($item);
                });
            } else if ($item[ 'type' ] == 'local') {
                unset($item[ 'type' ]);
                Ioc::bind(StorageInterface::class, LocalFileStorage::class, function(LocalFileStorage $ossStorage) use ($item) {
                    $ossStorage->config($item);
                });
            }
        }

        $item = $config[ "cache" ];
        if ($item) {
            if ($item[ 'type' ] == 'file') {
                Ioc::bind(CacheInterface::class, FileCache::class, function(FileCache $fileCache) use ($item) {
                    $fileCache->config($item);
                });
            } elseif ($item[ 'type' ] == 'redis') {
                Ioc::bind(CacheInterface::class, RedisCache::class, function(RedisCache $redisCache) use ($item) {
                    $redisCache->config($item);
                });
            }
        }
        $item = $config[ "session" ];
        if ($item) {
            if ($item[ 'type' ] == 'redis') {
                Ioc::bind(RedisSession::REDIS_CACHE_NAME, RedisCache::class, function(RedisCache $redisCache) use ($item) {
                    $redisCache->config($item);
                });
            }
        }
        if (IS_SWOOLE) {
            if ($config[ 'db' ]) {
                ResourcePool::instance()->preparePool(Connection::class);
            }
            if ($config[ 'cache' ]) {
                ResourcePool::instance()->preparePool(CacheInterface::class);
            }
            if ($config[ 'session' ] && $config[ 'session' ][ 'type' ] == 'redis') {
                ResourcePool::instance()->preparePool(RedisSession::REDIS_CACHE_NAME);
            }
        }

    }


}