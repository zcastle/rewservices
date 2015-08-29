<?php

require_once('vendor/mike42/escpos-php/Escpos.php');

class REWEscpos extends Escpos {

	function __construct($pathPrint) {
		if($pathPrint=="LPT1"){
			$connector = new WindowsPrintConnector("LPT1");
		}else{
			if(filter_var($pathPrint, FILTER_VALIDATE_IP)){
				$connector = new NetworkPrintConnector($pathPrint, 9100);
			}elseif (substr($pathPrint, 0, 2)=='//') {
				//$connector = new WindowsPrintConnector("smb://computername/Receipt Printer");
				$connector = new WindowsPrintConnector("smb:$pathPrint");
			}else{
				$connector = new FilePrintConnector($pathPrint);
			}
		}
		Escpos::__construct($connector);
		Escpos::setFont(REWEscpos::FONT_B);
	}

	public function _print($text){
		Escpos::text("$text");
	}

	public function _println($text){
		Escpos::text("$text\n");
	}

	public function _cutPartial(){
		Escpos::cut(Escpos::CUT_PARTIAL, 5);
	}

	public function _cutFull(){
		Escpos::cut();
	}

	public function _center($center){
		Escpos::setJustification($center?Escpos::JUSTIFY_CENTER:Escpos::JUSTIFY_LEFT);
	}

	public function _hr($len=40){
		$this->_println(str_repeat("-", $len));
	}

}
