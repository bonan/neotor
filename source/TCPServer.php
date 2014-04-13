<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handling of tcp listening sockets
 * 
 * PHP version 5
 * 
 * @category  Sockets
 * @package   SocketHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */

/**
 * Server for TCP that listens to a specified port and makes a callback when a new client connects
 *
 * PHP version 5
 *
 * @category  Sockets
 * @package   SocketHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
 
class TCPServer extends SocketHandler
{
    /**
     * The constructor, creates the socket and starts listening
     * 
     * @param integer $port Port to bind to
     * @param string $bind IP address to bind to
     * @return
     */
    public function __construct($port, $bind='0.0.0.0')
    {
        $socket = stream_socket_server(
            "tcp://$bind:$port", 
            $this->errno, 
            $this->errstr
        );
        
        
        if (false === $socket) {
            throw new TCPServerException($this->errno);
        }
        
        $this->meta['bind'] = $bind;
        $this->meta['port'] = $port;
        
        parent::__construct($socket);
        
    }
    
    
    /**
     * Accepts a new connection, creates a handler for it and passes it to the callback
     * 
     * @return Boolean true on success
     */
    public function readData()
    {
        $newSock = stream_socket_accept($this->socket);
        $handler = 'SocketHandler';
        if (isset($this->meta['handler']) && class_exists($this->meta['handler'])) {
            $handler = $this->meta['handler'];
        }
        $newHandler = new $handler($newSock);
        $this->callback($newHandler);
        return true;
    }
    
}

/**
 * Exception thrown when TCPServer encouters an error
 *
 * PHP version 5
 *
 * @category  Sockets
 * @package   SocketHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
class TCPServerException extends Exception
{
    /**
     * The constructor
     * 
     * @param mixed $msg Error message 
     * @return void
     */
    public function __construct($msg)
    {
        parent::__construct($msg);
    }
}
