<?php

namespace rap\util\bean\field;

class Json implements MType
{
    private $data;

    public function __construct(mixed $data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->data = $data;
    }

    public function dbValue()
    {
        return json_encode($this->data);
    }

    public function value()
    {
        return $this->data;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return $this->dbValue();
    }


}
