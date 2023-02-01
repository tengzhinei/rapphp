<?php

namespace rap\util\bean\field;

use rap\util\EncryptUtil;

/**
 * 数据库里存加密信息,前台显示原始信息
 */
class EncryptText implements MType
{
    private string $encrypt_value = '';

    public function __construct(mixed $data)
    {
        if (!is_string($data)) {
            return;
        }
        if (strpos($data, 'encrypt_') !== 0) {
            $data = 'encrypt_' . EncryptUtil::encrypt($data,'duohuo');
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
        return EncryptUtil::decrypt(substr($this->encrypt_value, 8),'duohuo');
    }

    public function jsonSerialize()
    {
        return $this->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }

}
