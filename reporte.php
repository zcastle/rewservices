<?php

include_once('lib/reporte.class.php');

$struct = array(
    'titulos' => array('ventas'=>'REPORTE DE VENTAS','familias'=>'REPORTE DE VENTAS X FAMILIAS'),
    'cabecera' => array(
        'cia' => null,
        'titulo' => "REPORTE DE VENTAS",
        'del' => null,
        'al' => null
    ),
    'ventas' => array(
        'sql' => "DATE_FORMAT(fechahora, '%d/%m/%Y') AS fecha,
                    CONCAT(IF(tipo_documento_id=2,'FV','BV'),'-',MID(CONCAT('000',serie),-3),'-',MID(CONCAT('0000000',numero),-7)) AS documento,
                    cliente.ruc, cliente.nombre, IF(anulado_id>0,0,base) AS base, IF(anulado_id>0,0,igv) AS igv, 
                    IF(anulado_id>0,0,servicio) AS servicio, IF(anulado_id>0,0,total) AS total, IF(anulado_id>0,'S', '') AS anulado",
        'detalle' => array(
            array('w' => 22, 'f' => 'fecha', 't' => 'FECHA', 'n' => false),
            array('w' => 29, 'f' => 'documento', 't' => 'DOCUMENTO', 'n' => false),
            array('w' => 23, 'f' => 'ruc', 't' => 'RUC', 'n' => false),
            array('w' => 55, 'f' => 'nombre', 't' => 'CLIENTE', 'n' => false),
            array('w' => 20, 'f' => 'base', 't' => 'NETO', 'n' => true),
            array('w' => 15, 'f' => 'igv', 't' => 'IGV', 'n' => true),
            array('w' => 15, 'f' => 'servicio', 't' => 'SERV.', 'n' => true),
            array('w' => 20, 'f' => 'total', 't' => 'TOTAL', 'n' => true),
            array('w' => 6, 'f' => 'anulado', 't' => ' A', 'n' => false)
        ),
        'grupo' => null,
        'total' => array(
            'f' => 'total',
            't' => 'TOTAL VENTA S/.'
        )
    ),
    'familias' => array(
        'sql' => "producto.categoria.nombre AS categoria_name, producto_name, 
                SUM(cantidad) AS cantidad, venta_detalle.precio, (SUM(cantidad) * venta_detalle.precio) AS total",
        'detalle' => array(
            array('w' => 50, 'f' => 'categoria_name', 't' => 'CATEGORIA', 'n' => false),
            array('w' => 95, 'f' => 'producto_name', 't' => 'PRODUCTO', 'n' => false),
            array('w' => 15, 'f' => 'cantidad', 't' => 'CANTIDAD', 'n' => true),
            array('w' => 20, 'f' => 'precio', 't' => 'PRECIO', 'n' => true),
            array('w' => 20, 'f' => 'total', 't' => 'TOTAL', 'n' => true)
        ),
        'grupo' => 'categoria_name',
        'total' => array(
            'f' => 'total',
            't' => 'TOTAL VENTA S/.'
        )
    )
);

function mostrar($struct, $index, $data, $export, $c) {
    $struct['cabecera']['titulo'] = $struct['titulos'][$index];
    $struct['cabecera']['cia'] = $c['cia'];
    $struct['cabecera']['del'] = $c['del'];
    $struct['cabecera']['al'] = $c['al'];
    $reporte = new Reporte($struct, $index, $data);
    if ($export=='true') {
        $reporte->descargar();
    } else {
        $reporte->ver();
    }
}

function error($app, $msg) {
    $result = array();
    $result['success'] = false;
    $result['message'] = $msg;
    $app->response()->write(json_encode($result));
}

