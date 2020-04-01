<?php


namespace rap\web\response;

use rap\config\Config;
use rap\web\Response;

class Redirect implements ResponseBody {

    private $code;

    public $url;

    /**
     * Redirect _initialize.
     *
     * @param $code
     * @param $url
     */
    public function __construct($url, $code = 302) {
        $this->code = $code;
        $this->url = $url;
    }

    public function beforeSend(Response $response) {
        $response->code($this->code);
        $response->header("location", $this->url);
        $response->send();
    }
}
