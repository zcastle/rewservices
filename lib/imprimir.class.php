<?php

//require_once('lib/escpos-php/Escpos.php');
//require_once('vendor/mike42/escpos-php/Escpos.php');
require_once('lib/REWEscpos.class.php');
require_once('lib/util.class.php');

class Imprimir {

	const FACTURA = 2;
	const BOLETA = 4;
    const ENVIO_ADD = "add";
    const ENVIO_REMOVE = "remove";

	private $config = array(
		"cabecera" => false,
		"cajero" => "",
		"mozo" => "",
		"despedida" => "",
		"factura" => false,
		"registradora" => null,
		"autorizacion" => null,
		"serie" => "",
		"numero" => "",
		"igv" => 18,
		"servicio" => 0,
        "total" => 0
	);

	private $printer = null;
	private $response = array(
		"data" => array(
			"success" => true,
			"error" => false,
			"message" => ""
		)
	);

	private $cia = null;

	function __construct($pathPrint, $cia=null) {
		$this->printer = new REWEscpos($pathPrint);
		$this->cia = $cia;
	}

	public function precuenta($atencion, array $config = null){
		$config = $config ? $config : $this->config;
		$printer = $this->printer;
		$cia = $this->cia;

		$printer->_center(true);
		if ($this->config["cabecera"]) {
            $printer->_println("P R E C U E N T A");
            $printer->_println("Comprobante no autorizado");
        } else {
            if ($cia["nombre_comercial"]) {
                $printer->_println($cia["nombre_comercial"]);
            }
            $printer->_println($cia["razon_social"]." - ".$cia["ruc"]);
            $printer->_println($cia["direccion"]);
            $printer->_println("TLF: ".$cia["telefono"]);
        }
        $printer->_center(false);
        $printer->_println(str_repeat("-",40));

        $printer->_println("FECHA : ".Util::now());
        $cabecera = $atencion->fetch();
        $printer->_println("MESA  : ".$cabecera["nroatencion"]." - PAX: ".$cabecera["pax"]);
        $printer->_println("CAJERO: ".$config["cajero"]);
        $printer->_println("MOZO  : ".$config["mozo"]);
        $printer->_println(str_repeat("-",40));
        $printer->_println("CANT PRODUCTO            UNIT. TOTAL S/.");
        $printer->_println(str_repeat("-",40));
        $total = 0.0;
		foreach ($atencion as $row) {
			$can = Util::left($row['cantidad'], 4);
			$pro = Util::left($row['producto_name'], 17);
			$pre = Util::right(number_format($row['precio'], 2), 9);
			$tot = Util::right(number_format($row['cantidad']*$row['precio'], 2), 10);
			$printer->_println("$can$pro$pre$tot");
			$total += (double)$row['cantidad']*(double)$row['precio'];
		}
		$printer->_println(str_repeat("-",40));
		$printer->_println("TOTAL                 S/.     ".Util::right(number_format($total, 2), 10));

		$printer->feed();
        $printer->_println("RUC:------------------------------------");
        $printer->feed();
        $printer->_println("RAZON SOCIAL:---------------------------");
        $printer->feed();
        $printer->_println(str_repeat("-",40));
        $printer->feed();
        $printer->_center(true);
        $printer->_println($config["despedida"]);
        $printer->_center(false);

		$printer->_cutFull();
		$printer->close();
		return $this->response;
	}

