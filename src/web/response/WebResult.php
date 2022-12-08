<?php

namespace rap\web\response;
use JsonSerializable;
/**
 * @template T
 */
class WebResult implements JsonSerializable
{

    private $success = true;

    private $msg = '';

    /**
     * @var T
     */
    private $data = null;

    private $code = 0;

    /**
     * @param bool $success
     * @param string $msg
     * @param null $data
     */
    public function __construct(bool $success, string $msg = '', mixed $data = null, $code = 0)
    {
        $this->success = $success;
        $this->msg = $msg;
        $this->data = $data;
        $this->code = $code;
    }


    public function jsonSerialize()
    {
        $data = ['success' => $this->success];
        if ($this->msg) {
            $data['msg'] = $this->msg;
        }
        if (isset($this->data)) {
            $data['data'] = $this->data;
        }
        if ($this->code !== 0) {
            $data['code'] = $this->code;
        }
        return $data;
    }

}
