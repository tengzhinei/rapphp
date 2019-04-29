<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/4/10 3:21 PM

 */

namespace rap\session;


use rap\cache\Cache;
use rap\web\Request;
use rap\web\Response;

class StorageSession implements Session{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * SwooleSession constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response){
        $this->request = $request;
        $this->response = $response;
    }


    public function sessionId(){
        $sessionId=$this->request->cookie('PHPSESSID');
        if(!$sessionId){
            $sessionId=md5(uniqid());
            $this->response->cookie('PHPSESSID',$sessionId);
        }
        return $sessionId;
    }

    public function start(){

    }

    public function pause(){

    }

    public function set($key, $value){
        $session_key='php_session'.self::sessionId();
        $session = Cache::get($session_key,[]);
        $session[$key]=$value;
        Cache::set($session_key,$session,-1);
    }

    public function get($key){
        $session_key='php_session'.self::sessionId();
        $session = Cache::get($session_key,[]);
        return  $session[$key];
    }

    public function del($key){
        $session_key = 'php_session' . self::sessionId();
        $session = Cache::get($session_key, []);
        unset($session[$key]);
        Cache::set($session_key,$session,-1);
    }

    public function clear(){
        $session_key = 'php_session' . self::sessionId();
        Cache::remove($session_key);
    }


}