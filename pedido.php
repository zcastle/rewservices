<?php 

$app->group('/pedido', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $db, $result) {
    	$nroatencion = $app->request->get('mesa');
    	$rows = $db->atenciones->where('nroatencion', $nroatencion);
    	foreach ($rows as $row) {
    		array_push($result['data'], $row);
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/pago/:nroatencion', function($nroatencion) use ($app, $db, $result) {
        $rows = $db->atenciones_pagos->where('nroatencion', $nroatencion);
        foreach ($rows as $row) {
            array_push($result['data'], $row);
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/pago', function() use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $values->id = null;
        $create = $db->atenciones_pagos->insert((array)$values);
        array_push($result['data'], array(
            'id' => $create['id']
        ));
        $app->response()->write(json_encode($result));
    });

    $app->put('/pago/:id', function($id) use ($app, $db, $result) {
        $atenciones_pagos = $db->atenciones_pagos->where("id", $id);
        if($row=$atenciones_pagos->fetch()) {
            $values = json_decode($app->request()->put('data'));
            $edit = $row->update((array)$values);
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->delete("/pago/:id", function($id) use($app, $db, $result) {
        $row = $db->atenciones_pagos[$id];
        if ($row->fetch()) {
            $row->delete();
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/pagar', function() use ($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
        $atencion = $rowsAtencion->fetch();
        if($atencion) {
            $cajaId = $atencion['caja_id'];
            $tipoDocumentoId = $atencion['tipo_documento_id'];
            $cajaUpdate = array();
            $serie = ''; $numero = '';
            $caja = $db->caja->where('id', $cajaId)->lock();
            $rowCaja = $caja->fetch();
            if($tipoDocumentoId==1) { //FACTURA
                $serie = $rowCaja['serie_f'];
                $numero = $rowCaja['numero_f'];
                $cajaUpdate['numero_f'] = $numero+1;
            } elseif($tipoDocumentoId==3){ //BOLETA
                $serie = $rowCaja['serie_b'];
                $numero = $rowCaja['numero_b'];
                $cajaUpdate['numero_b'] = $numero+1;
            }
            $tbVenta = $db->venta->insert(array(
                'caja_id' => $cajaId,
                'fechahora' => new NotORM_Literal("NOW()"),
                'dia' => 0,
                'tipo_documento_id' => $tipoDocumentoId,
                'serie' => $serie,
                'numero' => $numero,
                'cliente_id' => $atencion['cliente_id'],
                'base' => 0,
                'igv' => 0,
                'servicio' => 0,
                'total' => 0,
                'dscto' => 0,
                'pax' => $atencion['pax'],
                'mozo_id' => $atencion['mozo_id'],
                'cajero_id' => $atencion['cajero_id'],
                'nroatencion' => $atencion['nroatencion']
            ));
            $total = 0.0;
            foreach ($rowsAtencion as $row) {
                $db->venta_detalle->insert(array(
                    'venta_id' => $tbVenta['id'],
                    'producto_id' => $row['producto_id'],
                    'producto_name' => $row['producto_name'],
                    'cantidad' => $row['cantidad'],
                    'precio' => $row['precio'],
                    'mensaje' => $row['mensaje']
                ));
                $total += $row['cantidad'] * $row['precio'];
            }
            $rowsPagos = $db->atenciones_pagos->where('nroatencion', $nroatencion);
            if($rowsPagos->fetch()){
                foreach ($rowsPagos as $row) {
                    $db->venta_pagos->insert(array(
                        'venta_id' => $tbVenta['id'],
                        'tipopago' => $row['tipopago'],
                        'valorpago' => $row['valorpago'],
                        'tipocambio' => $row['tipocambio']
                    ));
                }
            } else {
                $db->venta_pagos->insert(array(
                    'venta_id' => $tbVenta['id'],
                    'tipopago' => 'SOLES',
                    'valorpago' => $total
                ));
            }
            //$rowsPagos->delete();
            //$rowsAtencion->delete();
            $rowCaja->update($cajaUpdate);
        } else {
            $result['success'] = false;
        }

        $app->response()->write(json_encode($result));
    });

    $app->get('/tablet/', function() use ($app, $db, $result) {
        $mesa = $app->request->get('mesa');
        $rows = $db->atenciones->where('nroatencion', $mesa);
        foreach ($rows as $row) {
            array_push($result['data'], array(
                'idatencion' => $row['id'],
                'usuario' => $row['cajero_id'],
                'mozo' => $row['mozo_id'],
                'idproducto' => $row['producto_id'],
                'producto' => $row['producto_name'],
                'cantidad' => $row['cantidad'],
                'precio' => $row['precio'],
                'pax' => $row['pax'],
                'co_destino' => $row->producto->destino['id'],
                'mensaje' => utf8_encode($row['mensaje']),
                //'stado' => $row['nombre'],
                'fl_envio' => $row['enviado']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/:id', function($id) use ($app, $db, $result) {
    	$row = $db->atenciones[$id];
    	array_push($result['data'], $row);
        $app->response()->write(json_encode($result));
    });

    //PUT
    $app->put('/:id', function($id) use ($app, $db, $result) {
    	$atenciones = $db->atenciones->where("id", $id);
    	if($row=$atenciones->fetch()) {
    		$values = json_decode($app->request()->put('data'));
    		$edit = $row->update((array)$values);
    	} else {
    		$result['success'] = false;
    	}
        $app->response()->write(json_encode($result));
    });

    $app->put('/mesa/:mesa', function($mesa) use ($app, $db, $result) {
        $rows = $db->atenciones->where('nroatencion', $mesa);
        if($rows) {
            $values = json_decode($app->request()->put('data'));
            $edit = $rows->update((array)$values);
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $values->id = null;
        $values->fechahora = new NotORM_Literal("NOW()"); //date("Y-m-d H:i:s");
        $create = $db->atenciones->insert((array)$values);
        array_push($result['data'], array(
            'id' => $create['id']
        ));
        $app->response()->write(json_encode($result));
    });

    $app->delete("/:id", function ($id) use($app, $db, $result) {
        $row = $db->atenciones[$id];
        if ($row->fetch() && $row->delete()) {
            $result['delete'] = true;
        } else {
            $result['success'] = false;
            $result['delete'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->delete("/", function () use($app, $db, $result) {
        $values = json_decode($app->request()->delete('data'));
        $id = $values->id;
        $row = $db->atenciones[$id];
        $result['id'] = $values->id;
        if ($row->fetch() && $row->delete()) {
            $result['delete'] = true;
        } else {
            $result['success'] = false;
            $result['delete'] = false;
        }
        $app->response()->write(json_encode($result));
    });

});

?>