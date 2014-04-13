<?php

/**
 * Listens for new web requests and creates an instance of WebBotRequest for every completed request
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

/**
 * Listens for new web requests and creates an instance of WebBotRequest for every completed request
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
class WebBotServer 
{

    // {{{ properties

    /** 
     * Standard response codes for http
     *
     * @var array
     */
    public static $errorCodes = Array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    );



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

    /**
     * Contains attached uri regexps and their priority and callbacks
     *
     * @var array
     */
    private $_attached = Array(
    	1000 => Array( // Priority
    		Array('.*', 'StaticFile') // Regexp, Callback
    	)
    );

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
        if (!defined("CONFIG_webbot_port"))
            throw new Exception("WebBot loaded, but no port configured");
        if (!defined("CONFIG_webbot_ip"))
            define('CONFIG_webbot_ip', '0.0.0.0');

        $this->_handler = new TCPServer(CONFIG_webbot_port, CONFIG_webbot_ip);
        $this->_handler->setCallback('newClient', $this);
    }

    /**
     * Returns singleton instance of WebBotServer
     * 
     * @return WebBotServer WebBotServer singleton
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Gets called when there's a new browser connecting to us, sets the callback
     * for the new handler and waits for a request
     * 
     * @param TCPServer              $serverHandler  The server handler
     * @param SocketHandlerInterface $handler        The new handler
     * @param integer                $error          Not used in this case
     * @return void
     */
    public function newClient($serverHandler, $handler, $error)
    {
        $handler->setCallback('clientData', $this);
    }

    /**
     * Gets called when there's new data from the browser, waits for a
     * complete request, then creates a WebBotRequest
     * 
     * @param SocketHandlerInterface $handler The handler
     * @param string                 $data    New data
     * @param integer                $error   0 if no error
     * @return void
     */
    public function clientData($handler, $data, $error)
    {
        if ($error > 0) {
            if (false !== $handler->request) {
                $handler->request->close();
            }
            
            return true;
        }
        
        if (false !== $handler->request) {
            $handler->request->data($data);
            return true;
        }
        
        if (false === $handler->data)
            $handler->data = '';
        $handler->data .= $data;
        
        if (strlen($handler->data) > 16384) {
            // Kill any request larger than 16KiB
            $handler->close();
            return true;
        }
        
        if (false !== strpos($handler->data, "\r\n\r\n") ||
            false !== strpos($handler->data, "\n\n") || 
            false !== strpos($handler->data, "\r\r")) {
                $handler->request = new WebBotRequest($this, $handler, $handler->data);
                return true;
        }
        return false;
    }

    public function attach($id, $uriRegexp, $callback) {

    }

    public function detach($id) {

    }


}