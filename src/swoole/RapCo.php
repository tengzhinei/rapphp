<?php


namespace rap\swoole;


use rap\swoole\coroutine\CoroutineOrganizer;
use rap\swoole\coroutine\CoroutineOrganizerDowngrade;
use rap\swoole\coroutine\ICoroutineOrganizer;

/**
 * 协程
 *
 * @author: 藤之内
 */
class RapCo {



    /***
     * @return ICoroutineOrganizer
     */
    private static function service() {
        return IS_SWOOLE ? new CoroutineOrganizer() : new CoroutineOrganizerDowngrade();
    }

    /**
     * 执行协程
     *
     * @param \Closure $closure 需要执行的回调
     *
     * @return ICoroutineOrganizer
     */
    public static function go(\Closure $closure) {
        $service = self::service();
        return $service->go($closure);

    }

    /**
     * 产生一个分组
     * 下面所有本类创建的协程都会在同一分组
     * @return ICoroutineOrganizer
     */
    public static function group() {
        $service = self::service();
        return $service->group();
    }

    /**
     * 执行协程 并且带上当前 Context
     *
     * @param \Closure $closure 需要执行的回调
     *
     * @return ICoroutineOrganizer
     */
    public static function goWithContext(\Closure $closure) {
        $service = self::service();
        return $service->goWithContext($closure);
    }

}