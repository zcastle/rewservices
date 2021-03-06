<?php

$app->group('/caja', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->caja as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/:id', function($id) use ($app, $db, $result) {
		$rows = $db->caja->select('id, nombre, tipo, centrocosto_id ')->where('id', $id);
		if ($row=$rows->fetch()) {
			$row['centrocosto_name'] = utf8_encode($row->centrocosto['nombre']);
			$row['empresa_name'] = utf8_encode($row->centrocosto->empresa['nombre_comercial']);
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/cierre/:caja_id', function($caja_id) use ($app, $db, $result) {
		$rowCC = $db->caja->select('centrocosto_id ')->where('id', $caja_id)->fetch();
		$rows = $db->caja('centrocosto_id', $rowCC["centrocosto_id"]);
		$diaActual = $rows->fetch()["dia"];
		$rows->update(array(
			'dia' => $diaActual+1
		));
	    $app->response()->write(json_encode($result));
	});

	$app->get('/tablet/:nombre', function($nombre) use ($app, $db, $result) {
		$rows = $db->caja->where('nombre', $nombre);
		if ($row=$rows->fetch()) {
			$row['cc_codigo'] = $row->centrocosto['codigo'];
			$row['cc_nombre'] = utf8_encode($row->centrocosto['nombre']);
			$row['cc_direccion'] = utf8_encode($row->centrocosto['direccion']);
			$row['cc_distrito'] = $row->centrocosto->ubigeo['nombre'];
			$row['cc_empresa_id'] = $row->centrocosto['empresa_id'];

			$row['e_codigo'] = $row->centrocosto->empresa['codigo'];
			$row['e_ruc'] = $row->centrocosto->empresa['ruc'];
			$row['e_razon'] = utf8_encode($row->centrocosto->empresa['razon_social']);
			$row['e_nombre'] = utf8_encode($row->centrocosto->empresa['nombre_comercial']);
			$row['e_direccion'] = utf8_encode($row->centrocosto->empresa['direccion']);
			$row['e_distrito'] = $row->centrocosto->empresa->ubigeo['nombre'];
			$row['e_igv'] = $db->impuesto->where('nombre','IGV')->fetch()['valor'];
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});

});

?>