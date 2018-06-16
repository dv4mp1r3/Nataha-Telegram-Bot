<?php
/**
 * php-markov
 *
 * Tiny Markov chain implementation for PHP.
 *
 * @package		php-markov
 * @subpackage		Common
 * @author		Kenny <0@kenny.cat>
*/
class Markov {
	public $chain = array();

	/**
	* Training
	*
	* Adds the given string to the chain currently defined in this class.
	*
	* @param string $message The message to be added to the markov chain.
	* @return true if string added, false if string empty.
	*/
	public function train($message) {
		if(empty($message)) return false;
		$array = explode(" ", $message);
		
		foreach($array as $num => $val) {
			$val = base64_encode($val);
			$commit = (isset($this->chain[$val]) ? $this->chain[$val] : array()); // if there is already a block for this word, keep it, otherwise create one
			$next = $array[$num + 1]; // the next word after the one currently selected
			if(empty($next)) continue; // if this word is EOL, continue to the next word
			$next = base64_encode($next);
			if(isset($commit[$next])) $commit[$next]++; // if the word already exists, increase the weight
			else $commit[$next] = 1; // otherwise save the word with a weight of 1
			
			$this->chain[$val] = $commit; // commit to the chain
		}
		
		return true;
	}
	
	/**
	* Generate text
	*
	* Generates a random string using the markov chain.
	*
	* @param int $maxWords Maximum number of words to be returned (may return less).
	* @return str if generation successful, false if chain empty.
	*/
	public function generateText($maxWords) {
		if(empty($this->chain)) return false;
		$out = array_rand($this->chain); // initial word
		
		while($out = $this->weighAndSelect($this->chain[$out])) {		
			$text[] = base64_decode($out);
			if(count($text) > $maxWords) break;
		}
		
		return implode(" ", $text);
	}
	
	/**
	* Weigh and select
	*
	* Resolves weighing of words and returns a random one. Used by generateText.
	*
	* @param array $block Array with words as the index and their weight as the value.
	* @return str next word to follow or false if empty block given.
	*/
	private function weighAndSelect($block) {
		if(empty($block)) return false;
		
		foreach($block as $key => $weight) {
			for($i = 1; $i <= $weight; $i++) $tmp[] = $key;
		}
		
		$rand = array_rand($tmp);
		return $tmp[$rand];
	}
}
