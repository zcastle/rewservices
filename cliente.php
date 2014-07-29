<?php

$app->group('/cliente', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->cliente_proveedor as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/:id', function($id) use ($app, $db, $result) {
		$rows = $db->cliente_proveedor->where('id', $id);
		if ($row=$rows->fetch()) {
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/ruc/:ruc', function($ruc) use ($app, $db, $result) {
		$rows = $db->cliente_proveedor->where('ruc', $ruc);
		if ($row=$rows->fetch()) {
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->post('/', function() use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $values->id = null;
        $create = $db->cliente_proveedor->insert((array)$values);
        array_push($result['data'], array(
            'id' => $create['id']
        ));
        $app->response()->write(json_encode($result));
    });

    $app->put('/:id', function($id) use ($app, $db, $result) {
        $cliente = $db->cliente_proveedor->where("id", $id);
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