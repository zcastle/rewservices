<?php

class Common {    
    private $app;
    private $db;
    private $result;

    function __construct($db, $result) {
		$this->db = $db;
		$this->result = $result;
	}

	public function getResumen($cajaId){
		$db = $this->db;
		$result = $this->result;

		$cc = $db->caja->select("centrocosto_id")->where("id", $cajaId)->fetch()["centrocosto_id"];
        $rowsAtencion = $db->atenciones->where('caja.centrocosto_id=?', $cc);
        $caja = $db->caja('id', $cajaId)->fetch();

        $rowsVenta = $db->venta->select("cajero_id, SUM(total) AS total")
                    ->where('dia', $caja['dia'])->and('caja_id', $cajaId)->and("anulado_id", 0)
                    ->group("cajero_id")->order("fechahora");

        foreach ($rowsVenta as $venta){
            $cajero = $db->usuario->select("nombre, apellido")->where('id', $venta["cajero_id"])->fetch();
            array_push($result['data'], array(
                'usuario' => $cajero["nombre"].' '.$cajero["apellido"],
                'total' => $venta['total']
            ));
        }

        if($rowsAtencion->fetch()){
            array_push($result['data'], array(
                'usuario' => "PEDIDOS ABIERTOS",
                'total' => $rowsAtencion->sum('cantidad * precio')
            ));
        }else{
            array_push($result['data'], array(
                'usuario' => "PEDIDOS ABIERTOS",
                'total' => 0
            ));
        }
        return $result;
	}

}

?>