<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handling of buffered and binary tcp connections
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
 * Client for TCP that connects non-blocking to a host on the specified port
 * Non-buffered, calls the callback method with a stream of binary data as read
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
class TCPClient extends SocketHandler
{
    /**
     * The constructor, creates the socket and establishes the connection 
     * 
     * @param string $host Host to connect to
     * @param integer $port Port to connect to
     * @param bool $ssl True if an ssl connection, defaults to false
     * @param string $bind IP address to bind to
     * @param integer $bindport Port number to bind to
     * @return
     */
    public function __construct($host, $port, $ssl=false, $bind='', $bindport=0, $verifyssl = false)
    {

        $options = Array('socket' => Array());
        if (!empty($bind) || ( $bindport > 0 && $bindport < 65536 )) {
            $options['socket']['bindto'] = ($bind=='' ? '0' : $bind) . ($bindport > 0 ?  ':' . $bindport : '');
        }
        
        if ($ssl) {
            $options['ssl']['verify_peer'] = $verifyssl;
            $options['ssl']['allow_self_signed'] = !$verifyssl;
        }

        $context = stream_context_create($options);

        $socket = stream_socket_client(
            ($ssl?'ssl':'tcp')."://$host:$port",
            $this->errno,
            $this->errstr,
            30,
            ($ssl?0:STREAM_CLIENT_ASYNC_CONNECT) | STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (false === $socket) {
            throw new TCPClientException($this->errno);
        }
        
        $this->meta['host'] = $host;
        $this->meta['port'] = $port;
        
        if (false === stream_set_blocking($socket, 0)) {
            throw new TCPClientException($this->errno);
        }
        
        parent::__construct($socket);
        
    }

}

/**
 * Extends TCPClient, reads buffered and calls the callback method for every line of data received
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
class TCPBufferedClient extends TCPClient
{
    /**
     * Overloaded function to read a new line of data
     * 
     * @return bool True on success
     */
    protected function readData()
    {
        return $this->handleRead(fgets($this->socket, 4096));
    }
}

/**
 * Exception thrown when TCPClient encouters an error
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
class TCPClientException extends Exception
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
