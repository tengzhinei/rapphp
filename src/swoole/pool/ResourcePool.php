<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午3:02
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;


use rap\ioc\Ioc;
use rap\swoole\CoContext;
use Swoole\Coroutine\Channel;

class ResourcePool {


    public $queues   = [];
    public $channels = [];
    public $buffers  = [];

    private static $instance;

    private function __construct() {
    }

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * 获取对象
     *
     * @param          $classOrName
     *
     * @return mixed
     */
    public function get($classOrName) {
        $bean = null;
        $bean = CoContext::getContext()->get($classOrName);
        if ($bean) {
            return $bean;
        }
        if (IS_SWOOLE) {
            /* @var $holder CoContext */
            //判定是否有没有使用的对象
            $queue = $this->getQueue($classOrName);
            if(!$queue)return null;
            if (!$queue->isEmpty()) {
                $bean = $queue->pop();
            }
            if (!$bean) {
                /* @var $channel Channel */
                $channel = $this->channels[ $classOrName ];
                /* @var $buffer PoolBuffer */
                $buffer = $channel->pop();
                $bean = $buffer->get();
            }
        } else {
            $bean = Ioc::get($classOrName);
        }
        CoContext::getContext()->set($classOrName, $bean);
        return $bean;
    }

    /**
     * @param $class
     *
     * @return \SplQueue
     */
    private function getQueue($class) {
        return $this->queues[ $class ];
    }

    public function release(PoolAble $bean) {
        if (!IS_SWOOLE) {
            return;
        }

        /* @var $bean PoolTrait */
        if ($bean->_poolLock_) {
            return;
        }
        CoContext::getContext()->remove($bean->_poolName_);
        $queue = $this->getQueue($bean->_poolName_);
        /* @var $channel Channel */
        $channel = $this->channels[ $bean->_poolName_ ];
        if ($bean->_poolBuffer_) {
            $bean->_poolBuffer_->active();
            $channel->push($bean->_poolBuffer_);
        } else {
            $queue->push($bean);
        }

    }

    /**
     * 加锁防止被子方法里的代码释放资源
     *
     * @param PoolAble $bean
     */
    public function lock(PoolAble $bean) {
        /* @var $bean PoolTrait */
        $bean->_poolLock_ = true;
    }

    /**
     * 释放锁
     *
     * @param PoolAble $bean
     */
    public function unLock(PoolAble $bean) {
        /* @var $bean PoolTrait */
        $bean->_poolLock_ = false;
    }


    /**
     * 初始化连接池
     *
     * @param string $classOrName 类名或在 Ioc中注册过的别名
     */
    public function preparePool($classOrName) {
        $queue = new \SplQueue();

        $this->queues[ $classOrName ] = $queue;
        /* @var $bean PoolAble|PoolTrait */
        $bean = Ioc::beanCreate($classOrName, false);
        $config = $bean->poolConfig();
        $bean->_poolName_ = $classOrName;
        $min = $config[ 'min' ];
        $max = $config[ 'max' ];

        $queue->push($bean);
        for ($i = 1; $i < $min; $i++) {
            $bean = Ioc::beanCreate($classOrName, false);
            $bean->_poolName_ = $classOrName;
            $queue->push($bean);
        }
        if (!$max || $max <= $min) {
            $max = $min + 1;
        }

        $chanel = new Channel($max - $min + 1);
        $this->channels[ $classOrName ] = $chanel;
        $buffers = [];
        for ($i = 0; $i < $max - $min; $i++) {
            $buffer = new PoolBuffer($classOrName);
            $chanel->push($buffer);
            $buffers[] = $buffer;
        }
        $this->buffers[ $classOrName ] = $buffers;
        //定时删除
        $idle = $config[ 'idle' ];
        if (!$idle) {
            $idle = 60;
        }
        $check = $config[ 'check' ];
        if (!$check) {
            $check = 30;
        }

        swoole_timer_tick(1000 * $check, function()use($idle) {
            foreach ($this->buffers as $class => $buffer_array) {
                foreach ($buffer_array as $buffer) {
                    /* @var $buffer PoolBuffer */
                    if ($buffer->is_use) {
                        continue;
                    }
                    $time = time() - $buffer->lastActiveTime;
                    if ($time > $idle) {
                        $buffer->bean = null;
                    }
                }
            }
        });
    }
}