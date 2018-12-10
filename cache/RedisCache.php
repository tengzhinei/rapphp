<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 下午4:50
 */

namespace rap\cache;

use rap\swoole\pool\PoolAble;
use rap\swoole\pool\PoolTrait;

/**
 * Redis 缓存
 * Class RedisCache
 * @package rap\cache
 */
class RedisCache implements CacheInterface, PoolAble {
    use PoolTrait;
    /**
     * @var \Redis
     */
    public $redis;

    protected $options = ['host' => '127.0.0.1',
                          'port' => 6379,
                          'password' => '',
                          'select' => 0,
                          'timeout' => 0,
                          'expire' => 0,
                          'pool' => [],
                          'persistent' => false];


    public function config($options = []) {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function ping() {
        if ($this->redis) {
            try {
                $this->redis->ping();
            } catch (\Exception $e) {
                $this->redis = null;
                $this->open();
            }
        } else {
            $this->open();
        }
    }

    public function connect() {
        $this->open();
    }

    public function open() {
        if (!$this->redis) {
            if (!extension_loaded('redis')) {
                throw new \BadFunctionCallException('not support: redis');
            }
            $func = $this->options[ 'persistent' ] ? 'pconnect' : 'connect';
            $this->redis = new \Redis;
            $this->redis->$func($this->options[ 'host' ], $this->options[ 'port' ], $this->options[ 'timeout' ]);

            if ('' != $this->options[ 'password' ]) {
                $this->redis->auth($this->options[ 'password' ]);
            }
            if (0 != $this->options[ 'select' ]) {
                $this->redis->select($this->options[ 'select' ]);
            }
        }
    }


    public function set($name, $value, $expire) {
        $this->open();
        if (!$expire) {
            $expire = $this->options[ 'expire' ];
        }
        $key = $name;
        //为支持对象类型 进行 serialize化
        $value = serialize($value);
        $result = null;
        if (is_int($expire) && $expire > -1) {
            try {
                $result = $this->redis->setex($key, $expire, $value);
            } catch (\RuntimeException $e) {
                $this->redis = null;
                $this->open();
                $result = $this->redis->setex($key, $expire, $value);
            }
        } else {
            try {
                $result = $this->redis->set($key, $value);
            } catch (\RuntimeException $e) {
                $this->redis = null;
                $this->open();
                $result = $this->redis->set($key, $value);
            }
        }
        return $result;
    }

    public function get($name, $default) {
        $this->open();
        $value = null;
        try {
            $value = $this->redis->get($name);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $value = $this->redis->get($name);
        }
        if (is_null($value)) {
            return $default;
        }
        return unserialize($value);
    }

    public function has($name) {
        $this->open();
        try {
            $has = $this->redis->get($name) ? true : false;
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $has = $this->redis->get($name) ? true : false;
        }
        return $has;
    }

    public function inc($name, $step = 1) {
        $this->open();
        try {
            $b = $this->redis->incrBy($name, $step);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $b = $this->redis->incrBy($name, $step);
        }
        return $b;
    }

    public function dec($name, $step = 1) {
        $this->open();
        try {
            $b = $this->redis->decrBy($name, $step);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $b = $this->redis->incrBy($name, $step);
        }
        return $b;
    }

    public function remove($name) {
        $this->open();
        try {
            $b = $this->redis->del($name);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $b = $this->redis->del($name);
        }
        return $b;
    }

    public function hashSet($name, $key, $value) {
        $this->open();
        $value = serialize($value);
        try {
            $this->redis->hSet($name, $key, $value);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $this->redis->hSet($name, $key, $value);
        }
    }

    public function hashGet($name, $key, $default) {
        $this->open();
        try {
            $value = $this->redis->hGet($name, $key);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $value = $this->redis->hGet($name, $key);
        }
        if ($value === false) {
            return $default;
        }
        return unserialize($value);
    }

    public function hashRemove($name, $key) {
        $this->open();
        try {
            $this->redis->hDel($name, $key);
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $this->redis->hDel($name, $key);
        }
    }

    public function clear() {
        $this->open();
        try {
            $this->redis->flushDB();
        } catch (\RuntimeException $e) {
            $this->redis = null;
            $this->open();
            $this->redis->flushDB();
        }
    }

    public function poolConfig() {
        return $this->options[ 'pool' ];
    }

}