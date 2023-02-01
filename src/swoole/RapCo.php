<?php


namespace rap\swoole;

use rap\log\Log;
use rap\swoole\coroutine\CoroutineOrganizer;
use rap\swoole\coroutine\CoroutineOrganizerDowngrade;
use rap\swoole\coroutine\ICoroutineOrganizer;
use \Closure;

/**
 * 协程
 * @author: 藤之内
 */
class RapCo
{


    /***
     * @return ICoroutineOrganizer
     */
    private static function service()
    {
        return IS_SWOOLE ? new CoroutineOrganizer() : new CoroutineOrganizerDowngrade();
    }

    /**
     * 执行协程
     *
     * @param Closure $closure 需要执行的回调
     *
     * @return ICoroutineOrganizer
     */
    public static function go(Closure $closure)
    {
        $service = self::service();
        return $service->go($closure);
    }

    /**
     * 产生一个分组
     * 下面所有本类创建的协程都会在同一分组
     * @return ICoroutineOrganizer
     */
    public static function group()
    {
        $service = self::service();
        return $service->group();
    }

    /**
     * 执行协程 并且带上当前 Context
     *
     * @param Closure $closure 需要执行的回调
     *
     * @return ICoroutineOrganizer
     */
    public static function goWithContext(Closure $closure)
    {
        $service = self::service();
        return $service->goWithContext($closure);
    }


    /**
     * 迭代执行,每个item 都会在单独的协程内执行
     *
     * @param array $array 数组
     * @param Closure $closure 回调方法
     */
    public static function each($array, Closure $closure)
    {
        $group = self::group();
        foreach ($array as $item) {
            $group->goWithContext(function () use ($item, $closure) {
                $closure($item);
            });
        }
        $group->wait();
    }
}
