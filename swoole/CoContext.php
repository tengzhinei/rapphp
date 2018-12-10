<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午3:09
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole;


use rap\swoole\pool\PoolAble;
use rap\swoole\pool\ResourcePool;
use rap\web\Request;

class CoContext {

    public $instances = [];

    private static $coHolders = [];

    /**`
     * 获取作用域的id
     * @return int
     */
    public static function id(){
         if(IS_SWOOLE){
            return \Co::getuid();
         }
         return 1;
    }

    public static function getContext() {
        $uid = 'cid_'.self::id();
        $holder = self::$coHolders[ $uid ];
        if (!$holder) {
            $holder = new CoContext();
            self::$coHolders[ $uid ] = $holder;
        }
        return $holder;
    }

    public function setRequest(Request $request) {
        $this->set('request', $request);
    }

    /**
     * 获取request
     * @return Request
     */
    public function getRequest() {
        return $this->get('request');
    }

    /**
     * 获取response
     * @return \rap\web\Response
     */
    public  function getResponse() {
        return self::getRequest()->response();
    }

    public function set($name, $bean=null) {
        if(!$bean){
            unset($bean);
            $this->instances[ $name ]=null;
        }else{
            $this->instances[ $name ] = $bean;
        }
    }

    public function get($name) {
        return $this->instances[ $name ];
    }

    public function remove($name) {
        unset($this->instances[ $name ]);
    }

    /**
     * 释放协程内资源,系统调用
     */
    public function release() {
        /* @var $pool ResourcePool */
        $pool = ResourcePool::instance();
        $id=CoContext::id();
        unset(self::$coHolders[ $id ]);
        foreach ($this->instances as $name => $bean) {
            if ($bean instanceof PoolAble) {
                $pool->release($bean);
            } else {
                unset($bean);
            }
        }
        unset($this->instances);
        $this->instances=[];
    }


}