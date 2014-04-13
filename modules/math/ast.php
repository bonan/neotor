<?php
namespace Math;

/*
expr
 binexpr
  ADD, SUB, MUL, DIV, INTDIV, POW
 num
  INT16, INT10, INT2, FLOAT
  func
   COS, SIN
 constant
  PI, RAND
*/

include_once "poly.php";

// Basklass till allt

abstract class Expr {
	function value() {
		throw new EvaluatorException(
			"value() saknas i " . get_class($this));
	}
	
	abstract function base();
	
	/*
	function tostring() {
		$value = $this->value();
		$base = $this->base();
		
		if(intval($value) != $value) {
			return $value;
		} else {
			switch($base) {
				case 16:
					return "0x" . strtoupper(base_convert($value, 10, $base));
				case 2:
					return base_convert($value, 10, 2) . "b";
				case 10:
				default:
					return $value;
			}
		}
	}
	*/
}

// BinExprs har två parametrar

class BinExpr extends Expr {
	var $left, $right;
	
	function __construct($left, $right) {
		$this->left = $left;
		$this->right = $right;
	}
	
	function depth() {
		return 1 + max($this->left->depth(), $this->right->depth());
	}
	
	function base() {
		return max($this->left->base(), $this->right->base());
	}
	
	function dump_tree($indent) {
		echo $indent . get_class($this) . "\n";
		$this->left->dump_tree($indent . " ");
		$this->right->dump_tree($indent . " ");
	}
}

class Sub extends BinExpr {
	function value() {
		return $this->left->value()->sub($this->right->value());
	}
}

class Add extends BinExpr {
	function value() {
		return $this->left->value()->add($this->right->value());
	}
}

class Mul extends BinExpr {
	function value() {
		return $this->left->value()->mul($this->right->value());
	}
}

class Div extends BinExpr {
	function value() {
		return $this->left->value()->div($this->right->value());
		
		/*
		$right = $this->right->value();
		
		if($right == 0) {
			throw new EvaluatorException("division med noll");
		}
		
		return $this->left->value() / $right;
		*/
	}
}

class IntDiv extends Div {
	function value() {
		return $this->left->value()->intdiv($this->right->value());
	}
}

class Pow extends BinExpr {
	function value() {
		return $this->left->value()->pow($this->right->value());
	}
}

// Constants har inga parametrar

class Constant extends Expr {
	function depth() {
		return 1;
	}
	
	function base() {
		return 0; // bryr oss inte: konstanterna är floats.
	}
	
	function dump_tree($indent) {
		echo $indent . get_class($this) . " " . $this->val . "\n";
	}
}

class Rand extends Constant {
	function value() {
		return new Polynom(array(
			new Term(
				mt_rand()/getrandmax(),
				array()
			)
		));
	}
}

class Pi extends Constant {
	function value() {
		return new Polynom(array(
			new Term(
				M_PI,
				array()
			)
		));
	}
}

class E extends Constant {
	function value() {
		return new Polynom(array(
			new Term(
				M_E,
				array()
			)
		));
	}
}
class C extends Constant {
	function value() {
		return new Polynom(array(
			new Term(
				299792458,
				array()
			)
		));
	}
}

// Nums har en parameter

class Num extends Expr {
	var $val;
	
	function __construct($val) {
		$this->val = $val;
	}
	
	function depth() {
		return 1;
	}
	
	function base() {
		return 0; // om inget anges bryr vi oss inte
	}
	
	function dump_tree($indent) {
		echo $indent . get_class($this) . " " . $this->val . "\n";
	}
}

class Func extends Num {
	function depth() {
		return 1 + $this->val->depth();
	}
	
	function dump_tree($indent) {
		echo $indent . get_class($this) . "\n";
		$this->val->dump_tree($indent . " ");
	}
}

class Cos extends Func {
/*	
	function value() {
		return round(cos($this->val->value()), 10);
	}
*/	
}

class Sin extends Func {
/*	
	function value() {
		return round(sin($this->val->value()), 10);
	}
*/	
}

class Int10 extends Num {
	function value() {
		return new Polynom(array(
			new Term(
				(int)$this->val,
				array()
			)
		));
	}
	
	function base() {
		return 10;
	}
}

class Int16 extends Num {
	function value() {
		return new Polynom(array(
			new Term(
				intval(substr($this->val, 2), 16),
				array()
			)
		));
	}
	
	function base() {
		return 16;
	}
}

class Int2 extends Num {
	function value() {
		return new Polynom(array(
			new Term(
				intval($this->val, 2),
				array()
			)
		));
	}
	
	function base() {
		return 2;
	}
}

class Float extends Num {
	function value() {
		return new Polynom(array(
			new Term(
				(float)str_replace(",", ".", $this->val),
				array()
			)
		));
	}
}

class Vari extends Num {
	function value() {
		return new Polynom(array(
			new Term(
				1,
				array($this->val => 1)
			)
		));
	}
}

class Fac extends Func {
	/*
	function value() {
		$value = $this->val->value();
		
		if(intval($value) == $value && $value >= 0) {
			$x = 1;
			for(; $value > 0 && $x < 1E300; $x *= $value, $value--);
			return $x;
		}
		
		throw new EvaluatorException(
			"Fakultet är bara definierat för positiva");
	}
	*/
}

class EvaluatorException extends \Exception {
}
?>
