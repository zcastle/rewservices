<?php

//require_once('lib/escpos-php/Escpos.php');
//require_once('vendor/mike42/escpos-php/Escpos.php');
require_once('lib/REWEscpos.class.php');
require_once('lib/util.class.php');

class Imprimir {

	const FACTURA = 2;
	const BOLETA = 4;

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
		"servicio" => 0
	);

	private $printer = null;
	private $response = array(
		"success" => true,
		"error" => false,
		"message" => ""
	);

	private $cia = null;

	function __construct($pathPrint, $cia) {
		$this->printer = new REWEscpos($pathPrint);
		$this->cia = $cia;
	}

	public function precuenta($atencion, array $config = null){
		$config = $config ? $config : $this->config;
		$printer = $this->printer;
		$cia = $this->cia;

		$printer->setFont(REWEscpos::FONT_B);
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
			$can = Util::left($row['cantidad'], 3);
			$pro = Util::left($row['producto_name'], 19);
			$pre = Util::right(number_format($row['precio'], 2), 7);
			$tot = Util::right(number_format($pre*$can, 2), 8);
			$printer->_println("$can $pro $pre $tot");
			$total += (double)$row['cantidad']*(double)$row['precio'];
		}
		$printer->_println(str_repeat("-",40));
		$printer->_println("TOTAL                 S/.     ".Util::right(number_format($total, 2), 10));

		$printer->feed();
        $printer->_println("RUC:------------------------------------");
        $printer->feed();
        $printer->_println("RAZON SOCIAL:---------------------------");
        $printer->feed();
        $printer->_println("----------------------------------------");
        $printer->feed();
        $printer->_center(true);
        $printer->_println($config["despedida"]);
        $printer->_center(false);

		$printer->_cutFull();
		$printer->close();
		return $this->response;
	}

	public function comprobante($cabecera, $detalle, array $config = null){
		$config = $config ? $config : $this->config;
		$printer = $this->printer;
		$cia = $this->cia;

		$printer->setFont(REWEscpos::FONT_B);
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
        //$cabecera = $atencion->fetch();
        //$printer->_println("MESA  : ".$cabecera["nroatencion"]." - PAX: ".$cabecera["pax"]);
        $printer->_println("CAJERO: ".$config["cajero"]);
        //$printer->_println("MOZO  : ".$config["mozo"]);
        $printer->_println(str_repeat("-",40));
        $printer->_println("CANT PRODUCTO            UNIT. TOTAL S/.");
        $printer->_println(str_repeat("-",40));
        $total = 0.0;
		foreach ($detalle as $row) {
			$can = Util::left($row['cantidad'], 3);
			$pro = Util::left($row['producto_name'], 19);
			$pre = Util::right(number_format($row['precio'], 2), 7);
			$tot = Util::right(number_format($pre*$can, 2), 8);
			$printer->_println("$can $pro $pre $tot");
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

		$printer->_cutFull();
		$printer->close();
		return $this->response;
	}

}



?>