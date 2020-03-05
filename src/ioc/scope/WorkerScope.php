<?php


namespace rap\ioc\scope;

/**
 * 在整个 worker进程类产生一个对象
 * 可以通过 worker_scope_change()方法清除所有WorkerScope作用域里的对象,以便于产生新对象
 * Interface WorkerScope
 * @package rap\ioc\scope
 */
interface WorkerScope
{

}