$app->group('/reporte', function () use ($app, $db, $struct) {

    $app->group('/ventas', function () use ($app, $db, $struct) {

        $app->get('/:dia_ini/:dia_fin(/:export)', function($dia_ini, $dia_fin, $export=false) use ($app, $db, $struct) {
            if ($dia_ini && $dia_fin) {
                $data = $db->venta->select($struct['ventas']['sql'])
                ->where('dia >= ? AND dia <= ?', $dia_ini, $dia_fin)
                ->order('fechahora, numero');
                mostrar($struct, 'ventas', $data, $export, array('cia'=>'DOGIA','del'=>$dia_ini,'al'=>$dia_fin));
            } else {
                error($app, 'DEBE INGRESAR LOS DIAS DE TRABAJO');
            }
        });

        $app->get('/:fe_ini_month/:fe_ini_year/:fe_fin_month/:fe_fin_year(/:export)', function($fe_ini_month, $fe_ini_year, $fe_fin_month, $fe_fin_year, $export=false) use ($app, $db, $struct) {
            if ($fe_ini_month && $fe_ini_year && $fe_fin_month && $fe_fin_year) {
                $data = $db->venta->select($struct['ventas']['sql'])
                ->where('(MONTH(fechahora)>=? AND YEAR(fechahora)>=?) AND (MONTH(fechahora)<=? AND YEAR(fechahora)<=?)', $fe_ini_month, $fe_ini_year, $fe_fin_month, $fe_fin_year)
                ->order('fechahora, numero');
                mostrar($struct, 'ventas', $data, $export, array('cia'=>'DOGIA','del'=>$fe_ini_month.'/'.$fe_ini_year,'al'=>$fe_fin_month.'/'.$fe_fin_year));
            } else {
                error($app, 'DEBE INGRESAR EL RANGO DE MESES');
            }
        });

        $app->get('/:dia_ini/:mes_ini/:anio_ini/:dia_fin/:mes_fin/:anio_fin(/:export)', function($dia_ini, $mes_ini, $anio_ini, $dia_fin, $mes_fin, $anio_fin, $export=false) use ($app, $db, $struct) {
            if($dia_ini && $mes_ini && $anio_ini && $dia_fin && $mes_fin && $anio_fin){
                $data = $db->venta->select($struct['ventas']['sql'])
                ->where('DATE(fechahora) BETWEEN DATE(?) AND DATE(?)', $anio_ini.'-'.$mes_ini.'-'.$dia_ini, $anio_fin.'-'.$mes_fin.'-'.$dia_fin)
                ->order('fechahora, numero');
                mostrar($struct, 'ventas', $data, $export, array('cia'=>'DOGIA','del'=>$dia_ini.'/'.$mes_ini.'/'.$anio_ini,'al'=>$dia_fin.'/'.$mes_fin.'/'.$anio_fin));
            } else {
                error($app, 'DEBE INGRESAR LAS FECHAS DE TRABAJO');
            }
        });

    });

    $app->group('/familias', function () use ($app, $db, $struct) {

        $app->get('/:dia_ini/:dia_fin(/:export)', function($dia_ini, $dia_fin, $export=false) use ($app, $db, $struct) {
            if ($dia_ini && $dia_fin) {
                $data = $db->venta_detalle->select($struct['familias']['sql'])
                ->where('venta.dia >= ? AND venta.dia <= ?', $dia_ini, $dia_fin)
                ->group('producto.categoria.nombre, producto_name, venta_detalle.precio')
                ->order('producto.categoria.nombre, producto_name');
                mostrar($struct, 'familias', $data, $export, array('cia'=>'DOGIA','del'=>$dia_ini,'al'=>$dia_fin));
            } else {
                error($app, 'DEBE INGRESAR LOS DIAS DE TRABAJO');
            }
        });

        $app->get('/:fe_ini_month/:fe_ini_year/:fe_fin_month/:fe_fin_year(/:export)', function($fe_ini_month, $fe_ini_year, $fe_fin_month, $fe_fin_year, $export=false) use ($app, $db, $struct) {
            if ($fe_ini_month && $fe_ini_year && $fe_fin_month && $fe_fin_year) {
                $data = $db->venta_detalle->select($struct['familias']['sql'])
                ->where('(MONTH(venta.fechahora)>=? AND YEAR(venta.fechahora)>=?) AND (MONTH(venta.fechahora)<=? AND YEAR(venta.fechahora)<=?)', $fe_ini_month, $fe_ini_year, $fe_fin_month, $fe_fin_year)
                ->group('producto.categoria.nombre, producto_name, venta_detalle.precio')
                ->order('producto.categoria.nombre, producto_name');
                mostrar($struct, 'familias', $data, $export, array('cia'=>'DOGIA','del'=>$fe_ini_month.'/'.$fe_ini_year,'al'=>$fe_fin_month.'/'.$fe_fin_year));
            } else {
                error($app, 'DEBE INGRESAR EL RANGO DE MESES');
            }
        });

        $app->get('/:dia_ini/:mes_ini/:anio_ini/:dia_fin/:mes_fin/:anio_fin(/:export)', function($dia_ini, $mes_ini, $anio_ini, $dia_fin, $mes_fin, $anio_fin, $export=false) use ($app, $db, $struct) {
            if($dia_ini && $mes_ini && $anio_ini && $dia_fin && $mes_fin && $anio_fin){
                $data = $db->venta->select($struct['familias']['sql'])
                ->where('DATE(venta.fechahora) BETWEEN DATE(?) AND DATE(?)', $anio_ini.'-'.$mes_ini.'-'.$dia_ini, $anio_fin.'-'.$mes_fin.'-'.$dia_fin)
                ->group('producto.categoria.nombre, producto_name, venta_detalle.precio')
                ->order('producto.categoria.nombre, producto_name');
                mostrar($struct, 'familias', $data, $export, array('cia'=>'DOGIA','del'=>$dia_ini.'/'.$mes_ini.'/'.$anio_ini,'al'=>$dia_fin.'/'.$mes_fin.'/'.$anio_fin));
            } else {
                error($app, 'DEBE INGRESAR LAS FECHAS DE TRABAJO');
            }
        });

    });

});


?>