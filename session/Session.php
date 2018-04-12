<?php
namespace rap\session;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:42
 */
interface Session {

    /**
     * 生成或获取sessionId
     * @return string
     */
    public  function sessionId();

    /**
     * 非 swoole 下启动后会阻塞同一 session 的访问
     */
    public  function start();

    /**
     * 暂停 session的 key
     */
    public  function pause();

    /**
     * 设置session数据
     * @param $key
     * @param $value
     */
    public  function set($key,$value);

    /**
     * 获取 session 的桑珊
     * @param $key
     * @return mixed
     */
    public  function get($key);

    /**
     * 删除 session 的数据
     * @param $key
     */
    public  function del($key);

    /**
     * 清空 session
     */
    public  function clear();

}