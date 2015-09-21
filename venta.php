<?php

$app->group('/venta', function () use ($app, $db, $result) {

    $app->post('/sync', function() use ($app, $db, $result) {
        $data = $app->request->post('data');
        if($data){
            
        }

        $app->response()->write(json_encode($result));
    });

    function formatValue($val) {
        if ($val instanceof DateTime) {
            return $val->format("d/m/Y"); //! may be driver specific
        }
        return $val;
    }

    function getResult($rows, $result, $db) {
        foreach ($rows as $row) {
            $anular = array();
            $anular['id'] = $row["id"];
            $anular['fecha'] = formatValue(new DateTime($row["fechahora"]));;
            $anular['documento'] = substr("00".$row["serie"], -3)."-".substr("000000".$row["numero"], -7);
            $anular['cliente'] = $db->cliente[$row["cliente_id"]]['nombre'];
            $anular['anulado'] = $row["anulado_id"] == 0 ? false : true;
            array_push($result['data'], $anular);
        }
        return $result;
    }

    /*$app->get('/anular/:caja_id(/:numero)', function($caja_id, $numero=null) use ($app, $db, $result) {
        $caja = $db->caja('id', $caja_id)->fetch();
        $rows = $db->venta('dia', $caja['dia'])->and('caja_id', $caja_id);
        if($numero){
            $rows->and("numero LIKE ?", "%".$numero."%");
        }
        $app->response()->write(json_encode(getResult($rows, $result, $db)));
    });*/

    $app->get('/anular/:caja_id(/:cajero_id(/:numero))', function($caja_id, $cajero_id=null, $numero=null) use ($app, $db, $result) {
        $caja = $db->caja('id', $caja_id)->fetch();
        $rows = $db->venta('dia', $caja['dia'])->and('caja_id', $caja_id);
        if($cajero_id){
            $rows->and("cajero_id", $cajero_id);
        }
        if($numero){
            $rows->and("numero LIKE ?", "%".$numero."%");
        }
        $app->response()->write(json_encode(getResult($rows, $result, $db)));
    });

    $app->post('/anular_documento', function() use ($app, $db, $result) {
        $venta_id = $app->request->post('venta_id');
        $anulado_id = $app->request->post('anulado_id');
        $anulado_message = $app->request->post('anulado_message');
        $rows = $db->venta('id', $venta_id);
        $rows->update(array(
            'anulado' => new NotORM_Literal("NOW()"),
            'anulado_id' => $anulado_id,
            'anulado_message' => $anulado_message
        ));
        $result['success'] = true;
        $app->response()->write(json_encode($result));
    });

    $app->get('/dias', function() use ($app, $db, $result) {
        $page = $app->request->get('page');
        $start = $app->request->get('start');
        $limit = $app->request->get('limit') != null ? $app->request->get('limit') : 10;
        $rows = $db->venta->select("dia, DATE_FORMAT(MIN(fechahora), '%d/%m/%Y') AS fe_ini, DATE_FORMAT(MAX(fechahora), '%d/%m/%Y') AS fe_fin")->group('dia')->order("dia DESC")->limit($limit, $start);
        $result['count'] = $db->venta->group('dia')->count();
        foreach ($rows as $row) {
            $procesado = 'N';
            if($db->guia_salida('dia', $row['dia'])->fetch()) {
                $procesado = 'S';
            }
            array_push($result['data'], array(
                'dia' => $row['dia'],
                'fe_ini' => $row['fe_ini'],
                'fe_fin' => $row['fe_fin'],
                'procesado' => $procesado
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/anio/cia/:cia', function($cia) use ($app, $db, $result) {
        $rowsCC = $db->centrocosto->select('id, nombre');
        foreach ($rowsCC as $row) {
            $caja = $db->caja('centrocosto_id', $row['id'])->and('tipo', 'C')->fetch();
            $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
            $dias = array("lunes","martes","miercoles","jueves","viernes","sabado","domingo");
            //$ini_mes = date('n', strtotime('-5 month'));
            $fila = array(
                'id' => $row['id'],
                'centrocosto_name' => $row['nombre']
            );
            //$fin_mes = $ini_mes+6;
            $primer_dia = mktime();
            $ultimo_dia = mktime();
            while(date("w",$primer_dia)!=1){
                $primer_dia -= 3600;
            }
            while(date("w",$ultimo_dia)!=0){
                $ultimo_dia += 3600;
            }
            $fechauno = new DateTime(date("Y-m-d",$primer_dia));
            for ($i=1; $i<=7; $i++) {
                $rowsVenta = $db->venta('caja_id', $caja['id'])->and('DATE(fechahora)', $fechauno->format('Y-m-d'));
                $venta = 0;
                if($rowsVenta->fetch()){
                    $venta = $rowsVenta->sum('total');
                }
                $fila[$dias[$i-1]] = $venta;
                $fechauno->add(new DateInterval('P1D'));
            }
            
            //echo "Primer día ".date("D Y-m-d",$primer_dia)."<br>";
            //echo "Hoy ".date("D Y-m-d",mktime())."<br>";
            //echo "Ultimo día ".date("D Y-m-d",$ultimo_dia)."<br>";
            
            /*$result['primer'] = $fechauno->format('Y-m-d');
            $fechauno->add(new DateInterval('P1D'));
            $result['segundo'] = $fechauno->format('Y-m-d');
            $result['hoy'] = date("Y-m-d",mktime());
            $result['ultimo'] = date("Y-m-d",$ultimo_dia);*/

            array_push($result['data'], $fila);
        }
        $app->response()->write(json_encode($result));
    });
});

?>