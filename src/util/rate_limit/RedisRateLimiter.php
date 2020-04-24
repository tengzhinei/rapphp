<?php


namespace rap\util\rate_limit;

use Psr\Log\InvalidArgumentException;

use rap\cache\Cache;


/**
 * 二级限速器
 * redis 实现类
 * 第一级 主要用于限制入口流量
 * 第二级 主要用于限制同时操作主流程的人数
 * @author: 藤之内
 */
class RedisRateLimiter implements IRateLimiter {

    /**
     * 一级限流器名称前缀
     */
    const RATE_LIMIT = "rate_limit:";

    /**
     * 二级限流器名称前缀
     */
    const RATE_LIMIT2 = "rate_limit2:";

    /**
     * 限流器名称
     * @var string
     */
    private $name;

    /**
     * 一级限流时间单位秒
     * @var string
     */
    private $time;

    /**
     * 一级限流允许的次数
     * @var int
     */
    private $count;


    /**
     * 二级限流时间单位秒
     * @var string
     */
    private $time2;


    /**
     * 二级限流允许的次数
     * @var int
     */
    private $count2;


    /**
     * RateLimiter __construct.
     *
     * @param string $name   限流器名称
     * @param int    $time   一级限流时间单位秒
     * @param int    $count  一级限流允许的次数
     * @param int    $time2  二级限流时间单位秒
     * @param int    $count2 二级限流允许的次数
     */
    public function __construct($name, $time = 0, $count = 0, $time2 = 0, $count2 = 0) {
        if (!$name) {
            throw new InvalidArgumentException("RateLimiter name can not be none");
        }
        $this->name = $name;
        $this->time = $time;
        $this->count = $count;
        $this->time2 = $time2;
        $this->count2 = $count2;

    }

    /**
     * 获取令牌
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    public function get($id = '') {
        if (!$id) {
            $id = request()->session()->sessionId();
        }
        $ok = $this->getRateToken(self::RATE_LIMIT . $this->name, $id, $this->time, $this->count);
        if (!$this->time2 || !$ok) {
            return $ok;
        }
        $ok = $this->getRateToken(self::RATE_LIMIT2 . $this->name, $id, $this->time2, $this->count2);
        return $ok;
    }


    /**
     * 获取令牌
     *
     * @param string $name        限速器名称
     * @param string $id          标识当前用户的身份,默认为 sessionId
     * @param string $time_limit  时间限制
     * @param string $count_limit 数量限制
     *
     * @return bool
     */
    private function getRateToken($name, $id, $time_limit, $count_limit) {
        try {
            $redis = Cache::redis();
            $time = time();
            $redis->zRemRangeByScore($name, 0, $time - $time_limit);
            $value = $redis->zScore($name, $id);
            if ($value) {
                return true;
            }
            $lua = "local count = redis.call('zCount', KEYS[1],KEYS[3],KEYS[4])";
            $lua .= " if(count==false) then  count=0 end";
            $lua .= " if(count >= tonumber(KEYS[5])) then return 0 end";
            $lua .= " redis.call('zAdd',KEYS[1],KEYS[4],KEYS[2])";
            $lua .= " return 1";
            $count = $redis->eval($lua, [$name, $id, $time - $time_limit, $time, $count_limit], 5);
            return $count;
        } finally {
            Cache::release();
        }
    }

    /**
     * 删除令牌
     * 当用户已经在规定时间内完成任务了,应该主动删除令牌,让更多人进来
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     */
    public function remove($id = '') {
        if (!$id) {
            $id = request()->session()->sessionId();
        }
        if ($this->time2) {
            $this->removeRateToken(self::RATE_LIMIT2 . $this->name, $id);
        }
        $this->removeRateToken(self::RATE_LIMIT . $this->name, $id);
    }

    /**
     * 删除令牌
     *
     * @param string $name 限速器名称
     * @param string $id   标识当前用户的身份,默认为 sessionId
     */
    private function removeRateToken($name, $id = '') {
        try {
            $redis = Cache::redis();
            $redis->zRem($name, $id);
        } finally {
            Cache::release();
        }
    }

    /**
     * 检查是否拥有令牌
     * 如果有二级限速,只会检查二级
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    public function check($id = '') {
        if (!$id) {
            $id = request()->session()->sessionId();
        }
        $check_name = $this->time2 ? self::RATE_LIMIT2 : self::RATE_LIMIT;
        $check_name .= $this->name;
        return $this->checkRateToken($check_name, $id);
    }

    /**
     * 检查是否拥有令牌
     *
     * @param string $name 限速器名称
     * @param string $id   标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    private function checkRateToken($name, $id = '') {
        $redis = Cache::redis();
        try {
            $value = $redis->zScore($name, $id);
        } finally {
            Cache::release();
        }
        return $value ? true : false;
    }


}