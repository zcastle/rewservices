<?php

require "bootstrap.php";

$recurso = $_SERVER['REQUEST_URI'];
if(strlen($recurso)>1) {
	//print_r($recurso);
	$mod = explode("/",$recurso)[1]; //1 to port, 2 to path
	$contiene = strpos($mod, '?');
	if($contiene===false){
		$mod = $mod.'.php';
	} else {
		$mod = substr($mod, 0, $contiene).'.php';
	}
	//print_r($mod);
	include_once $mod;
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