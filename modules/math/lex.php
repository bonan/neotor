<?php
namespace Math;

class Lexer {
	// Sträng kvar att scanna
	var $cur;
	
	// Position i strängen
	var $pos;
	
	var $skip = array(
		'\s+'
	);
	
	var $tokens = array(
		'RAND' => 'RAND',
		'COS' => 'COS',
		'SIN' => 'SIN',
		'PI' => 'PI',
		'E' => 'E',
		'C' => 'C',
		'LPAR' => '\(',
		'RPAR' => '\)',
		'FLOAT' => '[0-9]+[\.\,][0-9]+', 
		'INT2' => '[01]+b',
		'INT16' => '0x[0-9A-F]+',
		'INT10' => '[0-9]+',
		'VARI' => '[a-z]',
		'POW' => '\^',
		'MUL' => '\*',
		'ADD' => '\+',
		'SUB' => '\-',
		'DIV' => '\/',
		'FAC' => '!',
		'INTDIV' => '\\\\' // kommer bli två backslashar till regexpen
	);
	
	function __construct($code) {
		$this->cur = $code;
		$this->pos = 0;
	}
	
	function match($pattern) {
		if(preg_match(sprintf('/^%s/i', $pattern), $this->cur, $match)) {
			$this->pos += strlen($match[0]);
			$this->cur = substr($this->cur, strlen($match[0]));
			return $match[0];
		}
		
		return false;
	}
	
	function next_token() {
		do {
			$t = $this->next_token2();
		} while(!$t);
		
		return $t;
	}
	
	function next_token2() {
		if(strlen($this->cur) == 0) {
			return new Token("EOF", "");
		}
		
		foreach($this->skip as $pattern) {
			if($this->match($pattern) !== false) {
				//printf("skippar på %d\n", $this->pos);
				return null;
			}
		}
		
		foreach($this->tokens as $type => $pattern) {
			$t = $this->match($pattern);
			
			if($t !== false) {
				//printf("token %s (%s) på %d\n", $type, $t, $this->pos);
				return new Token($type, $t);
			}
		}
		
		$x = sprintf("unexpected sign (ascii %d) at position %d",
			ord(substr($this->cur, 0, 1)),
			$this->pos + 1);
		echo "$x\n";
		throw new LexerException($x);
	}
}

class LexerException extends \Exception {
}

class Token {
	var $type, $str;
	
	function __construct($type, $str) {
		$this->type = $type;
		$this->str = $str;
	}
}