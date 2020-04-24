<?php


namespace rap\util\rate_limit;

use Psr\Log\InvalidArgumentException;

use rap\cache\Cache;

/**
 * 二级限速器
 * 使用了适配器模式
 * 该工具依赖redis
 * 第一级 主要用于限制入口流量
 * 第二级 主要用于限制同时操作主流程的人数
 * @author: 藤之内
 */
class RateLimiter implements IRateLimiter {


    /**
     * @var IRateLimiter
     */
    private $limiter;


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
        try{
            $redis = Cache::redis();
            if ($redis) {
                $this->limiter = new RedisRateLimiter($name, $time, $count, $time2, $count2);
            } else {
                $this->limiter = new EmptyRateLimter($name, $time, $count, $time2, $count2);
            }
        }finally{
            Cache::release();
        }


    }

    /**
     * 获取令牌
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    public function get($id = '') {
        return $this->limiter->get($id);
    }
    /**
     * 删除令牌
     * 当用户已经在规定时间内完成任务了,应该主动删除令牌,让更多人进来
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     */
    public function remove($id = '') {
        $this->limiter->remove($id);
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
        return $this->limiter->check($id);
    }


}