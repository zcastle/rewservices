<?php

$app->group('/util', function() use ($app, $db, $result) {

	$app->get('/', function() use ($app, $result) {
	    $app->response()->write('UTIL');
	});

	$app->get('/ruc/:ruc', function($ruc) use ($app, $result) {
		$contenido = trim(file_get_contents("http://www.sunat.gob.pe/w/wapS01Alias?ruc=$ruc"));
		$d =  new SimpleXMLElement($contenido);
	    $i = 1;
	    $ruc='';$razon='';$estado='';$comercial='';$direccion='';
		foreach ($d->card->p->small as $valor) {
			if(trim($valor->b)=='El numero Ruc ingresado es invalido.') {
				$result['success'] = false;
				$ruc = trim($valor->b);
				break;
			} else {
				if($i==1) {
					$ruc = substr(trim($valor), 0, 11);
					$razon = substr(trim($valor), 14);
				} else if ($i==4) {
					$estado = trim($valor);
				} else if ($i==6) {
					$comercial = trim($valor);
				} else if ($i==7) {
					$direccion = trim($valor);
				}
				$i+=1;
			}
		}
		array_push($result['data'], array(
			'ruc' => $ruc,
			'razon' => $razon,
			'nombrecomercial' => $comercial,
			'direccion' => $direccion,
			'estado' => $estado
		));
	    $app->response()->write(json_encode($result));
	});

});

?>