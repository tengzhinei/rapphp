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

    const CONNECTION_NAME   = '___CONNECTION_NAME__';
    const CONNECTION_scheme = '___CONNECTION_DB__';
    const REDIS_NAME        = '___REDIS_NAME__';
    const LOGIN_USER        = '___LOGIN_USER__';
    const REDIS_SELECT      = '___REDIS_SELECT__';

    public        $instances = [];
    public static $holder    = null;

    private static $coHolders = [];

    /**
     * 给非 swoole 环境下使用的
     * @var int
     */
    private static $id = 1;

    /**
     * 获取作用域的id
     * @return int
     */
    public static function id() {
        if (IS_SWOOLE) {
            return \Co::getuid();
        }
        return self::$id;
    }

    public static function setId($id) {
        self::$id = $id;
    }

    public static function getContext() {
        if (version_compare(swoole_version(), '4.3.0') >= 0) {
            if (!self::$holder) {
                self::$holder = new CoContext();
            }
            return self::$holder;
        };
        $uid = 'cid_' . self::id();
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
    public function getResponse() {
        return self::getRequest()->response();
    }

    public function set($name, $bean = null) {
        if (version_compare(swoole_version(), '4.3.0') >= 0) {
            \Co::getContext()[$name]=$bean;
        }else{
            if (!$bean) {
                unset($bean);
                $this->instances[ $name ] = null;
            } else {
                $this->instances[ $name ] = $bean;
            }
        }
    }

    public function get($name) {
        if (version_compare(swoole_version(), '4.3.0') >= 0) {
            return \Co::getContext()[$name];
        }else{
            return $this->instances[ $name ];
        }
    }

    public function remove($name) {
        if (version_compare(swoole_version(), '4.3.0') >= 0) {
            unset(\Co::getContext()[$name]);
        }else{
            unset($this->instances[ $name ]);
        }
    }

    /**
     * swoole 4.3以上会自动释放
     * 释放协程内资源,系统调用
     */
    public function release() {
        if (version_compare(swoole_version(), '4.3.0')< 0) {
            /* @var $pool ResourcePool */
            $pool = ResourcePool::instance();
            $id = CoContext::id();
            unset(self::$coHolders[ $id ]);
            foreach ($this->instances as $name => $bean) {
                if ($bean instanceof PoolAble) {
                    $pool->release($bean);
                } else {
                    unset($bean);
                }
            }
            unset($this->instances);
            $this->instances = [];
        }

    }


}