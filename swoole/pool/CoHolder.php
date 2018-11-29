<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午3:09
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;


use rap\ioc\Ioc;

class CoHolder {

    public $instances = [];

    private static $coHolders = [];

    public static function getHolder() {
        $uid='1';
        if(IS_SWOOLE_HTTP){
            $uid = \Co::getuid();
        }
        $holder = self::$coHolders[ $uid ];
        if (!$holder) {
            $holder = new CoHolder();
            self::$coHolders[ $uid ] = $holder;
        }
        return $holder;
    }

    public function add($name, $bean) {
        $this->instances[ $name ] = $bean;
    }

    public function get($name) {
        return $this->instances[ $name ];
    }

    public function release() {
        /* @var $pool Pool  */
        $pool = Pool::instance();
        foreach ($this->instances as $name => $bean) {
            if ($bean instanceof PoolAble) {
                $pool->release($name,$bean);
            }else{
                unset($bean);
            }
        }
        unset($this->instances);
    }

}