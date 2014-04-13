<?php

namespace Math;
include_once "lex.php";
include_once "ast.php";

/*
Aktuell grammatik:

expr ::= term (("+" | "-") term)*
term ::= negfactorial (("*" | "/" | "\") negfactorial)*
term ::= negfactorial (factorial)*
negfactorial ::= ["-"] factorial
factorial ::= factor ("!")+
factorial ::= factor ["^" negfactorial]
factor ::= "(" expr ")"
factor ::= (COS | SIN) "(" expr ")"
factor ::= (RAND | PI | INT10 | INT16 | INT2 | FLOAT | VARI)
*/

function calc($expr) {
    $lexer = new Lexer(pad_parenthesis($expr));
    $parser = new Parser($lexer);
	$tree = $parser->start();
	$polynom = $tree->value();
	$val = $polynom->tostring();
    return $val;
}

class Parser {
	var $token;
    protected $lexer;
	
	function __construct($lexer) {
		$this->lexer = $lexer;
	}

	function start() {
		$this->token = $this->lexer->next_token();
		return $this->expr();
	}
	
	function accept($type) {
		if($this->token->type == $type) {
			//printf("accepterar %s\n", $type);
			$this->token = $this->lexer->next_token();
		} else {
			throw new ParserException(array($type), $this->token->type);
		}
	}

	function expr() {
		$node = $this->term();
		
		while(true) {
			$token = $this->token->type;
            $mtoken = "Math\\".$token;
			
			switch($token) {
				case "ADD":
				case "SUB":
					$this->accept($token);
					$node = new $mtoken($node, $this->term());
					break;
				case "EOF":
				default:
					return $node;
			}
		}
	}
	
	function term() {
		$node = $this->negfactorial();
		
		while(true) {
			$token = $this->token->type;
            $mtoken = "Math\\".$token;
			
			switch($token) {
				// 1, 2, 3, 4
				case "MUL":
				case "DIV":
				case "INTDIV":
					$this->accept($token);
					$node = new $mtoken($node, $this->negfactorial());
					break;
				// 5
				case "LPAR":
				case "COS":
				case "SIN":
				case "RAND":
				case "E":
				case "C":
				case "PI":
				case "INT10":
				case "INT2":
				case "INT16":
				case "FLOAT":
				case "VARI":
					$node = new Mul($node, $this->factorial());
					break;
				case "EOF":
				default:
					return $node;
			}
		}
	}
	
	function negfactorial() {
		if($this->token->type == "SUB") {
			$this->accept("SUB");
			return new Sub(new Int10("0"), $this->factorial());
		} else {
			return $this->factorial();
		}
	}
	
	function factorial() {
		$node = $this->factor();
		
		switch($this->token->type) {
			case "FAC":
				while($this->token->type == "FAC") {
					$node = new Fac($node);
					$this->accept("FAC");
				}
				break;
			case "POW":
				$this->accept("POW");
				$node = new Pow($node, $this->negfactorial());
				break;
		}
		
		return $node;
	}

	function factor() {
		$token = $this->token->type;
        $mtoken = "Math\\".$token;
		$str = $this->token->str;
		
		switch($token) {
			case "LPAR":
				$this->accept("LPAR");
				$node = $this->expr();
				$this->accept("RPAR");
				return $node;
			case "COS":
			case "SIN":
				$this->accept($token);
				$this->accept("LPAR");
				$node = new $mtoken($this->expr());
				$this->accept("RPAR");
				return $node;
			case "PI":
			case "E":
			case "C":
			case "RAND":
				$this->accept($token);
				return new $mtoken();
			case "INT10":
			case "INT2":
			case "INT16":
			case "FLOAT":
			case "VARI":
				$this->accept($token);
				return new $mtoken($str);
			default:
				throw new ParserException(
					array("LPAR", "COS", "SIN", "PI", "E", "C", "RAND", "INT10", "INT2",
						"INT16", "FLOAT", "VARI"),
					$token);
		}
	}
	
	function funcparam() {
		$this->accept("LPAR");
		$node = $this->expr();
		$this->accept("RPAR");
		return $node;
	}
}

// se till så det är lika många "(" som ")"
function pad_parenthesis($str) {
	$freqs = count_chars($str);
	$diff = $freqs[ord("(")] - $freqs[ord(")")];
	return str_repeat("(", $diff >= 0 ? 0 : ($diff * -1)) .
	       $str .
	       str_repeat(")", $diff <= 0 ? 0 : $diff);
}

class ParserException extends \Exception {
	var $expected, $found;
	
	function __construct($expected, $found) {
		$this->expected = $expected;
		$this->founds = $found;
	}
	
	function tostring() {
		return sprintf("Found %s, expected %s\n",
			$this->founds, implode(", ", $this->expected));
	}
}
?>
