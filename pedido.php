<?php 

$app->group('/pedido', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $result) {
        $app->response()->write(json_encode($result));
    });

    $app->get('/:nroatencion/:caja_id', function($nroatencion, $cajaId) use ($app, $db, $result) {
    	$cc_id = $db->caja('id', $cajaId)->fetch()->centrocosto['id'];
        $cajaId = $db->caja('centrocosto_id', $cc_id)->and('tipo', 'C')->fetch()['id'];
    	$rows = $db->atenciones->where('nroatencion', $nroatencion)->and('caja_id', $cajaId);
    	foreach ($rows as $row) {
            $row['cliente_name'] = $db->cliente->where('id', $row['cliente_id'])->fetch()['nombre'];
            $row['descuento_tipo_name'] = $row->descuento_tipo['nombre'];
    		array_push($result['data'], $row);
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/:id', function($id) use ($app, $db, $result) {
        //$nroatencion = $app->request->get('mesa');
        $rows = $db->atenciones->where('nroatencion', $id);
        foreach ($rows as $row) {
            $row['cliente_name'] = $db->cliente->where('id', $row['cliente_id'])->fetch()['nombre'];
            array_push($result['data'], $row);
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
            $values = (array)json_decode($app->request()->put('data'));
            $values['hijos'] = json_encode($values['hijos']);
            $row->update($values);
            $result['data'] = $values;
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
        /*$atenciones = $db->atenciones->where('nroatencion', $values->nroatencion);
        $atenciones->update(array(
            'mozo_id' => $values->mozo_id,
            'pax' => $values->pax
        ));*/
        $app->response()->write(json_encode($result));
    });

    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $values->id = null;
        $values->fechahora = new NotORM_Literal("NOW()"); //date("Y-m-d H:i:s");
        $cc_id = $db->caja('id', $values->caja_id)->fetch()->centrocosto['id'];
        $values->caja_id = $db->caja('centrocosto_id', $cc_id)->and('tipo', 'C')->fetch()['id'];
        $create = $db->atenciones->insert((array)$values);

        array_push($result['data'], array(
            'id' => $create['id']
        ));
        //
        $atenciones = $db->atenciones->where('nroatencion', $values->nroatencion);
        $atenciones->update(array(
            'cajero_id' => $values->cajero_id
        ));
        //
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

    $app->group('/pago', function () use ($app, $db, $result) {
        $app->get('/:nroatencion/:caja_id', function($nroatencion, $cajaId) use ($app, $db, $result) {
            $rows = $db->atenciones_pagos->where('nroatencion', $nroatencion)->and('caja_id', $cajaId);
            foreach ($rows as $row) {
                $row['tarjeta_credito_name'] = $row->tarjeta_credito['nombre'];
                array_push($result['data'], $row);
            }
            $app->response()->write(json_encode($result));
        });

        $app->post('/', function() use($app, $db, $result) {
            $values = json_decode($app->request->post('data'));
            $values->id = null;
            $create = $db->atenciones_pagos->insert((array)$values);
            array_push($result['data'], array(
                'id' => $create['id']
            ));
            $app->response()->write(json_encode($result));
        });

        $app->put('/:id', function($id) use ($app, $db, $result) {
            $atenciones_pagos = $db->atenciones_pagos->where("id", $id);
            if($row=$atenciones_pagos->fetch()) {
                $values = json_decode($app->request()->put('data'));
                $edit = $row->update((array)$values);
            } else {
                $result['success'] = false;
            }
            $app->response()->write(json_encode($result));
        });

        $app->delete("/:id", function($id) use($app, $db, $result) {
            $row = $db->atenciones_pagos[$id];
            if ($row->fetch()) {
                $row->delete();
            } else {
                $result['success'] = false;
            }
            $app->response()->write(json_encode($result));
        });
    });

    $app->post('/pagar', function() use ($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $cajaId = $app->request->post('caja_id');
        $cajeroId = $app->request->post('cajero_id');
        $ticket = $app->request->post('ticket')=="true"?true:false;
        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion)->and('caja_id', $cajaId);
        if($atencion= $rowsAtencion->fetch()) {
            /*$rowsAtencion->update(array(
                'cajero_id' => $cajeroId
            ));*/
            $cajaId = $atencion['caja_id'];
            $tipoDocumentoId = 4;
            if($ticket){
                $tipoDocumentoId = 13;
            }elseif($atencion['cliente_id']>0) {
                $cliente = $db->cliente->where('id', $atencion['cliente_id'])->fetch();
                $lenRuc =strlen($cliente['ruc']);
                
                if($lenRuc==11) {
                    $tipoDocumentoId = 2;
                }/* else if($lenRuc==8) {
                    $tipoDocumentoId = 4;
                }*/
            } /*else {
                $tipoDocumentoId = 4;
            }*/
            $cajaUpdate = array();
            $serie = ''; $numero = '';
            $caja = $db->caja->where('id', $cajaId)->lock();
            $rowCaja = $caja->fetch();
            if($tipoDocumentoId==13) { //TICKET
                $serie = $rowCaja['serie_f'];
                $numero = $rowCaja['numero_t'];
                $cajaUpdate['numero_t'] = $numero+1;
            } elseif($tipoDocumentoId==2) { //FACTURA
                $serie = $rowCaja['serie_f'];
                $numero = $rowCaja['numero_f'];
                $cajaUpdate['numero_f'] = $numero+1;
            } elseif($tipoDocumentoId==4){ //BOLETA
                $serie = $rowCaja['serie_b'];
                $numero = $rowCaja['numero_b'];
                $cajaUpdate['numero_b'] = $numero+1;
            }
            if($tipoDocumentoId==13){
                $tbVenta = $db->tv->insert(array(
                    'caja_id' => $cajaId,
                    'fechahora' => new NotORM_Literal("NOW()"),
                    'dia' => $rowCaja['dia'],
                    'tipo_documento_id' => $tipoDocumentoId,
                    'serie' => $serie,
                    'numero' => $numero,
                    'cliente_id' => $atencion['cliente_id'],
                    'base' => 0,
                    'igv' => 0,
                    'servicio' => 0,
                    'total' => 0,
                    'pax' => $atencion['pax'],
                    'mozo_id' => $atencion['mozo_id'],
                    'cajero_id' => $cajeroId,
                    'nroatencion' => $atencion['nroatencion'],
                    'descuento_tipo_id' => $atencion['descuento_tipo_id'],
                    'dscto_m' => $atencion['dscto_m'],
                    'dscto_p' => $atencion['dscto_p']
                ));
            }else{
                $tbVenta = $db->venta->insert(array(
                    'caja_id' => $cajaId,
                    'fechahora' => new NotORM_Literal("NOW()"),
                    'dia' => $rowCaja['dia'],
                    'tipo_documento_id' => $tipoDocumentoId,
                    'serie' => $serie,
                    'numero' => $numero,
                    'cliente_id' => $atencion['cliente_id'],
                    'base' => 0,
                    'igv' => 0,
                    'servicio' => 0,
                    'total' => 0,
                    'pax' => $atencion['pax'],
                    'mozo_id' => $atencion['mozo_id'],
                    'cajero_id' => $cajeroId,
                    'nroatencion' => $atencion['nroatencion'],
                    'descuento_tipo_id' => $atencion['descuento_tipo_id'],
                    'dscto_m' => $atencion['dscto_m'],
                    'dscto_p' => $atencion['dscto_p']
                ));
            }
             //$atencion['cajero_id'],
            $total = 0.0;
            foreach ($rowsAtencion as $row) {
                if($tipoDocumentoId==13){
                    $db->tv_d->insert(array(
                        'venta_id' => $tbVenta['id'],
                        'producto_id' => $row['producto_id'],
                        'producto_name' => $row['producto_name'],
                        'cantidad' => $row['cantidad'],
                        'precio' => $row['precio'],
                        'mensaje' => $row['mensaje'],
                        'hijos' => $row['hijos']
                    ));
                }else{
                    $db->venta_detalle->insert(array(
                        'venta_id' => $tbVenta['id'],
                        'producto_id' => $row['producto_id'],
                        'producto_name' => $row['producto_name'],
                        'cantidad' => $row['cantidad'],
                        'precio' => $row['precio'],
                        'mensaje' => $row['mensaje'],
                        'hijos' => $row['hijos']
                    ));
                }
                $total += $row['cantidad'] * $row['precio'];
            }

            $vIgv = $db->impuesto->select('valor')->where('nombre', 'IGV')->fetch()['valor'];
            $vServ = $rowCaja->centrocosto['servicio'];

            $base = ($vIgv+$vServ)>0?$total/((($vIgv+$vServ)/100)+1):$total;
            $igv = $vIgv>0?$base*($vIgv/100):0;
            $servicio = $vServ>0?$base*($vServ/100):0;

            $tbVenta->update(array(
                'base' => $base,
                'igv' => $igv,
                'servicio' => $servicio,
                'total' => $total
            ));
            $rowsPagos = $db->atenciones_pagos->where('nroatencion', $nroatencion);
            if($rowsPagos->fetch()){
                foreach ($rowsPagos as $row) {
                    $db->venta_pagos->insert(array(
                        'venta_id' => $tbVenta['id'],
                        //'tipopago' => 'SOLES',
                        'moneda_id' => $row['moneda_id'],
                        'tarjeta_credito_id' => $row['tarjeta_credito_id'],
                        'valorpago' => $row['valorpago'],
                        'tipocambio' => $row['tipocambio'] == null ? 0 : $row['tipocambio']
                    ));
                }
            } else {
                if($tipoDocumentoId==13){
                    $db->tv_p->insert(array(
                        'venta_id' => $tbVenta['id'],
                        //'tipopago' => 'SOLES',
                        'moneda_id' => 1,
                        'tarjeta_credito_id' => 1,
                        'valorpago' => $total
                    ));
                }else{
                    $db->venta_pagos->insert(array(
                        'venta_id' => $tbVenta['id'],
                        //'tipopago' => 'SOLES',
                        'moneda_id' => 1,
                        'tarjeta_credito_id' => 1,
                        'valorpago' => $total
                    ));
                }
            }
            $rowsPagos->delete();
            $rowsAtencion->delete();
            $rowCaja->update($cajaUpdate);
            $result['data']['id'] = $tbVenta['id'];
        } else {
            $result['success'] = false;
        }

        $app->response()->write(json_encode($result));
    });

    $app->get('/precuenta/:cajaId/:nroatencion', function($cajaId, $nroatencion) use($app, $db, $result) {
        $atenciones = $db->atenciones->where('caja_id', $cajaId)->and('nroatencion', $nroatencion);
        $db->atenciones_p->insert($atenciones);
        $app->response()->write(json_encode($result));
    });

    $app->post('/liberar/:adminId', function($adminId) use($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
        if($atencion=$rowsAtencion->fetch()){
            $caja = $db->caja->where('id', $atencion['caja_id']);
            $rowCaja = $caja->fetch();
            
            $tbLiberado = $db->liberado->insert(array(
                'fecha' => new NotORM_Literal("NOW()"),
                'nroatencion' => $atencion['nroatencion'],
                'dia' => $rowCaja['dia'],
                'mozo_id' => $atencion['mozo_id'],
                'cajero_id' => $atencion['cajero_id'],
                'admin_id' => $adminId,
                'caja_id' => $atencion['caja_id']
            ));
            $total = 0.0;
            foreach ($rowsAtencion as $row) {
                $db->liberado_detalle->insert(array(
                    'liberado_id' => $tbLiberado['id'],
                    'producto_id' => $row['producto_id'],
                    'producto_name' => $row['producto_name'],
                    'precio' => $row['precio'],
                    'cantidad' => $row['cantidad']
                ));
                $total += $row['precio'] * $row['cantidad'];
            }
            $db->liberado->where('id', $tbLiberado['id'])->update(array(
                'total' => $total
            ));

            $rowsAtencion->delete();
            $result['data']['id'] = $tbLiberado['id'];
        }
        $app->response()->write(json_encode($result));
    });
    
    $app->group('/resumen', function () use ($app, $db, $result) {

        $app->get('/cia/:cia', function($cia) use($app, $db, $result) {
            $rowsCC = $db->centrocosto->select('id, nombre')->where("visible", "S");
            foreach ($rowsCC as $row) {
                $caja = $db->caja->select("dia")->where('centrocosto_id', $row['id'])->and('tipo', 'C')->fetch();
                $rowsVentas = $db->venta->select("total")->where("dia", $caja['dia'])->and("anulado_id", 0);
                $pedido = 0;
                if($rowsVentas->fetch()) {
                    $pedido = $rowsVentas->sum('total');
                }
                array_push($result['data'], array(
                    'id' => $row['id'],
                    'centrocosto_name' => $row['nombre'],
                    'pedido' => $pedido
                ));
            }
            $app->response()->write(json_encode($result));
        });

        $app->get('/demo', function() use($app, $db, $result) {
            $cc = $db->caja->select("centrocosto_id")->where("id", 2)->fetch()["centrocosto_id"];
            echo $cc;
            /*$rowsAtencion = $db->atenciones
                            ->select("'Atenciones:' AS usuario, SUM(cantidad*precio) AS total")
                            ->where('caja.centrocosto_id=?', $cc)
                            ->group("cajero_id");*/
            //echo $rowsAtencion;
        });

        $app->get('/cc/:cajaId/:cajeroId', function($cajaId, $cajeroId) use($app, $db, $result) {
            $common = new Common($db, $result);
            $result = $common->getResumen($cajaId);
            $app->response()->write(json_encode($result));
        });

    });

    $app->post('/cambiar', function() use($app, $db, $result){
        $nroatencion = $app->request->post('nroatencion');
        $nrodestino = $app->request->post('nrodestino');
        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
        if($rowsAtencion->fetch()){
            $rowsAtencionDestino = $db->atenciones->where('nroatencion', $nrodestino);
            if($rowsAtencionDestino->fetch()){
                $result['success'] = false;
                $result['error'] = 'destinoexiste';
            } else {
                $rowsAtencion->update(array('nroatencion' => $nrodestino));
            }
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/unir', function() use($app, $db, $result){
        $nroatencion = $app->request->post('nroatencion');
        $nrodestino = $app->request->post('nrodestino');
        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
        if($row=$rowsAtencion->fetch()){
            $rowsAtencionDestino = $db->atenciones->where('nroatencion', $nrodestino);
            if($rowD=$rowsAtencionDestino->fetch()){
                $mozo_id = $rowD['mozo_id'];
                $pax = $row['pax'] + $rowD['pax'];
                $cajero_id = $rowD['cajero_id'];
                $rowsAtencion->update(array(
                    'nroatencion' => $nrodestino,
                    'mozo_id' => $mozo_id,
                    'pax' => $pax,
                    'cajero_id' => $cajero_id
                ));
            } else {
                $result['success'] = false;
                $result['error'] = 'destinovacio';
            }
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/actualizar', function() use($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $cajaId = $app->request->post('caja_id');
        $mozoId = $app->request->post('mozo_id');
        $pax = $app->request->post('pax');

        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion)->and('caja_id', $cajaId);
        if($rowsAtencion->fetch()){
            $rowsAtencion->update(array(
                'mozo_id' => $mozoId,
                'pax' => $pax
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/actualizar/debug', function() use($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $cajaId = $app->request->post('caja_id');
        $cajeroId = $app->request->post('cajero_id');

        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion)->and('caja_id', $cajaId);
        if($rowsAtencion->fetch()){
            $rowsAtencion->update(array(
                //'caja_id' => $cajaId,
                'cajero_id' => $cajeroId
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->post('/actualizar/cliente', function() use($app, $db, $result) {
        $nroatencion = $app->request->post('nroatencion');
        $clienteId = $app->request->post('clienteId');

        $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
        if($rowsAtencion->fetch()){
            $rowsAtencion->update(array(
                'cliente_id' => $clienteId
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->group('/print', function () use ($app, $db, $result) {
        $app->post('/precuenta', function() use($app, $db, $result) {
            $nroatencion = $app->request->post('nroatencion');
            $rowsAtencion = $db->atenciones->where('nroatencion', $nroatencion);
            if($rowsAtencion->fetch()){
                $rowsAtencion->update(array(
                    'print' => 'S'
                ));
            }
            $app->response()->write(json_encode($result));
        });
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

});

?>