<?php
date_default_timezone_set('America/Lima');
$DEBUG = false;

require 'vendor/autoload.php';

$app = new \Slim\Slim();
$app->config(array(
    'debug' => $DEBUG
));

/*$app->response()->header("Access-Control-Allow-Origin", "*");
$app->response()->header("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS");
$app->response()->header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, user");
$app->response()->header("Content-Type", "application/json;charset=utf-8");*/

$app->options('/', function() {});
$app->options('/almacen', function(){});
$app->options('/atenciones/:mesa', function() {});
$app->options('/launcher/:id', function() {});
$app->options('/caja', function(){});
$app->options('/caja/:cc_id', function(){});
$app->options('/caja/tablet/:nombre', function(){});
$app->options('/categoria', function(){});
$app->options('/categoria/:id', function(){});
$app->options('/categoria/tablet', function(){});
$app->options('/cliente', function(){});
$app->options('/cliente/:id', function(){});
$app->options('/cliente/ruc/:ruc', function(){});
$app->options('/corporacion/:id', function(){});
$app->options('/destino', function(){});
$app->options('/grupo', function(){});
$app->options('/guia/cabecera', function(){});
$app->options('/guia/cabecera/:id', function(){});
$app->options('/guia/cabecera/buscar/:nombre', function(){});
$app->options('/guia/detalle', function(){});
$app->options('/guia/detalle/:id', function(){});
$app->options('/guia/detalle/:guia', function(){});
$app->options('/guia/procesar/:id', function(){});
$app->options('/launcher/:id', function(){});
$app->options('/mesa', function(){});
$app->options('/pedido', function(){});
$app->options('/pedido/:id', function(){});
$app->options('/pedido/tablet', function(){});
$app->options('/pedido/mesa/:mesa', function(){});
$app->options('/pedido/pago/:nroatencion', function(){});
$app->options('/pedido/pagar', function(){});
$app->options('/pedido/liberar/:adminId', function(){});
$app->options('/pedido/actualizar', function(){});
$app->options('/pedido/actualizar/debug', function(){});
$app->options('/pedido/actualizar/cliente', function(){});
$app->options('/pedido/print/precuenta', function(){});
$app->options('/pedido/resumen/:cajaId', function(){});
$app->options('/producto', function(){});
$app->options('/producto/:id', function(){});
$app->options('/producto/codigo/:codigo', function(){});
$app->options('/producto/buscar/:nombre', function(){});
$app->options('/producto/pos', function(){});
$app->options('/producto/pos/categoria/:id', function(){});
$app->options('/producto/pos/buscar/:nombre', function(){});
$app->options('/producto/tablet/', function() {});
$app->options('/producto/tienda/', function() {});
$app->options('/producto/receta/:id', function() {});
$app->options('/tipo_documento', function(){});
$app->options('/tipo_operacion', function(){});
$app->options('/ubigeo', function(){});
$app->options('/ubigeo/lima', function(){});
$app->options('/unidad', function(){});
$app->options('/unidad/producto/:id', function(){});
$app->options('/util/ruc/:ruc', function(){});
$app->options('/usuario', function(){});
$app->options('/usuario/:id', function(){});
$app->options('/usuario/tablet', function(){});


$dsn = 'mysql:host=localhost;dbname=dbrewsoft2014;';
//$dsn = 'mysql:host=mibarrunto.no-ip.org;dbname=dbrewsoft2014;';
$username = 'root';
$password = '123456';
try{ 
    $pdo = new PDO($dsn, $username, $password);
} catch(PDOException $e) { 
    die(json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    )));
} 

$pdo->exec("SET CHARACTER SET utf8");
$db = new NotORM($pdo);
$db->debug = $DEBUG;

$result = array();
$result['success'] = true;
$result['data'] = array();

$authenticate = function() use($DEBUG) {
    if(!$DEBUG) {
        $app = \Slim\Slim::getInstance();
    	$user = $app->request->headers->get('http_user');
        //$row = $app->equipos->where('nombre', $user);
        $app->response()->status(401);
        $app->response()->write(json_encode(array(
            'errorauth' => true,
            'status' => $app->response->getStatus(),
            'http_user' => $user
        )));
        $app->stop();
    }
};

$almacen = function($guia, $producto, $almacen_id) use($db, &$almacen) {
    $producto_id = $producto['producto_id'];
    $receta = $db->receta->where('producto_id', $producto_id);
    if($receta->fetch()) {
        foreach ($receta as $producto) {
            $almacen($guia, $producto, $producto['almacen_id']);
        }
    } else {
        $guia_id = $guia['id'];
        $unidad_type = $producto['unidad_type'];
        $stock = 0;
        if(strtolower($unidad_type)=='menor') {
            $cantidadUnidad = $producto->unidad['cantidad'];
            $cantidad = $cantidadUnidad>0 ? $cantidad/$cantidadUnidad : $cantidad;

        }
        $cantidad = $producto['cantidad'];
        if(strtolower($guia->tipo_operacion['tipo'])=='s') {
            $cantidad *= -1;
        }
        $saldo = $cantidad;
        $tbStock = $db->stock->where('producto_id', $producto_id)->and('almacen_id', $almacen_id);
        if($row=$tbStock->fetch()){
            $stock = $row['stock'];
            $saldo = $stock+$cantidad;
            $row->update(array(
                'stock' => $saldo
            ));
        } else {
            $db->stock->insert(array(
                'producto_id' => $producto_id,
                'almacen_id' => $almacen_id,
                'stock' => $saldo
            ));
        }
        $db->kardex->insert(array(
            'producto_id' => $producto_id,
            'almacen_id' => $almacen_id,
            'guia_id' => $guia_id,
            'stock' => $stock,
            'cantidad' => $cantidad,
            'saldo' => $saldo,
            'registrado' => new NotORM_Literal("NOW()")
        ));
    }
    $guia->update(array(
        'procesado' => new NotORM_Literal("NOW()")
    ));
};

?>