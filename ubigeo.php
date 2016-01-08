<?php

$app->group('/ubigeo', function () use ($app, $db, $result) {

	$app->get('(/:departamento_id)(/:provincia_id)(/:distrito_id)', 
			function($departamento_id=null, $provincia_id=null, $distrito_id=null) use ($app, $db, $result) {
		$ubigeo = $db->ubigeo->select('id, nombre');

		if(!is_null($departamento_id)){
			$ubigeo->where('co_departamento', 
					$db->ubigeo->select('co_departamento')->where('id', $departamento_id)->fetch()['co_departamento']
				);
			if(!is_null($provincia_id)){
				$ubigeo->and('co_provincia', 
					$db->ubigeo->select('co_provincia')->where('id', $provincia_id)->fetch()['co_provincia']
				);
				if(!is_null($distrito_id)){
					$ubigeo->and('co_distrito', 
						$db->ubigeo->select('co_distrito')->where('id', $distrito_id)->fetch()['co_distrito']
					);
				}else{
					$ubigeo->and('co_distrito != ?', '00');
				}
			}else{
				$ubigeo->and('co_provincia != ? AND co_distrito = ?', array('00', '00'));
			}
		} else {
			$ubigeo->where('co_provincia = ? AND co_distrito = ?', array('00','00'));
		}

		foreach ($ubigeo as $row) {
			//$row['nombre'] = utf8_encode($row['nombre']);
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/lima', function() use ($app, $db, $result) {
		$rows = $db->ubigeo->where(array(
			"co_departamento" => "15",
			"co_provincia" => "01",
			"NOT co_distrito" => "01"
		));
		foreach ($rows as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>