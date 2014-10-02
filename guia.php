<?php

$app->group('/guia', function() use ($app, $db, $result, $almacen) {

	$app->get('/cabecera', function() use ($app, $db, $result) {
		foreach ($db->guia()->order('fecha DESC, id DESC') as $row) {
			$row['fecha'] = date("d/m/Y", strtotime($row['fecha']));
			$row['tipo_documento_name'] = $row->tipo_documento['nombre'];
			$row['tipo_operacion_name'] = $row->tipo_operacion['nombre'];
			$row['cliente_name'] = $row->cliente['nombre'];
			if($row['procesado']) {
				$row['procesado'] = date("d/m/Y", strtotime($row['procesado']));
			}
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/cabecera/buscar/:nombre', function($nombre) use ($app, $db, $result) {
		$rows = $db->guia()->where('numero LIKE ?', '%'.$nombre.'%')->order("fecha DESC");
	    $result['success'] = false;
	    foreach ($rows as $row) {
	    	$row['fecha'] = date("d/m/Y", strtotime($row['fecha']));
			$row['tipo_documento_name'] = $row->tipo_documento['nombre'];
			$row['tipo_operacion_name'] = $row->tipo_operacion['nombre'];
			$row['cliente_name'] = $row->cliente['nombre'];
	        array_push($result['data'], $row);
	        $result['success'] = true;
	    }
	    $app->response()->write(json_encode($result));
	});

	$app->post('/cabecera', function() use ($app, $db, $result) {
		$values = json_decode($app->request->post('data'));
		$values->fecha = substr($values->fecha, 6, 4).'-'.substr($values->fecha, 3, 2).'-'.substr($values->fecha, 0, 2);
		$values->registrado = new NotORM_Literal("NOW()"); //date("Y-m-d H:i:s");
	    $create = $db->guia()->insert((array)$values);
	    array_push($result['data'], array(
	    	'id' => $create['id']
	    ));
	    $app->response()->write(json_encode($result));
	});

	$app->put('/cabecera/:id', function($id) use ($app, $db, $result) {
		$rows = $db->guia()->where("id", $id);
		if ($rows->fetch()) {
			$values = json_decode($app->request->put('data'));
			$values->fecha = substr($values->fecha, 6, 4).'-'.substr($values->fecha, 3, 2).'-'.substr($values->fecha, 0, 2);
			$values->actualizado = new NotORM_Literal("NOW()"); //date("Y-m-d H:i:s");
			$rows->update((array)$values);
	    }
	    $app->response()->write(json_encode($result));
	});

	$app->get('/detalle/:guia', function($guia) use ($app, $db, $result) {
		$rows = $db->guia_detalle->where('guia_id', $guia);
		foreach ($rows as $row) {
			$row['producto_codigo'] = $row->producto['codigo'];
			$row['producto_name'] = $row->producto['nombre'];
			if($row['unidad_type']=='mayor'){
				$row['unidad_name'] = $row->unidad['mayor'];
				$row['unidad_cantidad'] = 1;
			} else {
				$row['unidad_name'] = $row->unidad['menor'];
				$row['unidad_cantidad'] = $row->unidad['cantidad'];
			}
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->post('/detalle', function() use ($app, $db, $result) {
		$values = json_decode($app->request->post('data'));
	    $create = $db->guia_detalle()->insert((array)$values);
	    array_push($result['data'], array(
	    	'id' => $create['id']
	    ));
	    $app->response()->write(json_encode($result));
	});

	$app->put('/detalle/:id', function($id) use ($app, $db, $result) {
		$rows = $db->guia_detalle()->where("id", $id);
		if ($rows->fetch()) {
			$values = json_decode($app->request->put('data'));
			$rows->update((array)$values);
		} else {
	    	$result['success'] = false;
	    }
	    $app->response()->write(json_encode($result));
	});

	$app->delete("/detalle/:id", function($id) use($app, $db, $result) {
	    $rows = $db->guia_detalle()->where("id", $id);
	    if ($rows->fetch()) {
	        $rows->delete();
	    } else {
	    	$result['success'] = false;
	    }
	    $app->response()->write(json_encode($result));
	});

	$app->get('/procesar/:id', function($id) use ($app, $db, $result, $almacen) {
		$tbGuia = $db->guia->where('id', $id);
		if($guia=$tbGuia->fetch()){
			if($guia['procesado']) {
				$result['success'] = false;
			} else {
				$almacen_id = $guia['almacen_id'];
				$detalle = $db->guia_detalle->where('guia_id', $guia['id']);
				foreach ($detalle as $producto) {
					$almacen($guia, $producto, $almacen_id);
				}
			}
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/procesar/venta/:id', function(){
		
	});

});

?>