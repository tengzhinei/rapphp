<?php

namespace rap\db;


use rap\util\bean\field\MType;

class BaseModel
{
    public const TABLE = "BaseModel";

    public const PK = "id";

    public function toDbArray(): array
    {
        $clazz = new \ReflectionClass($this);
        $pros = $clazz->getProperties();
        $array = [];
        foreach ($pros as $pro) {
            $name = $pro->getName();
            $val = $this->$name;
            if (isset($val)) {
                $type = $pro->getType();
                $type_name = $type->getName();
                if (is_subclass_of($type_name, MType::class)) {
                    /**@var $val MType * */
                    $val = $val->dbValue();
                }
                $array[$name] = $val;
            }

        }
        return $array;
    }
}
