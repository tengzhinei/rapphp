<?php


namespace rap\db;
use rap\util\bean\BeanUtil;

/**
 * 对象转数据帮助类
 * 已过期 请使用rap\util\bean\BeanUtil
 * @author: 藤之内
 */
class RecordArray {

    public static function toArray($model, $fields, $contain) {

        return BeanUtil::toArray($model, $fields, $contain);
    }


}
