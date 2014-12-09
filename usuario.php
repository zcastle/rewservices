<?php

$app->group('/usuario', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $db, $result) {
    	$rows = $db->usuario->where('eliminado', 'N');
    	$result['count'] = $rows->count('*');
    	foreach ($rows as $row) {
    		$row['ubigeo_name'] = $row->ubigeo["nombre"];
    		$row['centrocosto_name'] = $row->centrocosto["nombre"];
    		$row['empresa_id'] = $row->centrocosto["empresa_id"];
    		$row['empresa_name'] = $row->centrocosto->empresa["razon_social"];
    		$row['rol_name'] = $row->rol['nombre'];
    		array_push($result['data'], $row);
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/pos/rol/:id', function($id) use ($app, $db, $result) {
        $rows = $db->usuario->select('id, nombre, apellido, usuario, clave, rol_id, centrocosto_id, sexo')->where('eliminado', 'N')->and('rol_id', $id);
        foreach ($rows as $row) {
            /*$row['centrocosto_name'] = $row->centrocosto["nombre"];
            $row['empresa_id'] = $row->centrocosto["empresa_id"];
            $row['empresa_name'] = $row->centrocosto->empresa["razon_social"];*/
            array_push($result['data'], $row);
        }
        $app->response()->write(json_encode($result));
    });

    //Usuario por ID
    $app->get('/:id', function($id) use ($app, $db, $result) {
    	$row = $db->usuario[$id];
    	if ($row) {
    		$row['ubigeo_name'] = $row->ubigeo["nombre"];
    		$row['centrocosto_name'] = $row->centrocosto["nombre"];
    		$row['empresa_id'] = $row->centrocosto["empresa_id"];
    		$row['empresa_name'] = $row->centrocosto->empresa["razon_social"];
    		$row['rol_name'] = $row->rol['nombre'];
    		$modulos_id = $db->rol_modulo()->where("rol_id", $row->rol['id'])->select('modulo_id');
    		$modulos = $db->modulo()->where('id', $modulos_id)->select('titulo');
    		$row['modulos'] = $modulos;
    		array_push($result['data'], $row);
    	} else {
    		$result['success'] = false;
    	}
        $app->response()->write(json_encode($result));
    });

    //Nombre y Clave
    $app->get('/:user/:pass', function($user, $pass) use ($app, $db, $result) {
    	$usuario = $db->usuario()->where("usuario=? AND clave=?", $user, md5($pass));
    	if ($usuario = $usuario->fetch()) {
    		array_push($result['data'], array(
                "id" => $usuario["id"],
                "nombres" => $usuario["nombres"]
            ));
    	} else {
    		$result['success'] = false;
    	}
        $app->response()->write(json_encode($result));
    });

    $app->get('/tablet/', function() use ($app, $db, $result) {
        $rows = $db->usuario->where('eliminado', 'N');
        foreach ($rows as $row) {
            array_push($result['data'], array(
                'id' => $row['id'],
                'usuario' => $row['usuario'],
                'clave' => $row['clave'],
                'rol_id' => $row['rol_id'],
                'rol_name' => $row->rol['nombre']
            ));
        }
        $app->response()->write(json_encode($result));
    });

    //Crear Usuario
    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request()->post('data'));
        $create = $db->usuario->insert((array)$values);
        $result['id'] = $create['id'];
        $result['post'] = (array)$values;
        $app->response()->write(json_encode($result));
    });

    //Actualizar Usuario
    $app->put("/:id", function ($id) use ($app, $db, $result) {
        //$usuario = $db->usuario()->where("id", $id);
        $usuario = $db->usuario[$id];
        //if ($usuario->fetch()) {
        if ($usuario) {
            //$put = $app->request()->put();
            $values = json_decode($app->request()->put('data'));
            $edit = $usuario->update((array)$values);
            $result['success'] = (bool)$edit;
            if((bool)$edit) {
            	$result['update'] = true;
            } else {
            	$result['update'] = false;
            }
        } else {
            $result['success'] = false;
            $result['message'] = 'No se ha encontrado al usuario con el ID: '+$id;
        }
        $result['id'] = $id;
        $app->response()->write(json_encode($result));
    });

    //Eliminar Usuario
    $app->delete("/:id", function ($id) use($app, $db, $result) {
        //$usuario = $db->usuario()->where("id", $id);
        $usuario = $db->usuario[$id];
        //if ($usuario->fetch()) {
        if ($usuario && $usuario->delete()) {
            //$delete = $usuario->delete();
            //if ($delete>0) {
            	$result['delete'] = true;
            	//$result['deleteCount'] = $delete;
            /*} else {
            	$result['delete'] = false;
            }*/
        } else {
            $result['success'] = false;
            $result['delete'] = false;
        }
        $app->response()->write(json_encode($result));
    });

});

?>