	public function comprobante($cliente, $detalle, array $config = null){
		$config = $config ? $config : $this->config;
		$printer = $this->printer;
		$cia = $this->cia;

		$printer->_center(true);
		if ($cia["nombre_comercial"]) {
            $printer->_println($cia["nombre_comercial"]);
        }
        $printer->_println($cia["razon_social"]." - ".$cia["ruc"]);
        $printer->_println($cia["direccion"]);
        $printer->_println("TLF: ".$cia["telefono"]);
        $printer->_println(str_repeat("-",40));
        $sunat = null;
        if ($config["registradora"]) {
            $sunat .= "Serie: ".$config["registradora"];
        }
        if ($config["autorizacion"]) {
            $sunat .= " Autorizacion: ".$config["autorizacion"];
        }
        if ($sunat) {
            $printer->_println($sunat);
        }
        $seq = "TICKET ";
        if ($config["factura"]) {
            $seq .= "F";
        } else {
            $seq .= "B";
        }
        $seq .= "V: ".Util::right($config["serie"], 3, "0")."-".Util::right($config["numero"], 7, "0");
        $printer->_println($seq);
        $printer->_center(false);
        $printer->_println(str_repeat("-",40));
        $printer->_println("FECHA : ".Util::now());
        $printer->_println("CAJERO: ".$config["cajero"]);
        $printer->_println(str_repeat("-",40));
        $printer->_println("CANT PRODUCTO            UNIT. TOTAL S/.");
        $printer->_println(str_repeat("-",40));
        $total = 0.0;
		foreach ($detalle as $row) {
			$can = Util::left($row['cantidad'], 4);
			$pro = Util::left($row['producto_name'], 17);
			$pre = Util::right(number_format($row['precio'], 2), 9);
			$tot = Util::right(number_format($row['cantidad']*$row['precio'], 2), 10);
			$printer->_println("$can$pro$pre$tot");
			$total += (double)$row['cantidad']*(double)$row['precio'];
		}
		$printer->_println(str_repeat("-",40));
		if ($config["factura"]){
			$recargo = $config["igv"] + $config["servicio"];
            $sTotal = $total / (($recargo / 100) + 1);
            $igv = $sTotal * ($config["igv"] / 100);
            $printer->_println("BASE                  S/.     ".Util::right(number_format($sTotal, 2), 10));
            $printer->_println("IGV(".$config["igv"]."%)              S/.     ".Util::right(number_format($igv, 2), 10));
            $servicio = $config["servicio"] > 0 ? $sTotal * ($config["servicio"] / 100) : 0;
            if ($servicio > 0) {
                $printer->_println("SERVICIO(".$config["servicio"]."%)         S/.     ".Util::right(number_format($servicio, 2), 10));
            }
		}
		$printer->_println("TOTAL                 S/.     ".Util::right(number_format($total, 2), 10));
		$printer->feed();
		if ($cliente['cliente_id'] > 0) {
            $printer->_println("CLIENTE: ".$cliente['nombre']);
            $printer->_println("RUC: ".$cliente['ruc']);
            $printer->_println("DIRECCION: ".$cliente['direccion']);
        }
        $printer->feed();
        $printer->_center(true);
        $printer->_println($config["despedida"]);
        $printer->_center(false);


		$printer->_cutFull();
		$printer->close();
		return $this->response;
	}

    public function liberar($cabecera, array $config = null){
        $config = $config ? $config : $this->config;
        $printer = $this->printer;

        $printer->_hr();
        $printer->_center(true);
        $printer->_println("M E S A  L I B E R A D A");
        $printer->_center(false);
        $printer->_hr();

        $date = new DateTime($cabecera['fecha']);
        $printer->_println("FECHA : ".$date->format('d-m-Y H:i:s'));
        $printer->_println("MESA  : ".$cabecera['nroatencion']);
        $printer->_println("CAJERO: ".$config['cajero']);
        $printer->_println("MONTO : ".number_format($config['total'], 2));
        
        $printer->_cutFull();
        $printer->close();
        return $this->response;
    }

    public function pedido($atencion, $destino, array $config = null, $tipo=Imprimir::ENVIO_ADD){
        $config = $config ? $config : $this->config;
        $printer = $this->printer;

        $printer->_hr();
        $printer->_center(true);
        if($tipo==Imprimir::ENVIO_ADD){
            $printer->_println("PEDIDO NUEVO");
        }else{
            $printer->_println("PEDIDO ELIMINADO");
        }
        $printer->_center(false);
        $printer->_hr();

        $row = $atencion->fetch();
        $printer->_println("FECHA  : ".Util::now());
        $printer->_println("MESA   : ".$row['nroatencion']." MOZO: ".$config['mozo']);
        $printer->_println("DESTINO: ".$destino);
        $printer->_hr();
        $printer->_println("CANT PRODUCTO");
        $printer->_hr();
        foreach ($atencion as $row) {
            $printer->_print(Util::left($row["cantidad"], 4));
            $printer->_println(Util::left($row["producto_name"], 36));
            if($row["mensaje"] && $tipo==Imprimir::ENVIO_ADD) {
                $printer->_println("   >".$row["mensaje"]);
            }
        }
        $printer->_hr();
        
        $printer->_cutFull();
        $printer->close();
        return $this->response;
    }

    public function cierre($comprobantes, $productos, array $config = null){
        $config = $config ? $config : $this->config;
        $printer = $this->printer;
        $cia = $this->cia;

        $printer->_center(true);
        $printer->_hr();
        if ($cia["nombre_comercial"]) {
            $printer->_println($cia["nombre_comercial"]);
        }
        $printer->_println($cia["razon_social"]." - ".$cia["ruc"]);
        $printer->_println($cia["direccion"]);
        $printer->_println("TLF: ".$cia["telefono"]);
        $printer->_hr();
        if ($config["cajero"]==0) {
            $printer->_println("CIERRE TOTAL");
        } else {
            $printer->_println("CIERRE PARCIAL");
        }
        $printer->_hr();
        $printer->_center(false);

        $printer->_cutFull();
        $printer->close();
        return $this->response;
    }

}



?>