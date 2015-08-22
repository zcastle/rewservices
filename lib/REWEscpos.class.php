<?php

require_once('vendor/mike42/escpos-php/Escpos.php');

class REWEscpos extends Escpos {

	function __construct($pathPrint) {
		if($pathPrint=="LPT1"){
			$connector = new WindowsPrintConnector("LPT1");
		}else{
			if(filter_var($pathPrint, FILTER_VALIDATE_IP)){
				$connector = new NetworkPrintConnector($pathPrint, 9100);
			}else{
				$connector = new FilePrintConnector($pathPrint);
			}
		}
		Parent::__construct($connector);
		Parent::setFont(REWEscpos::FONT_B);
	}

	public function _print($text){
		Parent::text("$text");
	}

	public function _println($text){
		Parent::text("$text\n");
	}

	public function _cutPartial(){
		Parent::cut(Escpos::CUT_PARTIAL, 5);
	}

	public function _cutFull(){
		Parent::cut();
	}

	public function _center($center){
		Parent::setJustification($center?Parent::JUSTIFY_CENTER:Parent::JUSTIFY_LEFT);
	}

	public function _hr($len=40){
		$this->_println(str_repeat("-", $len));
	}

}