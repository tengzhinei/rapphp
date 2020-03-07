<?php


namespace rap\web\response;


use rap\web\Response;

interface ResponseBody {

    public function beforeSend(Response $response);

}