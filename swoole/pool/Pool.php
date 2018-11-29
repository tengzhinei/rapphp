<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午3:02
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;



class Pool {


    private $unused = [];

    private static $instance;

    private function __construct() {

    }

    public static function instance(){
        if(!self::$instance){
            self::$instance=new self();
        }
        return self::$instance;
    }
    /**
     * 获取对象
     *
     * @param          $class
     * @param \Closure $closure
     *
     * @return mixed
     */
    public function get($class, \Closure $closure) {
        /* @var $holder CoHolder */
        $holder = CoHolder::getHolder();
        $m = $holder->get($class);
        //如果当前协程已分配对象,直接返回对象
        if ($m) {
            return $m;
        }
        //判定是否有没有使用的对象
        if ($this->unused[ $class ] && count($this->unused[ $class ])) {
            $size=count($this->unused[ $class ]);
            if($size>=1){
                /* @var $item PoolAble  */
                $item=$this->unused[ $class ][0];
                if($size<=$item->poolSize()){
                    $m = array_pop($this->unused[ $class ]);
                    $m->is_pool=true;
                }
            }
        }
        if(!$m){
            $m = $closure();
        }
        if($m instanceof PoolAble){
            $holder->add($class, $m);
        }
        return $m;
    }


    public function release($class,PoolAble  $bean) {
        $size = $bean->poolSize();
        if (!$size) {
            $size = 10;
        }
        if (count($this->unused[ $class ]) < $size) {
            $this->unused[ $class ][]=$bean;
        }else{
            unset($bean);
        }

    }

    public function test($class){
        return count($this->unused[ $class ]);
    }

}