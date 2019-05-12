<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/1 10:17 PM
 */

namespace rap\config;


use rap\aop\Event;
use rap\ioc\Ioc;
use rap\swoole\web\ServerInfo;
use rap\util\FileUtil;
use rap\util\http\Http;
use rap\web\Application;
use rap\web\interceptor\Interceptor;
use rap\web\Request;
use rap\web\Response;

class Seal implements Interceptor {

    const SEAL_FILE = ROOT_PATH . 'runtime/seal';

    static $push_secret = 0;

    static $local_ip;


    public static function init() {
        $config = Config::getFileConfig();
        $seal = $config[ 'seal' ];
        $secret = $seal[ 'secret' ];
        if (ServerInfo::$SEAL_SECRET) {
            $secret = ServerInfo::$SEAL_SECRET;
        }
        $local_port = $config[ 'swoole_http' ][ 'port' ];
        if (!$local_port) {
            $local_port = $config[ 'websocket' ][ 'port' ];
        }
        if (!$local_port) {
            $local_port = '9501';
        }
        self::$local_ip = '';
        $ips = swoole_get_local_ip();
        if ($ips[ "eth0" ]) {
            self::$local_ip = $ips[ "eth0" ];
        }
        $MY_HOST = self::$local_ip . ':' . $local_port;
        $port = '9501';
        if ($seal[ 'port' ]) {
            $port = $seal[ 'port' ];
        }
        self::$push_secret = md5(time() . rand(1, 1000) . $MY_HOST);
        $url = $seal[ 'url' ];
        if (!$url) {
            $url = 'http://' . $seal[ 'host' ] . ':' . $port;
        }
        $response = \Unirest\Request::post($url . '/api/register', [], ['app_name' => $seal[ 'app_name' ],
                                                                        'secret' => $secret,
                                                                        'client_name' => $config[ 'app' ][ 'name' ] . '(' . self::$local_ip . ')',
                                                                        'push_link' => 'http://' . $MY_HOST . '/seal________push?push_secret=' . self::$push_secret]);
        FileUtil::writeFile(Seal::SEAL_FILE, $response->raw_body);
        //添加拦截器
        /* @var $app Application */
        $app = Ioc::get(Application::class);
        $app->addInterceptor(Seal::class);
        //添加关闭监听
        Event::add('onServerShutdown', Seal::class, 'onServerShutdown');

    }

    public function handler(Request $request, Response $response) {
        if ($request->path() == '/seal________push') {
            $secret = $request->get('push_secret');
            if (self::$push_secret != $secret) {
                $result = ['success' => false, 'message' => 'secret error'];
                $response->setContent(json_encode($result));
                $response->send();
                return true;
            }
            $body = $request->body();
            $old = FileUtil::readFile(Seal::SEAL_FILE);
            if ($body == $old) {
                $result = ['success' => true, 'msg' => 'config no change'];
                $response->setContent(json_encode($result));
                $response->send();
                return true;
            }
            FileUtil::writeFile(Seal::SEAL_FILE, $body);
            if (IS_SWOOLE) {
                //重启所有 worker
                /* @var $app Application */
                $app = Ioc::get(Application::class);
                $app->server->reload();
            }
            $result = ['success' => true];
            $response->setContent(json_encode($result));
            $response->send();
            return true;
        }

    }

    public function onServerShutdown() {

        $config = Config::getFileConfig();
        $seal = $config[ 'seal' ];
        $port = '9501';
        if ($seal[ 'port' ]) {
            $port = $seal[ 'port' ];
        }
        $secret = $seal[ 'secret' ];
        if (ServerInfo::$SEAL_SECRET) {
            $secret = ServerInfo::$SEAL_SECRET;
        }
        $data = ['app_name' => $seal[ 'app_name' ],
                 'secret' => $secret,
                 'client_name' => $config[ 'app' ][ 'name' ] . '(' . self::$local_ip . ')',];
        try {
            //注销配置
            \Unirest\Request::post('http://' . $seal[ 'host' ] . ':' . $port . '/api/unRegister', [], $data);
        } catch (\Exception $exception) {

        }

    }

    /***
     * 加载配置
     */
    public static function loadConfig() {
        $result = FileUtil::readFile(Seal::SEAL_FILE);
        if ($result) {
            return json_decode($result, true);
        }
        return [];

    }

}