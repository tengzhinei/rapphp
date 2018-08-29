<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:40
 */

namespace rap\swoole\web;


use rap\cache\Cache;
use rap\session\Session;

class SwooleSession implements Session{

    /**
     * @var SwooleRequest
     */
    private $request;

    /**
     * @var SwooleResponse
     */
    private $response;

    /**
     * SwooleSession constructor.
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     */
    public function __construct(SwooleRequest $request, SwooleResponse $response){
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