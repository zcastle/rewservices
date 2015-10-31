<?php

$app->group('/descuento_tipo', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->descuento_tipo->order('orden') as $row) {
			if($row['tipo']){
				if($row['tipo']=='M'){
					$row['nombre_largo'] = $row['nombre']." S/.";
				}else{
					$row['nombre_largo'] = $row['nombre']." %";
				}
			}else{
				$row['nombre_largo'] = $row['nombre'];
			}
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>