<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/4/10 3:21 PM

 */

namespace rap\session;

use rap\cache\Cache;
use rap\web\Request;
use rap\web\Response;

class RedisSession implements Session
{

    const REDIS_CACHE_NAME="IOC_REDIS_CACHE_NAME";


    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;


    private $session_id;


    /**
     * SwooleSession constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }


    public function sessionId()
    {
        if (!$this->session_id) {
            $this->session_id=  $session_id=$this->request->header('x-session-id');
        }
        if (!$this->session_id) {
            $this->session_id=$this->request->cookie('PHPSESSID');
        }
        if (!$this->session_id) {
            $this->session_id = md5(uniqid());
            $this->response->cookie('PHPSESSID', $this->session_id);
        }
        return $this->session_id;
    }

    public function start()
    {
    }

    public function pause()
    {
    }

    public function set($key, $value)
    {
        $session_key='php_session'.self::sessionId();
        $session = Cache::getCache(self::REDIS_CACHE_NAME)->get($session_key, []);
        $session[$key]=$value;
        Cache::getCache(self::REDIS_CACHE_NAME)->set($session_key, $session, 60*60*24);
    }

    public function get($key)
    {
        $session_key='php_session'.self::sessionId();
        $session = Cache::getCache(self::REDIS_CACHE_NAME)->get($session_key, []);
        return  $session[$key];
    }

    public function del($key)
    {
        $session_key = 'php_session' . self::sessionId();
        $session = Cache::getCache(self::REDIS_CACHE_NAME)->get($session_key, []);
        unset($session[$key]);
        Cache::getCache(self::REDIS_CACHE_NAME)->set($session_key, $session, 60*60*24);
    }

    public function clear()
    {
        $session_key = 'php_session' . self::sessionId();
        Cache::getCache(self::REDIS_CACHE_NAME)->remove($session_key);
    }
}
