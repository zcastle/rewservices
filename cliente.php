<?php

$app->group('/cliente', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->cliente as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/:id', function($id) use ($app, $db, $result) {
		$rows = $db->cliente->where('id', $id);
		if ($row=$rows->fetch()) {
			$ubigeo = $db->ubigeo->where('id', $row['ubigeo_id'])->fetch();
			$row['ubigeo_name'] = $ubigeo['nombre'];
			$provincia = $db->ubigeo
							->where('co_departamento=? AND co_provincia=? AND co_distrito = ?', 
								array($ubigeo['co_departamento'], $ubigeo['co_provincia'], '00'))
							->fetch();
			$row['provincia_id'] = $provincia['id'];
			//$row['provincia_name'] = $provincia['nombre'];
			$departamento = $db->ubigeo
							->where('co_departamento=? AND co_provincia=? AND co_distrito = ?', 
								array($provincia['co_departamento'], '00', '00'))
							->fetch();
			$row['departamento_id'] = $departamento['id'];
			//$row['departamento_name'] = $departamento['nombre'];
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/ruc/:ruc', function($ruc) use ($app, $db, $result) {
		$rows = $db->cliente->where('ruc', $ruc);
		if ($row=$rows->fetch()) {
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/buscar/:nombre', function($nombre) use ($app, $db, $result) {
		$rows = $db->cliente->where('ruc = ? OR nombre LIKE ?', $nombre, '%'.$nombre.'%');
		foreach ($rows as $row) {
			$ubigeo = $db->ubigeo->where('id', $row['ubigeo_id'])->fetch();
			$row['ubigeo_name'] = $ubigeo['nombre'];
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->post('/', function() use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $values->id = null;
        $create = $db->cliente->insert((array)$values);
        array_push($result['data'], array(
            'id' => $create['id']
        ));
        $app->response()->write(json_encode($result));
    });

    $app->put('/:id', function($id) use ($app, $db, $result) {
        $cliente = $db->cliente->where("id", $id);
        if($row=$cliente->fetch()) {
            $values = json_decode($app->request()->put('data'));
            $edit = $row->update((array)$values);
            $result['data'] = $values;
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });
	
});

?>