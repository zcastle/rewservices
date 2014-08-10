<?php

$app->group('/venta', function () use ($app, $db, $result) {

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
            $anular['cliente'] = $db->cliente_proveedor[$row["cliente_id"]]['nombre'];
            $anular['anulado'] = $row["anulado_id"] == 0 ? false : true;
            array_push($result['data'], $anular);
        }
        return $result;
    }

    $app->get('/anular/:caja_id', function($caja_id) use ($app, $db, $result) {
        $caja = $db->caja('id', $caja_id)->fetch();
        $rows = $db->venta('dia', $caja['dia'])->and('caja_id', $caja_id);
        $app->response()->write(json_encode(getResult($rows, $result, $db)));
    });

    $app->get('/anular/:caja_id/:cajero_id', function($caja_id, $cajero_id) use ($app, $db, $result) {
        $caja = $db->caja('id', $caja_id)->fetch();
        $rows = $db->venta('dia', $caja['dia'])->and('caja_id', $caja_id)->and('cajero_id', $cajero_id);
        $app->response()->write(json_encode(getResult($rows, $result, $db)));
    });

    $app->post('/anular', function() use ($app, $db, $result) {
        $venta_id = $app->request->post('venta_id');
        $anulado_id = $app->request->post('anulado_id');
        $rows = $db->venta('id', $venta_id);
        $rows->update(array(
            'anulado' => new NotORM_Literal("NOW()"),
            'anulado_id' => $anulado_id
        ));
        $result['success'] = true;
        $app->response()->write(json_encode($result));
    });
});

?>