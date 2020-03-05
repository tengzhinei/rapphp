<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/11/28
 * Time: 下午3:02
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole\pool;

use rap\cache\RedisCache;
use rap\ioc\Ioc;
use rap\swoole\CoContext;
use Swoole\Coroutine\Channel;

class ResourcePool
{


    public $channels = [];
    public $buffers = [];

    public $pool_config = [];
    private static $instance;

    private function __construct()
    {
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * 获取对象
     *
     * @param          $classOrName
     * @throws
     * @return mixed
     */
    public function get($classOrName)
    {
        $bean = null;
        $bean = CoContext::getContext()->get($classOrName);
        if ($bean) {
            return $bean;
        }
        if (IS_SWOOLE) {
            /* @var $channel Channel */
            $channel = $this->channels[$classOrName];
            $timeout = $this->pool_config[$classOrName]['timeout'];
            /* @var $buffer PoolBuffer */
            $buffer = $channel->pop($timeout);
            if (!$buffer) {
                throw new PoolTimeoutException("pool is empty and get item timeout");
            }
            $bean = $buffer->get();
        } else {
            $bean = Ioc::get($classOrName);
        }
        CoContext::getContext()->set($classOrName, $bean);
        return $bean;
    }



    public function release(PoolAble $bean)
    {
        if (!IS_SWOOLE) {
            return;
        }

        /* @var $bean PoolTrait */
        if ($bean->_poolLock_) {
            return;
        }
        CoContext::getContext()->remove($bean->_poolName_);
        /* @var $channel Channel */
        $channel = $this->channels[$bean->_poolName_];
        if ($bean->_poolBuffer_) {
            $bean->_poolBuffer_->active();
            $channel->push($bean->_poolBuffer_);
        }
    }

    /**
     * 加锁防止被子方法里的代码释放资源
     *
     * @param PoolAble $bean
     */
    public function lock(PoolAble $bean)
    {
        /* @var $bean PoolTrait */
        $bean->_poolLock_ = true;
    }

    /**
     * 释放锁
     *
     * @param PoolAble $bean
     */
    public function unLock(PoolAble $bean)
    {
        /* @var $bean PoolTrait */
        $bean->_poolLock_ = false;
    }


    /**
     * 初始化连接池
     *
     * @param string $classOrName 类名或在 Ioc中注册过的别名
     */
    public function preparePool($classOrName)
    {

        /* @var $bean PoolAble|PoolTrait */
        $bean = Ioc::beanCreate($classOrName, false);
        $config = $bean->poolConfig();

        $bean->_poolName_ = $classOrName;
        $max = $config['max'];
        $min = $config['min'];
        if (!$config['timeout']) {
            $config['timeout'] = 0.5;
        }
        $this->pool_config[$classOrName] = $config;
        $chanel = new Channel($max + 1);
        $this->channels[$classOrName] = $chanel;
        $buffers = [];
        for ($i = 0; $i < $max; $i++) {
            $buffer = new PoolBuffer($classOrName);
            $chanel->push($buffer);
            $buffers[] = $buffer;
        }
        $this->buffers[$classOrName] = $buffers;
        //定时删除
        $idle = $config['idle'];
        if (!$idle) {
            $idle = 60;
        }
        $check = $config['check'];
        if (!$check) {
            $check = 30;
        }

        if ($max==$min) {
            return;
        }
        swoole_timer_tick(1000 * $check, function () use ($idle, $max, $min) {
            $removed=0;
            foreach ($this->buffers as $class => $buffer_array) {
                if ($removed>$max-$min-1) {
                    return;
                }
                foreach ($buffer_array as $buffer) {
                    /* @var $buffer PoolBuffer */
                    if ($buffer->is_use) {
                        continue;
                    }
                    $time = time() - $buffer->lastActiveTime;
                    if ($time > $idle) {
                        $bean=$buffer->bean;
                        $buffer->bean = null;
                        unset($bean);
                        $removed++;
                    }
                }
            }
        });
    }
}
