<?php

namespace rap\util\bean\field;

use  JsonSerializable;

interface MType extends JsonSerializable
{
    /**
     * 数据构建 传入值正常是字符串或json 数组
     * @param mixed $data
     */
    public function __construct(mixed $data);

    /**
     * 获取放入数据库的值
     * @return bool|string
     */
    public function dbValue();

    /**
     * 获取真实值
     * @return mixed
     */
    public function value();

    /**
     * 获取转移为数据库的值
     * @return mixed
     */
    public function jsonSerialize();
}
