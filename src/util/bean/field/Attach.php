<?php

namespace rap\util\bean\field;

use rap\util\bean\AttachHelper;

class Attach implements MType
{
    protected ?array $data;

    public function __construct(mixed $val)
    {
        $data = $val;
        if (is_string($val)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            $this->data = is_string($val) ? ['url' => $val] : [];
            return;
        }
        if (is_array($data[0]) && key_exists("url", $data[0])) {
            $data = $data[0];
            if (!is_string($data['url'])) {
                $data['url'] = '';
            }
        } else if (!key_exists("url", $data)) {
            $data = [];
        }
        $this->data = $data;
    }

    public function value()
    {
        return $this->jsonSerialize();
    }

    /**
     *
     * @return bool|string
     */
    public function dbValue()
    {
        $url = $this->data['url'];
        $this->data['url'] = AttachHelper::fromIoc()->urlClear($url);
        return json_encode([$this->data]);
    }

    /**
     * 删除附件
     */
    public function delete()
    {
        $url = $this->data['url'];
        $url = AttachHelper::fromIoc()->urlClear($url);
        AttachHelper::fromIoc()->delete($url);
    }


    public function jsonSerialize()
    {
        if ($this->data && $this->data['url']) {
            $url = $this->data['url'];
            return AttachHelper::fromIoc()->urlFix($url);
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->jsonSerialize();
    }


}
