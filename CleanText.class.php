<?php

class CleanText {
	/*
	MODO 0: minúsculas
	MODO 1: mayúsculas
	*/
	private $mode;
	private $text;
	private $quotes;
	
	public function __construct($text, $quotesEm=false) {
		$this->text = $text;
		$this->quotes = $quotesEm;
	}

	public function of_clean_text() {
		$this->of_text_mode();

		$this->of_normalize();

		if ($this->quotes) {
			$this->of_quotes_em();
		}

		return trim($this->text);
	}
	private function of_normalize() {

		$this->text = trim(html_entity_decode($this->text,ENT_QUOTES,'utf-8'));
		$this->of_recoge_especiales();
		$this->text = $this->of_punctuation_position($this->text);

		if ($this->mode == 1) {
			$this->text = mb_strtolower($this->text,'UTF-8');
		}

		$this->text = $this->of_translate_letters($this->text);

		$this->of_resucita_especiales();

		/*New line to <p> in the finish*/
		$this->nl2p();
	}
	private function of_quotes_em() {
		$this->text = preg_replace('/"([^"]+)"/i','"<em>$1</em>"',$this->text);
	}
	private function of_bold_names() {
		$this->text = preg_replace('/([A-Z][a-zA-Z]*)(\W)/','<strong>$1</strong>$2', $this->text);
	}
	private function of_text_mode() {
		$percent = 0.0;
		$lower = mb_strtolower($this->text, "UTF-8");
		
		$return = similar_text($this->text, $lower, $percent);
		
		if ($percent > 50) {
			$this->mode = 0;
		} else {
			$this->mode = 1;
		}
	}
	private function of_recoge_especiales() {
		$envuelve = array('/(https?:\/\/([-\w\.]+)+(:\d+)?(\/([\w\/_\.]*(\?\S+)?)?)?)/i',
		'/[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+/i',
		"/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", "/<a\s+href=['\"].*['\"]>.*<\a>/i");

		$matches = array();
		$especiales = array();
		foreach ($envuelve as $env) {
			preg_match_all($env,$this->text,$matches);

			foreach ($matches[0] as $m) {
				$especiales[] = $m;	
			}
		}

		$this->especiales = $especiales;

		//print_r($this->especiales);
		//return $texto;
	}
	private function of_resucita_especiales() {
		$matches = array();
		foreach ($this->especiales as $especial) {
			$roto = $this->of_punctuation_position($especial);
			$roto = $this->of_translate_letters($roto);

			$roto = trim($roto,' .');
			
			$this->text = str_ireplace($roto,$especial,$this->text);
		}
	}
	private function of_translate_letters($text) {
		//setlocale(LC_COLLATE, 'es_ES');
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
	private function of_punctuation_position($text) {
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

		/*Si termina en caracter de palabra, que acabe en punto (.)*/
		$text = preg_replace('/([\w])$/i', "$1.", $text);
		
		return $text;
	}
	private function nl2p() {
		$this->text = '<p>'.str_replace('\n\n', '</p>\n<p>', $this->text).'</p>';
	}
}
?>