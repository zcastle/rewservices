<?php

//require "bootstrap.php";

$app->group('/producto', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $db, $result) {
        $page = $app->request->get('page');
        $start = $app->request->get('start');
        $limit = $app->request->get('limit') != null ? $app->request->get('limit') : 10;
    	$tb = $db->producto->where('eliminado', 'N')->order("nombre")->limit($limit, $start);
        $result['count'] = $db->producto()->count('*');
    	foreach ($tb as $row) {
            $row['categoria_name'] = $row->categoria["nombre"];
            $row['grupo_name'] = $row->grupo["nombre"];
    		array_push($result['data'], $row);
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/pos', function() use ($app, $db, $result) {
        $tb = $db->producto()->order("nombre");
        foreach ($tb as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                //'orden' => $row['orden'],
                'categoria_id' => $row['categoria_id'],
                'categoria_name' => $row->categoria['nombre']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/posstock/limit/:limit', function($limit) use ($app, $db, $result) {
        if($limit>0) {
            $tb = $db->producto()->limit($limit)->order("nombre");
        } else {
            $tb = $db->producto()->order("nombre");
        }
        foreach ($tb as $row) {
            $stock = 0;
            foreach ($db->stock->where('producto_id', $row['id']) as $res) {
                $stock += $res['stock'];
            }
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'categoria_name' => $row->categoria['nombre'],
                'unidad_id' => $row['unidad_id'],
                'unidad' => $row->unidad['mayor'],
                'stock' => $stock
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/posstock/:id', function($id) use ($app, $db, $result) {
        $tb = $db->producto->where('id', $id);
        if ($row=$tb->fetch()) {
            $stock = 0;
            foreach ($db->stock->where('producto_id', $row['id']) as $res) {
                $stock += $res['stock'];
            }
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'categoria_name' => $row->categoria['nombre'],
                'unidad_id' => $row['unidad_id'],
                'unidad' => $row->unidad['mayor'],
                'stock' => $stock
            ));
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/posstock/buscar/:nombre', function($nombre) use ($app, $db, $result) {
        $tb = $db->producto->where('nombre LIKE ?', '%'.$nombre.'%')->order("nombre");
        foreach ($tb as $row) {
            $stock = 0;
            foreach ($db->stock->where('producto_id', $row['id']) as $res) {
                $stock += $res['stock'];
            }
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'categoria_name' => $row->categoria['nombre'],
                'unidad_id' => $row['unidad_id'],
                'unidad' => $row->unidad['mayor'],
                'stock' => $stock
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/pos/categoria/:id', function($id) use ($app, $db, $result) {
        $tb = $db->producto->where('categoria_id=? AND eliminado=?', $id, 'N')->order("orden");
        //->limit(10, 0)
        foreach ($tb as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio']
            ));
            //,'orden' => $row['orden']
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/pos/buscar/:nombre', function($nombre) use ($app, $db, $result) {
        if( strtoupper(substr($nombre, 0, 2))=='||') {
            $nombre = substr($nombre, 2);
            $tb = $db->producto->where('codigo = ? AND eliminado=?', $nombre, 'N')->order("orden");
        } else {
            $tb = $db->producto->where('nombre LIKE ? AND eliminado=?', '%'.$nombre.'%', 'N')->order("orden");
        }
        
        foreach ($tb as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                //'categoria_name' => $row->categoria['nombre'],
                'precio' => $row['precio']
            ));
            //,'orden' => $row['orden']
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/codigo/:codigo', function($codigo) use ($app, $db, $result) {
        $tb = $db->producto->where('codigo', $codigo);
        if ($row = $tb->fetch()) {
            array_push($result['data'], $row);
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/buscar/:nombre', function($nombre) use ($app, $db, $result) {
        $tb = $db->producto->where('nombre like ? OR codigo=?', '%'.$nombre.'%', $nombre)->order("nombre");
        $result['success'] = false;
        foreach ($tb as $row) {
            array_push($result['data'], $row);
            $result['success'] = true;
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/tablet/', function() use ($app, $db, $result) {
        $tb = $db->producto->where('eliminado', 'N')->order("nombre");
        foreach ($tb as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'orden' => $row['orden'],
                'destino_id' => $row['destino_id'],
                'categoria_id' => $row['categoria_id']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/tienda/', function() use ($app, $db, $result) {
        $tb = $db->producto->where('eliminado', 'N')->order("nombre")->limit(20);
        foreach ($tb as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'foto' => $row['foto']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request()->post('data'));
        $row = $db->producto()->where('codigo', $values->codigo);
        if ($row->fetch()) {
            $result['existe'] = true;
        } else {
            $result['nuevo'] = true;
            $create = $db->producto->insert((array)$values);
            $result['id'] = $create['id'];
            $result['data'] = (array)$values;
        }
        $app->response()->write(json_encode($result));
    });

    $app->put("/:id", function ($id) use ($app, $db, $result) {
        $producto = $db->producto->where("id", $id);
        if ($row=$producto->fetch()) {
            $values = json_decode($app->request()->put('data'));
            $existe = $db->producto->where('codigo', $values->codigo)->and('id<>?', $id);
            if ($existe->fetch()) {
                $result['error'] = true;
                $result['message'] = 'El codigo ingresado existe';
            } else {
                $row->update((array)$values);
            }
        } else {
            $result['error'] = true;
            $result['message'] = 'No se ha encontrado el producto con el ID: '+$id;
        }
        $result['id'] = $id;
        $app->response()->write(json_encode($result));
    });

    $app->delete("/:id", function ($id) use($app, $db, $result) {
        $producto = $db->producto->where("id", $id);
        if ($producto->fetch()) {
            $producto->delete();
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->get('/receta/:id', function($id) use ($app, $db, $result) {
        $tb = $db->receta->where('producto_id', $id);
        foreach ($tb as $row) {
            $insumo = $db->producto[$row['insumo_id']];
            $row['insumo_name'] = $insumo['nombre'];
            $row['unidad_id'] = $insumo->unidad['id'];
            $row['unidad_name'] = strtolower($row['unidad_type'])=='mayor' ? $insumo->unidad['mayor'] : $insumo->unidad['menor'];
            $row['costo'] = strtolower($row['unidad_type'])=='mayor' ? $insumo['costo'] : $insumo['costo']/$insumo->unidad['cantidad'];
            $row['almacen_name'] = $row->almacen['nombre'];
            array_push($result['data'], $row);
        }
        $app->response()->write(json_encode($result));
    });

    $app->post("/receta", function () use($app, $db, $result) {
        $values = json_decode($app->request->post('data'));
        $create = $db->receta->insert((array)$values);
        array_push($result['data'], array(
            'id' => $create['id']
        ));
        $app->response()->write(json_encode($result));
    });

    $app->put('/receta/:id', function($id) use ($app, $db, $result) {
        $receta = $db->receta->where("id", $id);
        if ($row=$receta->fetch()) {
            $values = json_decode($app->request->put('data'));
            $row->update((array)$values);
        }
        $app->response()->write(json_encode($result));
    });
});

?>