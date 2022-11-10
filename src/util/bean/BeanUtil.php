<?php


namespace rap\util\bean;


use rap\util\ArrayUtil;
use rap\web\BeanWebParse;
use rap\web\Request;

class BeanUtil {


    /**
     * 设置对象的属性
     * @param mixed                $bean
     * @param array|string|Request $data
     */
    public static function setProperty($bean, $data = []) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $class = new \ReflectionClass(get_class($bean));
        $properties = $class->getProperties();
        $define = [];
        if ($bean instanceof PropertyDefinition) {
            $define = $bean->propertyDefinition();
        }
        foreach ($properties as $property) {
            $name = $property->getName();

            if ($data instanceof Request) {
                $val = $data->param($name);
            } else if(is_object($data)) {
                $val = $data-> $name;
            }else{
                $val = $data[$name];
            }
            if (!isset($val)) {
                continue;
            }
            if ($define[ $name ]) {
                $type = $define[ $name ];
                if (is_array($type)) {
                    $rs = [];
                    $type = $type[ 0 ];
                    if (is_string($val)) {
                        $val = json_decode($val, true);
                    }
                    if (ArrayUtil::isList($val)) {
                        foreach ($val as $item) {
                            $type_bean = new $type();
                            self::setProperty($type_bean, $item);
                            $rs[] = $type_bean;
                        }
                    }
                    $bean->$name = $rs;
                } else {
                    $type_bean = new $type();
                    self::setProperty($type_bean, $val);
                    $bean->$name = $type_bean;
                }
            } else {
                $bean->$name = $val;
            }
        }
    }


    /**
     * 对象转数组
     * @param $model
     * @param string $fields
     * @param bool $contain
     * @return array
     */
    public static function toArray($model, $fields='', $contain=true)
    {
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

    private static function valueDiscern($value)
    {
        if ($value instanceof BeanWebParse) {
            $fb = $value->toJsonField();
            if (is_array($fb)) {
                return BeanUtil::toArray($value,$fb[ 0 ], $fb[ 1 ]);
            } else {
                return  BeanUtil::toArray($value,'', false);
            }
        }
        return $value;
    }


}
