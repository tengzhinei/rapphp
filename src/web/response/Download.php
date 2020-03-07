<?php


namespace rap\web\response;


use rap\web\Response;

class Download implements ResponseBody {


    private $file;
    private $file_name;

    public function __construct($file_path,$file_name='') {
        $this->file = $file_path;
        $this->file_name = $file_name;
    }

    public function beforeSend(Response $response) {
        $response->sendFile($this->file,$this->file_name);
    }
}