<?php


namespace rap\swoole\coroutine;


/**
 * 协程服务
 * Interface ICoroutineService
 * @package rap\swoole\coroutine
 */
interface ICoroutineOrganizer {

    /**
     *
     * 执行协程 并且带上当前 Context
     *
     * @param \Closure $closure 需要执行的回调
     *
     * @return $this
     */
    public function goWithContext(\Closure $closure);

    /**
     * 执行协程
     * @param \Closure $closure  需要执行的回调
     *
     * @return $this
     */
    public function go(\Closure $closure);

    /**
     * 产生一个分组
     * 下面所有本类创建的协程都会在同一分组,博能
     * @return $this
     */
    public function group();

    /**
     * 等待分组内的协程全部执行完成
     * @return $this
     */
    public function wait();
}