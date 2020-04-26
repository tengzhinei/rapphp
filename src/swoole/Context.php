<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/5
 * Time: 下午10:44
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole;

use rap\cache\CacheInterface;
use rap\db\Connection;
use rap\ioc\Ioc;
use rap\web\Application;

class Context {


    /**
     * 获取当前id
     * swoole 环境返回协程 id,其他环境为随机数,并在同一请求内相同
     * @return int
     */
    public static function id() {
        return CoContext::id();
    }




    public static function request() {
        return CoContext::getContext()->getRequest();
    }

    public static function response() {
        return CoContext::getContext()->getResponse();
    }

    public static function set($name, $bean = null) {
        CoContext::getContext()->set($name, $bean);
    }

    public static function requestParams() {
        return self::get('request_params');
    }

    public static function get($name) {
        $context = CoContext::getContext();

        return $context->get($name);
    }

    public static function remove($name) {
        CoContext::getContext()->remove($name);
    }

    public static function release() {
        CoContext::getContext()->release();
    }


    /**
     * 切换数据库连接
     *
     * @param      $connection_name
     * @param null $db
     */
    public static function useConnection($connection_name = '', $db = null) {
        if (!$connection_name) {
            $connection_name = Connection::class;
        }
        CoContext::getContext()->set(CoContext::CONNECTION_NAME, $connection_name);
        if ($db) {
            CoContext::getContext()->set(CoContext::CONNECTION_SCHEME, $db);
        }
    }

    /**
     * 切换数据库的 scheme
     *
     * @param $scheme
     */
    public static function useConnectionScheme($scheme) {
        CoContext::getContext()->set(CoContext::CONNECTION_SCHEME, $scheme);
    }

    /**
     * 切换 redis 连接
     *
     * @param      $redis_name
     * @param null $select
     */
    public static function useRedis($redis_name = '', $select = null) {
        if (!$redis_name) {
            $redis_name = CacheInterface::class;
        }
        CoContext::getContext()->set(CoContext::REDIS_NAME, $redis_name);
        if ($select !== null) {
            CoContext::getContext()->set(CoContext::REDIS_SELECT, $select);
        }
    }

    /**
     * 切换 redis 的 select
     *
     * @param $select
     */
    public static function useRedisSelect($select) {
        if ($select !== null) {
            CoContext::getContext()->set(CoContext::REDIS_SELECT, $select);
        }
    }

    /**
     * 获取所有数据
     * @return array
     */
    public static function data() {
        return CoContext::getContext()->data();
    }


    /**
     * 不使用 session 的情况
     *
     * @param null $user_id
     *
     * @return int|string|null
     */
    public static function userId($user_id = null) {
        if ($user_id) {
            self::set(CoContext::LOGIN_USER, $user_id);
            return $user_id;
        } else {
            return self::get(CoContext::LOGIN_USER);
        }
    }
}
