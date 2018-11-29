<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 下午4:50
 */

namespace rap\cache;
use rap\swoole\pool\PoolAble;

/**
 * Redis 缓存
 * Class RedisCache
 * @package rap\cache
 */
class RedisCache implements CacheInterface,PoolAble  {

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
                          'pool_size'=>5,
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
        if (is_int($expire) && $expire > -1) {
            $result = $this->redis->setex($key, $expire, $value);
        } else {
            $result = $this->redis->set($key, $value);
        }
        return $result;
    }

    public function get($name, $default) {
        $this->open();
        $value = $this->redis->get($name);
        if (is_null($value)) {
            return $default;
        }
        return unserialize($value);
    }

    public function has($name) {
        $this->open();
        return $this->redis->get($name) ? true : false;
    }

    public function inc($name, $step = 1) {
        $this->open();
        return $this->redis->incrBy($name, $step);
    }

    public function dec($name, $step = 1) {
        $this->open();
        return $this->redis->decrBy($name, $step);
    }

    public function remove($name) {
        $this->open();
        return $this->redis->del($name);
    }

    public function hashSet($name, $key, $value) {
        $this->open();
        $value = serialize($value);
        $this->redis->hSet($name, $key, $value);
    }

    public function hashGet($name, $key, $default) {
        $this->open();
        $value = $this->redis->hGet($name, $key);
        if ($value === false) {
            return $default;
        }
        return unserialize($value);
    }

    public function hashRemove($name, $key) {
        $this->open();
        $this->redis->hDel($name, $key);
    }

    public function clear() {
        $this->open();
        $this->redis->flushDB();
    }

    public function poolSize() {
       return $this->options['pool'];
    }


}