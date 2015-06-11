<?php

$app->group('/produccion', function () use ($app, $db, $result) {

    $app->get('/', function() use ($app, $db, $result) {
        $page = $app->request->get('page');
        $start = $app->request->get('start');
        $limit = $app->request->get('limit') != null ? $app->request->get('limit') : 10;
    	$tb = $db->orden_produccion->limit($limit, $start);
        $result['count'] = $db->orden_produccion()->count('*');
        $result['data'] = $row;
        $app->response()->write(json_encode($result));
    });

    $app->post("/", function () use($app, $db, $result) {
        $values = json_decode($app->request()->post('data'));
        $create = $db->orden_produccion->insert((array)$values);
        $result['id'] = $create['id'];
        $app->response()->write(json_encode($result));
    });

    $app->put("/:id", function ($id) use ($app, $db, $result) {
        $data = $db->orden_produccion->where("id", $id);
        if ($row=$data->fetch()) {
            $values = json_decode($app->request()->put('data'));
            $row->update((array)$values);
        } else {
            $result['error'] = true;
            $result['message'] = 'No se ha encontrado el ID: '+$id;
        }
        $result['id'] = $id;
        $app->response()->write(json_encode($result));
    });

    $app->delete("/:id", function ($id) use($app, $db, $result) {
        $c = $db->orden_produccion->where("id", $id);
        if ($row=$c->fetch()) {
            $row->orden_produccion_detalle->delete();
            $row->delete();
        } else {
            $result['success'] = false;
        }
        $app->response()->write(json_encode($result));
    });

    $app->group('/detalle', function () use ($app, $db, $result) {

        $app->get('/', function() use ($app, $db, $result) {
            $page = $app->request->get('page');
            $start = $app->request->get('start');
            $limit = $app->request->get('limit') != null ? $app->request->get('limit') : 10;
            $tb = $db->orden_produccion_detalle->limit($limit, $start);
            $result['count'] = $db->orden_produccion_detalle()->count('*');
            $result['data'] = $row;
            $app->response()->write(json_encode($result));
        });

        $app->post("/", function () use($app, $db, $result) {
            $values = json_decode($app->request()->post('data'));
            $create = $db->orden_produccion_detalle->insert((array)$values);
            $result['id'] = $create['id'];
            $app->response()->write(json_encode($result));
        });

        $app->put("/:id", function ($id) use ($app, $db, $result) {
            $data = $db->orden_produccion_detalle->where("id", $id);
            if ($row=$data->fetch()) {
                $values = json_decode($app->request()->put('data'));
                $row->update((array)$values);
            } else {
                $result['error'] = true;
                $result['message'] = 'No se ha encontrado el ID: '+$id;
            }
            $result['id'] = $id;
            $app->response()->write(json_encode($result));
        });

        $app->delete("/:id", function ($id) use($app, $db, $result) {
            $c = $db->orden_produccion_detalle->where("id", $id);
            if ($row=$c->fetch()) {
                $row->orden_produccion_detalle->delete();
                $row->delete();
            } else {
                $result['success'] = false;
            }
            $app->response()->write(json_encode($result));
        });
    });
});

?>