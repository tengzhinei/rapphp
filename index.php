<?php

//$linesa = file("./a");
//$linesb = file("./b");
//$result=array_diff($linesb,$linesa);
//echo json_encode($result);
//die;
$loader = require __DIR__ . '/vendor/autoload.php';
error_reporting(E_ALL& ~E_NOTICE& ~E_WARNING);
ini_set("display_errors", "On");
\rap\ioc\Ioc::bind(\rap\web\Application::class,\rap\MyApplication::class);
\rap\ioc\Ioc::get(\rap\web\Application::class)->start();
