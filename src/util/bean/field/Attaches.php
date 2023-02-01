<?php

namespace rap\util\bean\field;

use rap\util\bean\AttachHelper;

class Attaches implements MType
{
    protected ?array $data;

    public function __construct(mixed $val)
    {
        if (is_string($val)) {
            $val = json_decode($val, true);
        }
        $data = [];
        foreach ($val as $item) {
            if ($item['url']) {
                $data[] = $item;
            }
        }
        $this->data = $data;
    }

    public function value()
    {
        $data = [];
        foreach ($this->data as $item) {
            $item['url'] = AttachHelper::fromIoc()->urlFix($item['url']);
            $data[] = $item;
        }
        return $data;
    }


    public function dbValue()
    {
        $data = [];
        foreach ($this->data as $item) {
            $item['url'] = AttachHelper::fromIoc()->urlClear($item['url']);
            $data[] = $item;
        }
        return json_encode($data);
    }

    public function jsonSerialize()
    {
        $data = [];
        foreach ($this->data as $item) {
            $item['url'] = AttachHelper::fromIoc()->urlFix($item['url']);
            $data[] = $item;
        }
        return $data;
    }

    /**
     * 删除附件
     */
    public function delete()
    {
        foreach ($this->data as $item) {
            $url = $item['url'];
            $url = AttachHelper::fromIoc()->urlClear($url);
            AttachHelper::fromIoc()->delete($url);
        }
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }

}

