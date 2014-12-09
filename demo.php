<?php 

$app->get('/demo', function () use ($app, $db, $result) {
    $caja = $db->caja->where('id', 2); //->lock();
    $rowCaja = $caja->fetch();
    $result['res'] = $rowCaja->centrocosto['servicio'];
    $app->response()->write(json_encode($result));
});

?>