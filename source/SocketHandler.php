<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Base class for socket handlers
 * 
 * PHP version 5
 * 
 * @category  Sockets
 * @package   SocketHandler
 * @author    Bj�rn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Bj�rn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */

/**
 * Base class for socket handlers
 *
 * PHP version 5
 *
 * @category  Sockets
 * @package   SocketHandler
 * @author    Bj�rn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Bj�rn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
class SocketHandler implements StreamHandler
{

    protected $socket;
    protected $callback;
    protected $writeCallback;
    protected $writeBuffer;
    protected $errno=0;
    protected $errstr;
    protected $meta;
    private   $_close = false;

    /**
     * The constructor
     * 
     * @param resource $socket 
     * @return void
     */
    public function __construct($socket)
    {
        $this->meta   = Array();
        $this->socket = $socket;
        $this->buffer = '';
        $this->callback = false;
        $this->writeCallback = false;
        streamContainer()->addHandler($this);
    }

    /**
     * Sets meta-variables for handler, set with $handler->variable = value
     * 
     * @param string $var 
     * @param mixed $value
     * @return void
     */
    public function __set($var, $value)
    {
        $this->meta[$var] = $value;
    }
    
    /**
     * Gets meta-variable, accessed with $handler->variable
     * 
     * @param string $var
     * @return mixed Value of meta-variable
     */
    public function __get($var)
    {
        if (isset($this->meta[$var]))
            return $this->meta[$var];
        return false;
    }

    /**
     * Closes the socket and cleans up
     * 
     * @return void
     */
    public function close($error = false) {
        if (!$error && $this->getWriteBufferCount() > 0) {
            $this->_close = true;
            return false;
        }
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }    
        streamContainer()->removeHandler($this);
        return true;
    }

    /**
     * Gets called when the socket has new data
     * 
     * @return bool True on successful read
     */
    public function hasData()
    {
        return $this->readData();
    }

    /**
     * Gets called from hasData() to read the data availble
     * 
     * @return bool True on successful read
     */
    protected function readData()
    {
        return $this->handleRead(fread($this->socket, 4096));
    }
    
    /**
     * Gets called from readData() with data that was read. Performs checks if data is valid and connection wasn't closed 
     * 
     * @param string $data Data read from socket
     * @return bool True on successful read
     */
    protected function handleRead($data) {

        if ('' === $data) {
            $this->callback(false, 1);
            $this->close(true);
            return false;
        }
        
        if (false === $data) {
            if ($this->errno == 10035 || $this->errno == 115)
                return false;

            $this->callback(false, ($this->errno>0?$this->errno:1));
            $this->close(true);
            return false;
        }
        if (strlen($data) > 0) {
            $this->callback($data);
            return true;
        }
        return false;
    }

    /**
     * Gets called when the socket is availble for writing
     * 
     * @return bool True on successful write
     */
    public function writeData()
    {
        if (is_array($this->writeCallback)) {
            call_user_func($this->writeCallback, $this);
	}
        if (strlen($this->writeBuffer) > 0) {
            $bytes = fwrite($this->socket,
                            $this->writeBuffer,
                            strlen($this->writeBuffer));
                              
            if ($bytes === FALSE) {
                $this->callback(false, $this->errno);
                $this->close(true);
                return false;
            }
            if ($bytes > 0) {
                if ($bytes == strlen($this->writeBuffer)) {
                    $this->writeBuffer = '';
                } else {
                    $this->writeBuffer = substr($this->writeBuffer, $bytes);
                }
                return true;
            }
        }
        if ($this->_close == true) {
            $this->close();
        }
        
        return false;
    }

    /**
     * Returns the number of bytes in write buffer
     * 
     * @return int Number of bytes in write buffer
     */
    public function getWriteBufferCount()
    {
        $wb = strlen($this->writeBuffer) > 0;
        $wb += $this->_close;
        if (false !== $this->writeCallback) {
            $wb += call_user_method($this->writeCallback, $this, true);
        }

        return $wb;
    }

    /**
     * Returns the socket resource identifier
     * 
     * @return resource Socket resource identifier
     */
    public function getStream()
    {
        return $this->socket;
    }

    /**
     * Sets which method should be called whenever new data has been read
     * 
     * @param string $method
     * @param Object $object
     * @return void
     */
    public function setCallback($method, $object = false)
    {
        $this->callback = (false === $object || !is_object($object)) ? $method : Array($object, $method);
    }

    /**
     * Sets which method should be called whenever data should be written
     * The callback method should take a second parameter which is set to true
     * if we're asking wether there is data to be written or not.
     * 
     * @param string $method
     * @param Object $object
     * @return void
     */
    public function setWriteCallback($method, $object = false)
    {
        $this->writeCallback = (false === $object || !is_object($object)) ? $method : Array($object, $method);
    }


    /**
     * Calls the callback when new data has been read, or an error occurs
     * 
     * @param mixed $data Data that has been read
     * @param int $error Error id
     * @return void
     */
    protected function callback($data, $error = 0)
    {
        if ($this->callback) {
            call_user_func_array($this->callback, Array($this, $data, $error));
        }
    }

    /**
     * Saves data to the writeBuffer
     * 
     * @param string $data
     * @return void
     */
    public function put($data)
    {
        $this->writeBuffer .= $data;
    }
    
    /**
     * Returns the ip address and port for the local end of the socket
     * 
     * @return Array Array of ip and port
     */
    public function getIpPort()
    {
        $ip = $port = NULL;
        list($ip,$port) = explode(':', stream_socket_get_name($this->socket, false));
        return Array($ip, $port);
    }
    /**
     * Returns the ip address and port for the remote end of the socket
     * 
     * @return Array Array of ip and port
     */
    public function getPeerIpPort()
    {
        $ip = $port = NULL;
        list($ip,$port) = explode(':', stream_socket_get_name($this->socket, true));
        return Array($ip, $port);
    }

}

/**
 * Extends SocketHandler for sockets that should be read line-by-line
 *
 * PHP version 5
 *
 * @category  Sockets
 * @package   SocketHandler
 * @author    Bj�rn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Bj�rn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
class BufferedSocketHandler extends SocketHandler
{
    /**
     * Reads next available line and passes it to handleRead()
     * 
     * @return Boolean True on success
     */
    protected function readData()
    {
        return $this->handleRead(fgets($this->socket, 4096));
    }
}
