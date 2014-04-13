<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Views activate webchat users.
 *
 * PHP version 5
 *
 * @category  math
 * @package   Modules
 * @author    bonan bonan <bonan@zenet.org>
 * @copyright 2012 bonan <bonan@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Views activate webchat users.
 *
 * PHP version 5
 *
 * @category  math
 * @package   Modules
 * @author    bonan bonan <bonan@zenet.org>
 * @copyright 2012 bonan <bonan@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

/**
* Includes parse of this module
*
* @var string
*/

include_once('math/parse.php');

class Math implements Module {

    /**
     * The unique id of this module
     *
     * @var string
     */

	protected $id;

    /**
     * The caller of this module
     *
     * @var object
     * @access protected
     */

	protected $parent;

    /**
     * regexp for this module
     *
     * @var object
     * @access public
     */

	public static $mathRegexp = '\d[---\d\.,\(\)\+\*\/\^]+';

    /**
     * The constructor
     *
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */

	public function __construct($parent, $params) {
		$this->id = uniqid();
		$this->parent = $parent;
		$parent->attach('math1_'.$this->id, Array($this,'doMath'), Array('PRIVMSG'), '/^(?P<expr>[---\sa-z\d\.,\(\)\+\*\/\^\!]*)=\s*$/i');
//		$parent->attach('math2_'.$this->id, Array($this,'doMath'), Array('PRIVMSG'), '/^(?P<expr>'.self::$mathRegexp.')$/i');
	}

    /**
     * Stores math information
     *
     * @param array  $expr   Data
     *
     * @return none
     */

	private function _calc($expr) {
		try {
			$expr = str_replace(',', '.', $expr);
            $val = Math\calc($expr);
			return $val;
		} catch(Exception $e) {
		    return $e->getMessage();
			//return false;
		}
	}
	
    /**
     * Does the acually math for this module
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

	public function doMath($parent,$data,$extra) {
		extract($extra);
		extract($regexp);
		
		if (false !== ($ret = $this->_calc($expr))) {
			$parent->privmsg($replyto, $expr . ' = ' . $ret);
		}
		

	}

}
