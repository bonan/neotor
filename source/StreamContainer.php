<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class and helper function for managing stream handlers
 * 
 * PHP version 5
 * 
 * @category  Streams
 * @package   StreamHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */

/**
 * Interface for stream handlers, defines which methods should be available
 *
 * PHP version 5
 *
 * @category  Streams
 * @package   StreamHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
interface StreamHandler
{

    /**
     * Gets called when a stream has new data to read
     * 
     * @return void
     */
    public function hasData();
    /**
     * Returns the resource identifier
     * 
     * @return resource Resource identifier for stream
     */
    public function getStream();
    /**
     * Gets called when the stream is availble for writing
     * 
     * @return void
     */
    public function writeData();
    /**
     * Returns the number of bytes availble for writing
     * 
     * @return int Number of bytes in write buffer
     */
    public function getWriteBufferCount();
}

/**
 * Helper function to get singleton instance of StreamContainer
 * 
 * @return StreamContainer
 */
function streamContainer()
{
    return StreamContainer::getInstance();
}

/**
 * Class to manage active streamhandlers
 *
 * PHP version 5
 *
 * @category  Streams
 * @package   StreamHandler
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */

class StreamContainer
{

    // {{{ properties

    /** 
     * Contains the instance.
     *
     * @var instance
     */
    private static $_instance;

    /**
     * Keeps track of handlers.
     *
     * @var array
     */
    private $_handlers;

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
        $this->_handlers = Array();
    }

    /**
     * Returns singleton instance of StreamContainer
     * 
     * @return StreamContainer StreamContainer singleton
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Adds a handler to the list of active stream handlers
     * 
     * @param StreamHandler $handler The handler to be added
     *
     * @return bool True on success
     */
    public function addHandler($handler)
    {
        foreach ($this->_handlers as $tmpHandler) {
            if ($tmpHandler==$handler || !($handler instanceof StreamHandler)) {
                return false;
            }
        }

        $this->_handlers[] = $handler;
        return true;
    }

    /**
     * Removes a handler from the list of active stream handlers
     * 
     * @param StreamHandlerInterface $handler The handler to be remove
     * 
     * @return bool True on success
     */
    public function removeHandler($handler)
    {
        foreach ($this->_handlers as $tmpKey=>$tmpHandler) {
            if ($tmpHandler==$handler) {
                unset($this->_handlers[$tmpKey]);
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an array of streams available for reading
     * 
     * @return Array array of resource identifiers
     */
    public function getReadStreams()
    {
        $s = Array();
        foreach ($this->_handlers as $o) {
            if (is_resource($o->getStream())) {
                $s[] = $o->getStream();
            }
        }
        return $s;
    }

    /**
     * Returns an array of streams with data available for writing
     * 
     * @return Array array of resource identifiers
     */
    public function getWriteStreams()
    {
        $s = Array();
        foreach ($this->_handlers as $o) {
            if (($o->getWriteBufferCount() > 0) && is_resource($o->getStream())) {
                $s[] = $o->getStream();
            }
        }
        return $s;
    }

    /**
     * Returns the handler associated with a stream resource identifier
     * 
     * @param resource $stream Stream resource
     *
     * @return StreamHandlerInterface Handler associated with stream
     */
    public function getHandler($stream)
    {
        foreach ($this->_handlers as $o) {
            if ($o->getStream() == $stream) {
                return $o;
            }
        }
    }
}
