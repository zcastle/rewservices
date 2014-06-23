<?php

$app->group('/categoria', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $db, $result) {
    	$rows = $db->categoria->order('nombre');
    	$result['count'] = $rows->count('*');
    	foreach ($rows as $row) {
    		$row['grupo_name'] = $row->grupo["nombre"];
    		$row['destino_name'] = $row->destino["nombre"];
    		array_push($result['data'], $row);
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/tablet/', function() use ($app, $db, $result) {
        $rows = $db->categoria->where('eliminado', 'N')->order('nombre');
        foreach ($rows as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'codigo' => $row['id'],
                'nombre' => $row['nombre']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request()->post()['data']);
        $create = $db->categoria->insert((array)$values);
        $result['data'] = (array)$values;
        $app->response()->write(json_encode($result));
    });

    $app->put("/:id", function ($id) use ($app, $db, $result) {
        $tabla = $db->categoria[$id];
        if ($tabla) {
            $values = json_decode($app->request()->put()['data']);
            $edit = $tabla->update((array)$values);
            $result['edit'] = (bool)$edit;
        } else {
            $result['success'] = false;
            $result['message'] = 'No se ha encontrado el ID: '+$id;
        }
        $app->response()->write(json_encode($result));
    });

    $app->delete("/:id", function ($id) use($app, $db, $result) {
        $tabla = $db->categoria[$id];
        if ($tabla->fetch() && $tabla->delete()) {
            $result['delete'] = true;
        } else {
            $result['success'] = false;
            $result['delete'] = false;
        }
        $app->response()->write(json_encode($result));
    });

});

?>