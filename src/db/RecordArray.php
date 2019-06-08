<?php


namespace rap\db;


/**
 *对象转数据帮助类
 * @author: 藤之内
 */
class RecordArray {

    public static function toArray($model, $fields, $contain) {
        $data = [];
        if (!$fields) {
            foreach ($model as $key => $value) {
                $value = self::valueDiscern($value);
                $data[ $key ] = $value;
            }
        } else {
            $keys = explode(',', $fields);
            if ($contain) {
                foreach ($keys as $key) {
                    $value = $model->$key;
                    $value = self::valueDiscern($value);
                    $data[ $key ] = $value;
                }
            } else {
                foreach ($model as $key => $value) {
                    if (!in_array($key, $keys)) {
                        $value = self::valueDiscern($value);
                        $data[ $key ] = $value;
                    }
                }
            }

        }
        return $data;
    }

    private static function valueDiscern($value) {
        if ($value instanceof Record) {
            $value = $value->jsonSerialize();
        }
        return $value;
    }
}