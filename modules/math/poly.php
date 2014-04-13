<?php
namespace Math;

function mul_vars($a1, $a2) {
	foreach($a2 as $name => $val) {
		if(isset($a1[$name]))
			$a1[$name] += $val;
		else
			$a1[$name] = $val;
	}
	
	return array_filter($a1);
}

function div_vars($a1, $a2) {
	foreach($a2 as $name => $val) {
		if(isset($a1[$name]))
			$a1[$name] -= $val;
		else
			$a1[$name] = $val * -1;
	}
	
	return array_filter($a1);
}

class Polynom {
	var $terms;
	
	function __construct($terms) {
		foreach($terms as $term) {
			ksort($term->vars);
		}
		
		$this->terms = $terms;
		
		$this->repack();
	}
	
	function has_variables() {
		foreach($this->terms as $term) {
			if(count($term->vars))
				return true;
		}
		
		return false;
	}
	
	function coeff_sum() {
		$sum = 0;
		
		foreach($this->terms as $term) {
			$sum += $term->faktor;
		}
		
		return $sum;
	}
	
	function tostring() {
		reset($this->terms);
		
		$str = current($this->terms)->tostring();
		
		while(next($this->terms)) {
			$term = current($this->terms);
			$str .= ($term->faktor > 0 ? "+" : "") .$term->tostring();
		}
		
		if($str == "")
			return "0";
		
		return $str;
	}
	
	function repack() {
		$new_terms = array();
		
		for($i = 0; $i < count($this->terms); $i++) {
			for($k = $i + 1; $k < count($this->terms); $k++) {
				if($this->terms[$i]->vars == $this->terms[$k]->vars) {
					$this->terms[$i]->faktor += $this->terms[$k]->faktor;
					unset($this->terms[$k]);
				}
			}
			
			if($this->terms[$i]->faktor == 0) {
				unset($this->terms[$i]);
			}
			
			$this->terms = array_values($this->terms);
		}
		
		// Summan av alla termer blev 0, dvs. det finns inga termer kvar.
		if(!count($this->terms)) {
			$this->terms = array(
				new Term(
					0,
					array()
				)
			);
		}
	}
	
	function mul($x) {
		$new_terms = array();
		
		foreach($x->terms as $t2) {
			foreach($this->terms as $t1) {
				$new_terms[] = new Term(
					$t1->faktor * $t2->faktor,
					mul_vars($t1->vars, $t2->vars));
			}
		}
		
		return new Polynom($new_terms);
	}
	
	function div($x) {
		$new_terms = array();
		
		foreach($x->terms as $t2) {
			foreach($this->terms as $t1) {
				if($t2->faktor == 0)
					throw new EvaluatorException("division with zero");
				
				$new_terms[] = new Term(
					$t1->faktor / $t2->faktor,
					div_vars($t1->vars, $t2->vars));
			}
		}
		
		return new Polynom($new_terms);
	}
	
	function intdiv($x) {
		if($this->has_variables() || $x->has_variables()) {
			throw new EvaluatorException(
				"neither the numerator or denominator can not contain variables in integer division");
		}
		
		$p = $this->div($x);
		
		foreach($p->terms as $t) {
			$t->faktor = (int)$t->faktor;
		}
		
		return $p;
	}
	
	function add($x) {
		$a = array_map("Math\clone_object", $this->terms);
		$b = array_map("Math\clone_object", $x->terms);
		
		return new Polynom(array_merge($a, $b));
	}
	
	function sub($x) {
		$a = array_map("Math\clone_object", $this->terms);
		$b = array_map("Math\clone_object", $x->terms);
		
		foreach($b as $term) {
			$term->faktor *= -1;
		}
		
		return new Polynom(array_merge($a, $b));
	}
	
	// $this upphöjt till $x
	function pow($x) {
		if($this->has_variables()) {
			$coeff_sum = $x->coeff_sum();
			
			if($x->has_variables() || !my_is_integer($coeff_sum)) {
				throw new EvaluatorException(
					"exponent must be an integer if the base contains variables");
			}

			if(pow(count($this->terms), $coeff_sum) > 100) {
				throw new EvaluatorException(
					"will be too many terms");
			}
			
			$p = $this->add(new Polynom(array()));
			
			for(; $coeff_sum-1 > 0; $coeff_sum--) {
				$p = $p->mul($this);
			}
		
		} else if($x->has_variables()) {
			throw new EvaluatorException(
				"exponent can not contain variables");
		} else {
			$p = new Polynom(array(
				new Term(
					pow($this->coeff_sum(), $x->coeff_sum()),
					array()
				)
			));
		}

		if(strlen($p->tostring()) > 200) {
			throw new EvaluatorException("the answer was too long");
		}
		
		return $p;
	}
}

function my_is_integer($x) {
    return is_numeric($x) ? intval($x) == $x : false;
} 

function clone_object($a) {
	return clone $a;
}

class Term {
	var $faktor;
	var $vars;
	
	function __construct($f, $vs) {
		$this->faktor = $f;
		$this->vars = $vs;
	}
	
	function tostring() {
		if($this->faktor == 0)
			return "";
		
		if($this->faktor == 1 && count($this->vars) > 0) {
			$str = "";
		} else if($this->faktor == -1 && count($this->vars) > 0) {
			$str = "-";
		} else {
			$str = (string)$this->faktor;
		}
		
		foreach($this->vars as $name => $val) {
			if($val != 1) {
				$str .= $name . "^" . $val;
			} else {
				$str .= $name;
			}
		}
		
		return $str;
	}
}