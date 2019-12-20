<?php
namespace rap\cache;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 上午10:19
 */
interface CacheInterface {
    /**
     * 设置缓存
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire -1 永不过期 0默认配置
     */
    public function set($key, $value, $expire);

    /**
     * 获取数据
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default);

    /**
     * 是否包含
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * 自增
     *
     * @param string $key
     * @param int    $step
     */
    public function inc($key, $step = 1);

    /**
     * 自减
     *
     * @param string $key
     * @param int    $step
     */
    public function dec($key, $step = 1);

    /**
     * 删除对应的key的缓存
     *
     * @param string $key
     */
    public function remove($key);

    /**
     * 清空
     */
    public function clear();

    /**
     * 存到hash里
     *
     * @param string $name
     * @param string $key
     * @param mixed  $value
     */
    public function hashSet($name, $key, $value);

    /**
     * 从hash里取数据
     *
     * @param  string $name
     * @param  string $key
     * @param  mixed  $default
     */
    public function hashGet($name, $key, $default);

    /**
     * 从hash删除数据
     *
     * @param string $name
     * @param string $key
     */
    public function hashRemove($name, $key);


    /**
     * 设置过期时间
     *
     * @param $key
     * @param int $ttl 过期时间单位 s
     * @return mixed
     */
    public function expire($key,$ttl);

}