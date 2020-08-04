<?php


namespace rap\ioc;

use rap\cache\Cache;
use rap\ioc\scope\SessionScope;
use rap\session\RedisSession;
use rap\swoole\Context;
use rap\swoole\pool\Pool;

/**
 * SessionScope获取和保存
 * @author: 藤之内
 */
class SessionScopeHelp
{


    public static function get($clazzOrName)
    {
        $request = request();
        $bean = Context::get('Ioc_' . $clazzOrName);
        if ($bean) {
            return $bean;
        }
        if (!$request) {
            $bean = Ioc::beanCreate($clazzOrName);
        } else {
            $session_id = $request->session()->sessionId();
            $cache_key = 'scope_session_' . $clazzOrName . $session_id;
            $cache = Cache::getCache(RedisSession::REDIS_CACHE_NAME);
            try{
                $bean = $cache->get($cache_key, null);
                if ($bean) {
                    $cache->expire($cache_key, 60 * 30);
                } else {
                    $bean = Ioc::beanCreate($clazzOrName);
                    $cache->set($cache_key, $bean, 60 * 30);
                }
            }finally{
                Pool::release($cache);
            }

        }
        Context::set('Ioc_' . $clazzOrName, $bean);
        return $bean;
    }


    public static function save(SessionScope $sessionScope)
    {
        $request=request();
        if (!request()) {
            exception('request不存在');
        }
        $session_id = $request->session()->sessionId();
        $cache_key = 'scope_session_' . get_class($sessionScope) . $session_id;
        $cache = Cache::getCache(RedisSession::REDIS_CACHE_NAME);
        $cache->set($cache_key, $sessionScope, 60 * 30);
    }
}
