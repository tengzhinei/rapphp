<?php
namespace rap\session;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/4/7
 * Time: 下午9:42
 */
class HttpSession implements Session
{

    private $start=false;
    /**
     * 生成或获取sessionId
     * @return string
     */
    public function sessionId()
    {
         $this->start();
        return session_id();
    }

    /**
     * 非 swoole 下启动后会阻塞同一 session 的访问
     */
    public function start()
    {
        if (!$this->start) {
            session_start();
            $this->start=true;
        }
    }

    /**
     * 暂停 session的 key
     */
    public function pause()
    {
        session_write_close();
        $this->start = false;
    }

    /**
     * 设置session数据
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->start();
        $_SESSION[$key]=$value;
    }

    /**
     * 获取 session 的桑珊
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        $this->start();
        return $_SESSION[$key];
    }

    /**
     * 删除 session 的数据
     * @param $key
     */
    public function del($key)
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * 清空 session
     */
    public function clear()
    {
        $this->start();
        $_SESSION = [];
    }
}
