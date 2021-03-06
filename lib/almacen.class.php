<?php
class Almacen {
    
    const TIPO_OPERACION_COMPRA = 2;
    const TIPO_OPERACION_VENTA = 1;

    function __construct($db, $guia_id) { //, $tipoOperacionId
        $this->db = $db;
        $this->guia_id = $guia_id;
        //$this->tipoOperacionId = $tipoOperacionId;
    }

    public function ingreso(NotORM_Row $producto) {
        $db = $this->db;
        $db->transaction = "BEGIN";
        try {
            $guia = $db->guia->where('id', $this->guia_id)->fetch();
            $almacen_id = $guia['almacen_id'];
            //$unidad_id = $guia['unidad_id'];
            //$guia_id = $this->guia_id;
            $unidad_id = $producto['unidad_id'];
            $unidad_type = $producto['unidad_type'];
            $stock = 0;
            $cantidad = $producto['cantidad'];
            if(strtolower($unidad_type)=='menor') {
                $cantidadUnidad = $producto->unidad['cantidad'];
                $cantidad = $cantidadUnidad>0 ? $cantidad/$cantidadUnidad : $cantidad;
            }
            if(strtolower($guia->tipo_operacion['tipo'])=='s') $cantidad *= -1;
            $saldo = $cantidad;
            $tbStock = $db->stock->where('producto_id', $producto['producto_id'])
                                    ->and('unidad_id', $unidad_id)
                                    ->and('almacen_id', $almacen_id);
            if($row=$tbStock->fetch()){
                $stock = $row['stock'];
                $saldo = $stock+$cantidad;
                $row->update(array(
                    'stock' => $saldo
                ));
            } else {
                $db->stock->insert(array(
                    'producto_id' => $producto['producto_id'],
                    'unidad_id' => $unidad_id,
                    'almacen_id' => $almacen_id,
                    'stock' => $saldo
                ));
            }
            $db->kardex->insert(array(
                'guia_id' => $this->guia_id,
                'producto_id' => $producto['producto_id'],
                'unidad_id' => $unidad_id,
                'almacen_id' => $almacen_id,
                'stock' => $stock,
                'cantidad' => $cantidad,
                'saldo' => $saldo,
                'registrado' => new NotORM_Literal("NOW()")
            ));
            $db->transaction = 'COMMIT';
            return true;
        } catch (PDOException $e) {
            $db->transaction = 'ROLLBACK';
            return false;
        }
    }

    public function salida(array $producto) {
        $db = $this->db;
        $receta = $db->receta->select('id, cantidad, almacen_id')->where('producto_id', $producto['id']);
        if($receta->fetch()) {
            foreach ($receta as $insumo) {
                $producto = $db->producto->select('costo')->where('id', $insumo['id']);
                $costo = 0;
                if ($row = $producto->fetch()) {
                    $costo = $row['costo'];
                }
                $salida(array(
                    'id' => $insumo['id'],
                    'cantidad' => $insumo['cantidad']*$producto['cantidad'],
                    'costo' => $costo,
                    'almacenId' => $insumo['almacen_id']
                ));
            }
        } else {
            $stock = $db->stock->select('stock')->where('producto_id', $producto['id'])->and('almacen_id', $producto['almacenId']);
            $curStock = 0;
            $newStock = 0;
            if($row = $stock->fetch()){
                $curStock = $row['stock'];
                $newStock = $curStock-$producto['cantidad'];
                $stock->update(array(
                    'stock' => $newStock
                ));
            } else {
                $newStock = $producto['cantidad'];
                $db->stock->insert(array(
                    'producto_id' => $producto['id'],
                    'almacen_id' => $producto['almacenId'],
                    'stock' => $newStock*-1
                ));
            }
            $db->kardex->insert(array(
                'guia_id' => $this->guia_id,
                //'tipo_operacion_id' => Almacen::TIPO_OPERACION_VENTA,
                'producto_id' => $producto['id'],
                'almacen_id' => $producto['almacenId'],
                'stock' => $curStock,
                'cantidad' => $producto['cantidad']*-1,
                'saldo' => $newStock,
                'registrado' => new NotORM_Literal("NOW()")
            ));
        }
    }
}
?>