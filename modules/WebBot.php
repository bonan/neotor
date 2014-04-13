<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Creates as http server instance
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

include_once('WebBot/WebBotServer.php');
include_once('WebBot/WebBotRequest.php');
include_once('WebBot/StaticFile.php');


/**
 * Module class, there is no real use for this yet
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class WebBot implements Module
{
    /** 
     * Unique id used to identify instace
     *
     * @var string
     */
	protected $id;
    /** 
     * Parent Irc network object
     *
     * @var Irc
     */
	protected $parent;

    /**
     * The constructor
     *
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */

	public function __construct($parent, $params) {
		$this->id = uniqid();
		$this->parent = $parent;
		$webbot = WebBotServer::getInstance();
	}
    
}
