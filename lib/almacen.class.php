<?php
class Almacen {
    
    const TIPO_OPERACION_COMPRA = 2;
    const TIPO_OPERACION_VENTA = 1;

    function __construct($db, $guiaId, $tipoOperacionId) {
        $this->db = $db;
        $this->guiaId = $guiaId;
        $this->tipoOperacionId = $tipoOperacionId;
    }

    public function ingreso() {
    }

    public function salida($producto) {
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
                'guia_id' => $this->guiaId,
                'tipo_operacion_id' => $this->tipoOperacionId,
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