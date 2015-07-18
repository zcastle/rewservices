<?php

include_once('lib/almacen.class.php');

$app->group('/guia', function() use ($app, $db, $result, $almacen) {

	$app->get('/cabecera', function() use ($app, $db, $result) {
		$guias = $db->guia()->order('fecha DESC, id DESC');
		foreach ($guias as $row) {
			$row['fecha'] = date("d/m/Y", strtotime($row['fecha']));
			$row['tipo_documento_name'] = $row->tipo_documento['nombre'];
			$row['tipo_operacion_name'] = $row->tipo_operacion['nombre'];
			$row['cliente_ruc'] = $row->cliente['ruc'];
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

	$app->post('/cabecera/exist', function() use ($app, $db, $result){
		$values = $app->request->post();
		$existGuia = $db->guia->where('tipo_documento_id', $values['tipo_documento_id'])
                                ->and('serie', $values['serie'])
                                ->and('numero', $values['numero'])
                                ->and('cliente_id', $values['cliente_id']);
        if($existGuia->fetch()){
        	$result['error'] = true;
        	$result['message'] = Messages::WAR_DOCUMENTO_DUPLICADO;
        }else{
        	$result['error'] = false;
        }
        $app->response()->write(json_encode($result));
	});

	$app->post('/cabecera', function() use ($app, $db, $result) {
		$values = json_decode($app->request->post('data'));
		$values->fecha = substr($values->fecha, 6, 4).'-'.substr($values->fecha, 3, 2).'-'.substr($values->fecha, 0, 2);
		$values->registrado = new NotORM_Literal("NOW()"); //date("Y-m-d H:i:s");
	    $create = $db->guia->insert((array)$values);
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
				$almacen = new Almacen($db, $guia['id']);
				$detalle = $db->guia_detalle->where('guia_id', $guia['id']);
				$success = true;
				foreach ($detalle as $producto) {
					if(!$almacen->ingreso($producto)){
						$success = false;
						break;
					}
				}
				if($success){
					$result['success'] = $success;
					$guia->update(array('procesado' => new NotORM_Literal("NOW()")));
				}
			}
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/procesar/venta/dia/:dia', function($dia) use ($app, $db, $result) {
		$ventas = $db->venta->select('id')->where('dia', $dia)->and('anulado_id', 0);
		foreach ($ventas as $venta) {
			$almacen = new Almacen($db, $venta['id']); //Almacen::TIPO_OPERACION_VENTA
			$detalle = $db->venta_detalle->select('id, cantidad')->where('venta_id', $venta['id']);
			foreach ($detalle as $producto) {
				$producto = $db->producto->select('costo, almacen_id')->where('id', $producto['id']);
				$costo = 0; $almacenId = 1;
				if ($row = $producto->fetch()) {
					$costo = $row['costo'];
					$almacenId = $row['almacen_id'];
				}
				$almacen->salida(array(
					'id' => $producto['producto_id'],
					'cantidad' => $producto['cantidad'],
					'costo' => $costo,
					'almacenId' => $almacenId
				));
			}
			//print_r($almacen);
		}
	});

});

?>