<?php


namespace rap\web\response;


use rap\web\Response;

class JSONBody implements ResponseBody {

    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function beforeSend(Response $response) {
        $response->contentType("application/json");
        $value = json_encode($this->data);
        $response->setContent($value);
    }

}