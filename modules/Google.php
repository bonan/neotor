<?php
/**
 * 
 *
 * PHP version 5
 *
 * @category  google
 * @package   Modules
 * @author    Falgern <falgern@quakenet.org>
 * @copyright 2013 Falgern <falgern@quakenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * 
 *
 * PHP version 5
 *
 * @category  google
 * @package   Modules
 * @author    Falgern <falgern@quakenet.org>
 * @copyright 2013 Falgern <falgern@quakenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

/**
* Includes parse of this module
*
* @var string
*/


class Google implements Module {

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


    /**
     * The constructor
     *
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */

	public function __construct($parent, $params) {
		$this->id = uniqid();
		$this->parent = $parent;
		        $parent->attach(
            'google_'.$this->id, 
            Array($this,'fetchSearch'), 
            Array('PRIVMSG'), '/^!g(?:\s+(?P<term>.*))?$/i'
        );
	}
	
    public function fetchSearch($parent, $data, $extra)
    {
        extract($extra);
        if (!isset($regexp['term'])){
            $this->parent->privmsg($replyto, "[Google] Requires a string");
        }
        else
        {
        $regexp['term'] = urlencode($regexp['term']);
        $http = new httpSession('www.google.com');
        $http->setMaxLength(4096);
        $http->getPage(
            array($this, 'replySearch'),
            array_merge(
                $extra, 
                Array('parent' => $parent),
                Array('Regexp' => $regexp)
            ),
            '/search?hl=en&q='.
            $regexp['term']
        );
        }
    }
    public function replySearch($http, $page, $vars)
    {
        extract($vars);
        preg_match_all('/<a[^>]*?href="\/url\?q\=([^"]*)&amp;sa=U&amp;ei=[^>]*?>(.*?)<\/a>/i',$page->data, $data);
        $data[2][0] = strip_tags(html_entity_decode($data[2][0]));
        if (count($data[2]) > 0) {
            $parent->privmsg(
                $replyto, 
                sprintf(
                    "[Google] %s - %s",
                    $data[2][0],
                    $data[1][0]
                )
            );
        } else {
            $parent->privmsg($replyto, "[Google] An error occured.");
        }
    }






}
