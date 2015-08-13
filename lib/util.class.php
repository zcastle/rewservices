<?php

class Util {

	public static function left($text, $len){
		return substr($text.str_repeat(" ", $len), 0, $len);
	}

	public static function right($text, $len, $char=""){
		return substr(str_repeat($char, $len).$text, $len*-1);
	}

	public static function now(){
		$objDateTime = new DateTime('NOW');
		return $objDateTime->format('d-m-Y h:i A');
	}
}

?>