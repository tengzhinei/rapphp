<?php

namespace rap\util\bean;

use Closure;

class ArrayDeferInfo
{

    /**
     * @var mixed
     */
    public $field;

    /**
     * @var Closure
     */
    public $defer_list;

    /**
     * @var Closure
     */
    public $defer_item;

    /**
     * @param mixed $field
     * @param Closure $defer_list
     * @param Closure $defer_item
     */
    public function __construct($field, $defer_list, $defer_item)
    {
        $this->field = $field;
        $this->defer_list = $defer_list;
        $this->defer_item = $defer_item;
    }


}
