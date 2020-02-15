<?php


namespace rap\ioc\scope;
use rap\cache\Cache;
use rap\ioc\Ioc;
use rap\session\RedisSession;

/**
 * 同一个 session 内对象相同,默认有效期为 30 min
 * 实现SessionScope的类必须可以serialize
 * @author: 藤之内
 */
class SessionScope {


    /**
     * 获取对象
     * @return mixed
     */
    public static function instance(){
        return Ioc::get(get_called_class());

    }

    /**
     * 保存对象
     */
    public function save(){
        $request=request();
        if(!request()){
            exception('request不存在');
        }
        $session_id = $request->session()->sessionId();
        $cache_key = 'scope_session_' . get_called_class() . $session_id;
        $cache = Cache::getCache(RedisSession::REDIS_CACHE_NAME);
        $cache->set($cache_key, $this, 60 * 30);
    }

}