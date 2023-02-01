<?php

namespace rap\util\bean;

use Closure;


class ArrayDefer
{
    /**
     * @var ArrayDeferInfo[]
     */
    private $defers = [];

    /**
     * @param mixed $field 参数
     * @param $defer_list Closure(array $fields)   $fields 为所有列表取出来的
     * @param $defer_item Closure($item,$index)
     */
    public function __construct($field, Closure $defer_list, Closure $defer_item)
    {
        $this->defers[] = new ArrayDeferInfo($field, $defer_list, $defer_item);
    }

    public function and($field, Closure $defer_list, Closure $defer_item)
    {
        $this->defers[] = new ArrayDeferInfo($field, $defer_list, $defer_item);
        return $this;
    }


    /**
     * @return ArrayDeferInfo[]
     */
    public function getDefers()
    {
        return $this->defers;

    }

}
