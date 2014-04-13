<?php

/**
 * Takes a http request and returns data
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
 * Takes a http request and returns data
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
class WebBotRequest
{
    /** 
     * The handler
     *
     * @var SocketHandlerInterface
     */
    protected $handler;
    /** 
     * Parent Server object
     *
     * @var WebBotServer
     */
    protected $parent;
    /**
     * Method used
     * 
     * @var string
     */
    protected $method;
    /**
     * Requested URI
     * 
     * @var string
     */
    protected $uri;
    /**
     * Query string
     * 
     * @var string
     */
    protected $queryString;
    /**
     * Headers
     * 
     * @var array
     */
    protected $headers;

    /**
     * The constructor, sets properties and calls handleRequest()
     * 
     * @param WebBotServer              $parent   parent server object
     * @param SocketHandlerInterface    $handler  handler connected to the browser
     * @param string                    $request  the request
     * @return void
     */
    public function __construct($parent, $handler, $request)
    {
        $this->parent  = $parent;
        $this->handler = $handler;
        $this->headers = Array();
        $this->handleRequest($request);
    }
    
    /**
     * Parses the request and sends a response
     * TODO: Update this method to allow modules and other things to attach() to
     *       certain URI:s (as they do with Irc)
     * 
     * @param string $request The request
     * @return boolean True on success
     */
    public function handleRequest($request) {
        $phase          = 0;
        $method         = '';
        $uri            = '';
        $proto          = '';
        $queryString    = '';
        $header         = Array();
        
        $pos = 0;
        
        $rows = explode("\n", $request);
        $phase = 0;
        foreach ($rows as $row) {
            switch ($phase) {
                case 0:
                    $row = trim($row);
                    $phase++;
                    // Set status
                    list($method, $uri, $proto) = explode(' ', $row, 3);
                    break;
                
                case 1:
                    $row = trim($row);
                    if (empty($row)) { 
                        $phase++; continue; 
                    }
                    list($name, $value) = explode(': ', $row, 2);
                    $header[strtolower($name)] = $value;
                    break;
                
                default:
                    break;
            }
        }
        
        if (substr($proto, 0, 7) != 'HTTP/1.' || substr($proto, 7) != '0' && substr($proto, 7) != '1')
            $this->respond(505, "<h1>HTTP Version not allowed</h1>", "text/html");



        // Check for 405 Method not allowed
        
        if (false !== strpos($uri, '?')) {
            list($uri, $queryString) = explode('?', $uri, 2);
        }
        
        if (false !== strpos($uri, '/..')) {
            $this->respond(400, "<h1>Bad Request</h1>", 'text/html');
            return false;
        }
        
        if ($phase == 2) {
            
            if ($uri != '/' && file_exists('htdocs/'.$uri)) {
                $contentType = 'text/plain';
                if (false !== strpos($uri, '.')) {
                    $ext = substr($uri, strrpos($uri, '.'));
                    if (isset(StaticFile::$mime[strtolower($ext)])) {
                        $contentType = StaticFile::$mime[strtolower($ext)];
                    }
                }
                
                $this->respond(200, file_get_contents('htdocs/'.$uri), $contentType);
            }
            
            $r = Array(
                'method' => $method,
                'uri' => $uri,
                'proto' => $proto,
                'header' => $header,
                'queryString' => $queryString
            );
            $o = print_r($r, true);
            $this->respond(200, <<<EOF
<html>
    <head>
        <title>neotor WebBot</title>
    </head>
    <body>
        Your request looks like this:<br />
        <pre>
$o
        </pre>
        
        Also, here's a picture of a cat:
        <br />
        <img src="/cat.jpg" alt="DAT CAT" />
        <br />
        <form method="post"><input name="foo" value="bar" /><input type="submit"></form>
    </body>
</html>

            
            
EOF
, 'text/html');
            return true;
        }
        
    }
    
    public function data($data)
    {
        
    }
    
    public function close()
    {
        
    }
    
    
    /**
     * Send a response to the browser
     * 
     * @param integer $status Status code
     * @param string  $html   HTML code to send
     * @param string  $contentType content code to send
     * @return void
     */
    public function respond($status, $html, $contentType)
    {
        $statusMsg = "Internal Server Error";
        if (isset(WebBotServer::$errorCodes[$status])) {
            $statusMsg = WebBotServer::$errorCodes[$status];
        }
        
        // Transfer-Encoding: chunked
        // http://en.wikipedia.org/wiki/Chunked_transfer_encoding
        // dechex(len)\r\n CHUNK \r\n0
        
        $this->handler->put("HTTP/1.1 $status $statusMsg\r\n");
        $this->handler->put("Content-type: $contentType\r\n");
        $this->handler->put("Content-Length: ".strlen($html)."\r\n");
        $this->handler->put("Connection: Close\r\n");
        $this->handler->put("Date: ".date('r')."\r\n");
        $this->handler->put("Server: neotor/1.1 WebBot/0.1\r\n");
        $this->handler->put("\r\n");
        $this->handler->put($html);
        $this->handler->close();

    }
}

/**
 * Thrown whenever an invalid http request is detected
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
class WebBotInvalidRequestException extends Exception
{
    
}
