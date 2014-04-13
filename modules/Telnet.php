<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Creates as telnet server instance
 *
 * PHP version 5
 *
 * @category  Telnet
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

/**
 * Module class, there is no real use for this yet
 *
 * PHP version 5
 *
 * @category  Telnet
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class Telnet implements Module
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
		$telnet = TelnetServer::getInstance();
	}
    
}

/**
 * 
 *
 * PHP version 5
 *
 * @category  Telnet
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class TelnetServer 
{
    /** 
     * Contains the instance.
     *
     * @var instance
     */
    private static $_instance;

    /**
     * Keeps track of listening handler.
     *
     * @var array
     */
    private $_handler;

    // }}}
    /**
     * Prevents instance from being cloned
     *
     * @return none
     */
    private function __clone()
    {
    }
    /**
     * The constructor
     */
    private function __construct()
    {
        if (!defined("CONFIG_telnet_port"))
            throw new Exception("Telnet loaded, but no port configured");
        if (!defined("CONFIG_telnet_bind"))
            define('CONFIG_telnet_bind', '0.0.0.0');

        $this->_handler = new TCPServer(CONFIG_telnet_port, CONFIG_telnet_bind);
        $this->_handler->handler = 'BufferedSocketHandler';
        $this->_handler->setCallback('newClient', $this);
    }

    /**
     * Returns singleton instance of TelnetServer
     * 
     * @return TelnetServer TelnetServer singleton
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Gets called when there's a new client connecting to us, sets the callback
     * for the new handler and waits for a request
     * 
     * @param TCPServer              $serverHandler  The server handler
     * @param SocketHandlerInterface $handler        The new handler
     * @param integer                $error          Not used in this case
     * @return void
     */
    public function newClient($serverHandler, $handler, $error)
    {
        new TelnetClient($handler);
    }
}

/**
 * 
 *
 * PHP version 5
 *
 * @category  Telnet
 * @package   Modules
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class TelnetClient
{
    /** 
     * The handler
     *
     * @var SocketHandlerInterface
     */
    protected $handler;

    /**
     * Partyline object
     * 
     * @var partyline
     */
    protected $partyline;
     
    /**
     * The constructor, sets properties and calls handleRequest()
     * 
     * @param TelnetServer              $parent   parent server object
     * @param SocketHandlerInterface    $handler  handler connected to the browser
     * @param string                    $request  the request
     * @return void
     */
    public function __construct($handler)
    {
        $this->handler = $handler;
        $this->handler->setCallback('readData', $this);
        $this->partyline = new partyline(
            0, 
            'telnet', 
            $this
        );
    }

    public function readData($handler, $data, $error)
    {
        if ($error > 0) {
            return;
        }
        
        $data = str_replace(Array("\r","\n"), Array("",""), $data);
        if (!empty($data)) {
            $this->partyline->get($data);
        }
    }

    public function put($data, $silent = 0, $norn = 0)
    {
        $this->handler->put($data . ($norn == 0?"\r\n":''));

    }

    public function quit()
    {
        $this->handler->close();
	return;
    }

}
