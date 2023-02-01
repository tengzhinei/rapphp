<?php

namespace rap\util\bean\field;

use app\model\SellerTeam;
use rap\util\bean\BeanUtil;
use Iterator;
use Countable;
use ArrayAccess;
use ArrayObject;
use JsonSerializable;

/**
 * @template T
 */
class ArrayList extends ArrayObject implements JsonSerializable
{
//    private array $list;

    public const CLAZZ = '';

    public function __construct($array = [], $flags = 0, $iteratorClass = "ArrayIterator")
    {
        $clazz = static::CLAZZ;
        $list = [];
        foreach ($array as $item) {
            $to = new $clazz;
            BeanUtil::copy($to, $item);
            $list[] = $to;
        }
        parent::__construct($list, $flags, $iteratorClass);
    }


    /**
     *
     * @return T[]
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }


}
