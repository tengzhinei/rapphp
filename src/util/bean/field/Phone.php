<?php

namespace rap\util\bean\field;

use rap\util\EncryptUtil;


/**
 * 手机号 入库显示加密数据,前端显示为 176****9852格式
 */
class Phone implements MType
{
    public function __construct(mixed $data)
    {
        if (is_int($data)) {
            $data = (string)$data;
        }
        if (!is_string($data)) {
            return;
        }
        if (strpos($data, 'encrypt_') !== 0) {
            $data = 'encrypt_' . EncryptUtil::encrypt($data, 'duohuo');
        }
        $this->encrypt_value = $data;
    }

    public function dbValue()
    {
        return $this->encrypt_value;
    }

    public function value()
    {
        if (strpos($this->encrypt_value, 'encrypt_') !== 0) {
            return '';
        }
        return EncryptUtil::decrypt(substr($this->encrypt_value, 8), 'duohuo');
    }

    public function jsonSerialize()
    {
        $val = $this->value();
        if (strlen($val) > 10) {
            return substr($val, 0, 3) . '****' . substr($val, 7);
        }
        return $val;
    }

    public function __toString(): string
    {
        return $this->value();
    }

}
