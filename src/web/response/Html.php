<?php


namespace rap\web\response;


use rap\web\Response;

class Html implements ResponseBody  {

    private $file_index;


    /**
     * Html _initialize.
     *
     * @param $index
     */
    public function __construct($index) {
        $this->file_index = $index;
    }

    public function beforeSend(Response $response) {

    }


}