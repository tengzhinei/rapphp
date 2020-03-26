<?php


namespace rap\web\response;


use rap\ioc\Ioc;
use rap\web\mvc\View;
use rap\web\Response;

class Html implements ResponseBody {

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
        $view = Ioc::get(View::class);
        $content = $view->fetch($this->file_index, $response->data());
        $response->setContent($content);
    }


}