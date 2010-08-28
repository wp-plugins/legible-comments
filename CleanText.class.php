<?php

class CleanText {
	/*
	MODE 0: lowercase
	MODE 1: uppercase
	*/
	private $mode;
	private $text;
	private $quotes;
	private $specials;
	
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
		$this->of_catch_specials();
		$this->text = $this->of_punctuation_position($this->text);

		if ($this->mode == 1) {
			$this->text = mb_strtolower($this->text,'UTF-8');
		}

		$this->text = $this->of_translate_letters($this->text);

		$this->of_restore_specials();

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

		/*It ompares the original string wuth a entire lowercase version of it to determine if the mayority is in uppercase*/
		$return = similar_text($this->text, $lower, $percent);
		
		if ($percent > 50) {
			$this->mode = 0;
		} else {
			$this->mode = 1;
		}
	}
	private function of_catch_specials() {
		$wrap = array('/(https?:\/\/([-\w\.]+)+(:\d+)?(\/([\w\/_\.]*(\?\S+)?)?)?)/i',
		'/[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+/i',
		"/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", "/<a\s+href=['\"].*['\"]>.*<\a>/i");

		$matches = array();
		$specials = array();
		foreach ($wrap as $w) {
			preg_match_all($w,$this->text,$matches);

			foreach ($matches[0] as $m) {
				$specials[] = $m;	
			}
		}

		$this->specials = $specials;
	}
	private function of_restore_specials() {
		$matches = array();
		foreach ($this->specials as $special) {
			$broken = $this->of_punctuation_position($special);
			$broken = $this->of_translate_letters($broken);

			$broken = trim($broken,' .');
			
			$this->text = str_ireplace($broken,$special,$this->text);
		}
	}
	private function of_translate_letters($text) {
		/*First letter in uppercase*/
		$text = preg_replace_callback('/^([\P{L}]*)([\p{L}]+)/i',create_function('$matches','return "$matches[1] ".ucfirst($matches[2]);'), $text);

		/*Uppercase before dot*/
		$text = preg_replace_callback('/(\.\s*)([\p{L}]+)/iS',
				create_function('$matches','return "$matches[1] ".ucfirst($matches[2]);'), $text);
		
		/*Uppercase at start of paragraph and dot at the finish*/
		$text = preg_replace_callback('/([\p{L}]*)([\n|\r]+)([\P{L}]*)([\p{L}])/',
			create_function('$matches','return $matches[1].".".$matches[2].$matches[3].mb_strtoupper($matches[4], "utf-8");'), $text);
			
		$text = str_replace('. .', '. ', $text);
		
		return $text;
	}
	private function of_punctuation_position($text) {
		/*Dot + Space (We are here. Hello)*/
		$text = preg_replace('/ *([\.|,|;|:]) */', '$1 ', $text);
		
		/*Numbers + Dot + Space + Numbers, it converts decimal numbers*/
		$text = preg_replace('/(\d)\.\s(\d)/', '$1.$2', $text);
		/*Suspension points special case*/
		$text = preg_replace('/(\s*\.\s*){2,}/','... ',$text);
	
		/*Repeated letters more than two times*/
		$text = preg_replace('/([a-z])\\1{2,}/is', '$1$1', $text);

		/*These marks go without space in front and with it before.*/
		$pattern = "/\s*([,|;|:|!|?]){1,}\s*/is";
		$replace = "$1 ";
		$text = preg_replace($pattern, $replace, $text);

		/*If it finishes with word character, it will finish with dot (.)*/
		$text = preg_replace('/([\w])$/i', "$1.", $text);
		
		return $text;
	}
	private function nl2p() {
		$this->text = '<p>'.str_replace('\n\n', '</p>\n<p>', $this->text).'</p>';
	}
}
?>