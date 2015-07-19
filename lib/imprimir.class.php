<?php

require_once('lib/escpos-php/Escpos.php');

class Imprimir {

	private $printer = null;

	function __construct($ip) {

		//$connector = new NetworkPrintConnector($ip, 9100);
		$connector = new FilePrintConnector("/var/www/html/precuenta.txt");
		$this->printer = new Escpos($connector);
		//$printer -> text(str_repeat("0", 40)."\n");
		//$printer -> cut()
	}

	public function precuenta($atencion, $cia){

		$this->printer->text(str_repeat("-",40)."\n");
		foreach ($atencion as $row) {
			$can = substr($row['cantidad']."   ", 0, 3);
			$pro = substr($row['producto_name'].str_repeat(" ",19), 0, 19);
			$pre = substr(str_repeat(" ",7).number_format($row['precio'], 2), -7);
			$tot = substr(str_repeat(" ",8).number_format($pre*$can, 2), -8);
			$this->printer->text("$can $pro $pre $tot\n");
		}
		$this->printer->text(str_repeat("-",40)."\n");

		//if($ticketera)
		//$printer -> cut(Escpos::CUT_PARTIAL, 5);
		$this->printer -> close();
	}

	public function comprobante($cabecera, $detalle, $cia){
		
	}

}



?>