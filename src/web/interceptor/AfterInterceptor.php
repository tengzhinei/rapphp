<?php


namespace rap\web\interceptor;


use rap\web\Request;
use rap\web\Response;

interface AfterInterceptor {


    public function afterDispatcher(Request $request, Response $response);

}