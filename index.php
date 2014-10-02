<?php

require "bootstrap.php";

$recurso = $_SERVER['REQUEST_URI'];
if(strlen($recurso)>1) {
	$mod = explode("/",$recurso);
	include_once $mod[1].'.php';
}

/*
$pathBase = dirname(__FILE__);
$files = scandir($pathBase);
foreach ($files as $file) {
    if(!is_dir($file)){
        $info = pathinfo($pathBase.DIRECTORY_SEPARATOR.$file);
        if(strtolower($info['extension'])=='php' && $file!='index.php' && $file!='bootstrap.php') {
            include_once $file;
        }
    }
}
*/

$app->get('/', function() use ($app) {
    $app->response()->write('API');
});

$app->run();

?>