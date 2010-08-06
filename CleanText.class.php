<?php

class CleanText {

	public $nombres;
	/*
	MODO 0: minúsculas
	MODO 1: mayúsculas
	*/
	private $mode;
	private $e1;
	private $e2;
	
	public function __construct() {
		$this->e1 = "##___{";
		$this->e2 = "}___##";
	}

	function of_clean_text($text, $quotesEm=false) {

		$this->of_text_mode($text);

		$text = trim($this->of_normalize($text));

		if ($quotesEm) {
			$text = $this->of_quotes_em($text);
		}

		return $text;
	}
	function of_normalize($text) {

		$text = trim(html_entity_decode($text,ENT_QUOTES,'utf-8'));
		$this->of_recoge_especiales($text);
		$text = $this->of_punctuation_position($text);

		if ($this->mode == 1) {
			$text = mb_strtolower($text,'UTF-8');
		}

		$text = $this->of_translate_letters($text);

		/*New line to <p> in the finish*/
		$text = $this->nl2p($text);

		$text = $this->of_resucita_especiales($text);
		return $text;
	}
	function of_quotes_em($text) {
		return preg_replace('/"([^"]+)"/i','"<em>$1</em>"',$text);
	}
	function of_bold_names($text) {
		return preg_replace('/([A-Z][a-zA-Z]*)(\W)/','<strong>$1</strong>$2', $text);
	}
	function of_text_mode($text) {
		$percent = 0.0;
		$lower = mb_strtolower($text, "UTF-8");
		
		$return = similar_text($text, $lower, $percent);
		
		if ($percent > 50) {
			$this->mode = 0;
		} else {
			$this->mode = 1;
		}
	}
	function of_recoge_especiales($texto) {
		$envuelve = array('/(https?:\/\/([-\w\.]+)+(:\d+)?(\/([\w\/_\.]*(\?\S+)?)?)?)/i',
		'/[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+/i');

		$matches = array();
		$especiales = array();
		foreach ($envuelve as $env) {
			preg_match_all($env,$texto,$matches);
			
			foreach ($matches[0] as $m) {
				$especiales[] = $m;	
			}
		}

		$this->especiales = $especiales;
		//return $texto;
	}
	function of_resucita_especiales($text) {
		$matches = array();
		foreach ($this->especiales as $especial) {
			$roto = $this->of_punctuation_position($especial);
			$roto = $this->of_translate_letters($roto);
			
			$roto = trim($roto,' .');
			$text = str_ireplace($roto,$especial,$text);
		}

		return $text;
	}
	function of_translate_letters($text) {
		setlocale (LC_COLLATE, 'es_ES');
		/*Primera letra que aparezca, en mayúsculas*/
		$text = preg_replace_callback('/^([\P{L}]*)([\p{L}]+)/i',create_function('$matches','return "$matches[1] ".ucfirst($matches[2]);'), $text);

		/*Mayúsculas tras punto*/
		$text = preg_replace_callback('/(\.\s*)([\p{L}]+)/iS',
				create_function('$matches','return "$matches[1] ".ucfirst($matches[2]);'), $text);
		
		/*Mayúsculas al principio de párrafo y punto al final de este*/

		$text = preg_replace_callback('/([\p{L}]*)([\n|\r]+)([\P{L}]*)([\p{L}])/',
			create_function('$matches','return $matches[1].".".$matches[2].$matches[3].mb_strtoupper($matches[4], "utf-8");'), $text);
			
		$text = str_replace('. .', '. ', $text);

		return $text;
	}
	function of_punctuation_position($text) {
		/*Punto + Espacio (Aquí estamos. Hola)*/
		$text = preg_replace('/ *([\.|,|;|:]) */', '$1 ', $text);
		
		/*Números + Punto + Espacio + Números, convierte números decimales*/
		$text = preg_replace('/(\d)\.\s(\d)/', '$1.$2', $text);
		/*Caso de puntos suspensivos*/
		$text = preg_replace('/(\s*\.\s*){2,}/','... ',$text);
	
		/*Letras repetidas más de dos veces*/
		$text = preg_replace('/([a-z])\\1{2,}/is', '$1$1', $text);

		/*Estos signos van sin espacio delante y con uno después*/
		$pattern = "/\s*([,|;|:|!|?]){1,}\s*/is";
		$replace = "$1 ";
		$text = preg_replace($pattern, $replace, $text);

		/*Cursiva para lo que esté entre comillas*/
		//$text = preg_replace('/"([^"]+)"/i','"<em>$1</em>"',$text);

		/*Si termina en caracter de palabra, que acabe en punto (.)*/
		$text = preg_replace('/([\w])$/i', "$1.", $text);

		return $text;
	}
	function nl2p($text) {
		return '<p>'.str_replace('\n\n', '</p>\n<p>', $text).'</p>';
	}
}
?>