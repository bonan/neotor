<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHP-class to make advanced http-requests, best suitable for CLI-applications
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

/**
 * Exception for HTTP errors
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class HttpException extends exception
{
    /**
     * The constructor
     *
     * @param string $message Message to be used when throwing exceptions.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**
 * Exception for HTTP socket errors.
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class HttpSocketException extends exception
{
    /**
     * The constructor
     *
     * @param string $message Message to be used when throwing exceptions.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**
 * PHP-class to make advanced http-requests, best suitable for CLI-applications
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class HttpSession
{
    private $_socket;
    private $_headers;
    private $_hostname;
    private $_port = 80;
    private $_method;
    private $_uri;
    private $_phase = -1;
    private $_callback;
    private $_callbackvars;
    private $_cookies;
    private $_errno;
    private $_errstr;
    private $_debug;
    private $_maxLength;
    private $_ssl;
    private $_sslverify;

    /**
     * The constructor
     *
     * @param string $hostname Hostname to make http call to
     * @param int    $port     Port to make http call to
     *                         defaults to 80.
     */
    public function __construct($hostname, $port = 80, $ssl = false, $sslverify=true)
    {
        $this->id = uniqid();
        $this->_ssl = $ssl;
        $this->_sslverify = $sslverify;
        $this->setHostname($hostname, $port);
        $this->setHeader('User-Agent', 'Lynx/2.5');
        $this->setMaxLength(16384);
        $this->reset();
    }

    /**
     * Resets http object
     *
     * @return none
     *
     */
    public function reset() 
    {
        /*** Reset socket ***/
        if (is_object($this->_socket)) {
            $this->_socket->close();
        }
        $this->_socket = '';

        /*** Empty debug ***/
        $this->_debug = Array('in' => Array(), 'out' => Array());

        /*** Empty headers, except User-Agent and Host ***/
        $this->_headers = Array('User-Agent' => $this->_headers['User-Agent'],
                                'Host' => $this->_headers['Host']);

        /*** Default values for headers ***/
        $this->setHeader('Accept', '*/*');
        $this->setHeader('Connection', 'Close');
        $this->setHeader('Cache-Control', 'none');
        /*********************************/


        $this->_method = 'GET';
        $this->_uri = '';
        $this->postData = '';
        $this->_phase = -1;
    }

    /**
     * Destructor
     */
    public function __destruct() 
    {
        $this->reset();
    }

    /**
     * Sets hostname and port for the http call.
     *
     * @param string $hostname Hostname to make http call to.
     * @param int    $port     Port to make http call to
     *                         defaults to 80.
     *
     * @return none
     *
     */
    public function setHostname($hostname, $port = 80) 
    {
        $this->_hostname = $hostname;
        $this->_port = $port;
        $this->setHeader('Host', $hostname);
    }

    /**
     * Sets maxlength of http reply.
     *
     * @param int $length Legth of reply
     *
     * @return none
     */
    public function setMaxLength($length) 
    {
        $this->_maxLength = $length;
 
    }

    /******* HEADERS *******/
    /**
     * Removes illegal characters from the headers
     *
     * @param string $header The headers.
     *
     * @return string Fixed headers.
     */
    private function _fixHeader($header) 
    {
        $illegal = Array("\r", "\n");
        return str_replace($illegal, "", $header);
    }

    /**
     * Sets headers
     * 
     * @param string $name  Name of header.
     * @param string $value Value of header.
     *
     * @return boolean Returns boolean
     */ 
    public function setHeader($name, $value) 
    {
        if (empty($value) && isset($this->_headers[$this->_fixHeader($name)])) {
            unset($this->_headers[$this->_fixHeader($name)]);
        } else {
            $this->_headers[$this->_fixHeader($name)] = $this->_fixHeader($value);
        }
        return true;
    }

    /**
     * Sets useragent to use during the http call
     *
     * @param string $agent String containing the name of the user agent.
     *
     * @return none
     */ 
    public function setUserAgent($agent) 
    {
        $this->setHeader('User-Agent', $agent);
    }

    /**
     * Sets referer to be used for http call
     *
     * @param string $referer Referer to be used.
     *
     * @return none
     */ 
    public function setReferer($referer) 
    {
        $this->setHeader('Referer', $referer);
    }

    /**
     * Adds cookie
     *
     * @param string $name  Name
     * @param string $value Value
     *
     * @return boolean Returns boolean.
     */
    public function addCookie($name, $value) 
    {
        $this->_cookies[$name] = $value;
        return true;
    }

    /**
     * Gets cookie
     *
     * @param string $name Name
     *
     * @return string Returns string OR boolean false
     */
    public function getCookie($name) 
    {
        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }
        return false;
    }

    /**
     * Deletes cookie
     *
     * @param string $name Name of cookie to delete
     *
     * @return boolean Returns boolean true
     *
     */ 
    public function delCookie($name) 
    {
        if (isset($this->_cookies[$name])) {
            unset($this->_cookies[$name]);
        }
        return true;
    }
    /* These two functions are nice to have if you need to serialize the cookies
        between request (for file or database storage as an example) */

    /**
     * Gets an array of cookies
     *
     * @return array Array with cookie.
     */
    public function getCookieArray() 
    { 
        return $this->_cookies; 
    }

    /**
     * Sets cookie array
     *
     * @param array $cookies An array with cookie values
     *
     * @return boolean Returns boolean true.
     */
    public function setCookieArray($cookies) 
    { 
        $this->_cookies = $cookies; 
        return true; 
    }

    /**
     * Builds headers
     *
     * @return string Returns the headers
     */
    private function _buildHeaders() 
    {
        $_header = Array();
        $_header[] = strtoupper($this->_method) . " " . $this->_uri . " HTTP/1.1";
        foreach ($this->_headers as $name => $value) {
            $_header[] = $name . ": " . $value;
        }
        return implode("\r\n", $_header);
    }

    /**
     * Builds cookies
     *
     * @return string A string with cookie values.
     */
    private function _buildCookies() 
    {
        $cookie = '';
        if (is_array($this->_cookies) && count($this->_cookies) > 0) {
            foreach ($this->_cookies as $name => $value) {
                $cookie .= $name . "=" . $value . ";";
            }
        }
        return $cookie;
    }
    /**
     * Parses cookie
     *
     * @param string $data Contains cookie data.
     *
     * @return boolean Returns boolean true.
     *
     */
    private function _parseCookie($data) 
    {
        $cookies = explode(';', $data);

        foreach ($cookies as $cookie) {
            if (strtolower(substr($cookie, 0, 5)) != ' path' 
                && strtolower(substr($cookie, 0, 8)) != ' expires'
            ) {
                list($name,$value) = explode('=', $cookie, 2);
                $this->addCookie($name, $value);
            }
        }

        return true;
    }
    /******* END HEADERS *******/

    /**
     * Connects to host
     *
     * @return boolean Returns boolean true.
     */
    private function _open() 
    {
        try {
            $this->_socket = new TCPBufferedClient($this->_hostname, $this->_port, $this->_ssl);
            $this->_socket->setCallback('get', $this);
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Adds stuff to the request.
     *
     * @param string $data Stuff that needs to be added.
     *
     * @return boolean Returns boolean true.
     */
    private function _put($data) 
    {
        $this->_socket->put($data);
        if (defined('HTTP_DEBUG') && HTTP_DEBUG) {
            $this->_debug['out'][] = $data;
        }

        return true;
    }

    /**
     * Sets method
     *
     * @param string $method Method to use for request
     *
     * @return none
     */
    public function setMethod($method) 
    {
        $this->_method = $method;
    }

    /**
     * Sets URI
     *
     * @param string $uri URI to set.
     *
     * @return none
     *
     */ 
    public function setUri($uri) 
    {
        $this->_uri = $uri;
    }

    /**
     * Sets POST data
     *
     * @param string $data POST data to be set.
     *
     * @return none
     */ 
    public function setPostData($data) 
    {
        if (is_array($data)) {
            // If we got an array, translate it into a string
            $postArray = Array();
            foreach ($data as $name => $value) {
                $postArray[] = urlencode($name) . "=" . urlencode($value);
            }
            $this->postData = implode('&', $postArray);
        } else {
            // If not an array, use the entire string as post data
            $this->postData = $data;
        }
    }

    /**
     * Makes request
     *
     * @return boolean Returns boolean true or false.
     */ 
    private function _makeRequest() 
    {
        if (empty($this->_hostname)) {
            throw new httpException(
                __CLASS__ . '::setHost(string hostname[, int portnumber]): empty'
            );
        }

        if (empty($this->_uri)) {
            throw new httpException(__CLASS__ . '::setUri(string uri): empty');
        }

        if (is_array($this->_cookies) && count($this->_cookies) > 0) {
            $this->setHeader('Cookie', $this->_buildCookies());
        }

        if (!is_resource($this->_socket)) {
            if (!$this->_open())
                return false;
        }

        switch($this->_method) {
        case "GET":
            /****** Construct headers ******/
            $headers = $this->_buildHeaders() . "\r\n";

            /****** Send headers ******/
            if ($this->_put($headers . "\r\n")) {
                return true;
            }
            return false;

            break;
        
        case "POST":
            if (empty($this->postData)) {
                throw new httpException(
                    __CLASS__ . "::setPostData(string data): empty"
                );
            }

            /****** Construct headers ******/
            $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            $this->setHeader('Content-Length', strlen($this->postData));
            $headers = $this->_buildHeaders() . "\r\n";

            /****** Send headers and postdata ******/
            if ($this->_put($headers . "\r\n" . $this->postData . "\r\n")) {
                return true;
            }
            return false;
            break;
        default:
            throw new httpException(
                __CLASS__ . 
                "::setMethod(string method): no handler for method {$this->_method}"
            );
            break;
        }
    } // function makeRequest

    /**
     * Receives data
     *
     * @param string $data Data that has been read from socket.
     *
     * @return none
     */ 
    public function get($socket, $data, $error) 
    {
        if ($error > 0) {
            $this->errorConnectionReset();
        }
        
        if (defined('HTTP_DEBUG') && HTTP_DEBUG) {
            $this->_debug['in'][] = $data;
        }

        $this->page->response .= $data;

        switch ($this->_phase) {

        case 0:
            // Remove trailing \r\n
            $data = $this->_fixHeader($data);
            $this->_phase++;
            // Set status
            list(,$this->page->status, $this->page->statusMsg) = explode(' ', $data, 3);
            break;
        
        case 1:
            // Remove trailing \r\n
            $data = $this->_fixHeader($data);
            if (empty($data)) { 
                $this->_phase++; break; 
            }

            // Get the parts
            list($name, $value) = explode(': ', $data, 2);
            // Set cookie or header
            if (strtolower($name) == 'set-cookie') {
                $this->_parseCookie($value);
            } else {
                $this->page->header[strtolower($name)] = $value;
            }

            break;
        
        default:
            //
            $this->page->data .= $data;
            break;
        } // switch
        
        if ($this->_maxLength > 0 && strlen($this->page->response) >= $this->_maxLength) {
            // Trigger end-of-page
            $this->errorConnectionReset();
        }
    }

    /**
     * This is called when connection is reset or request is finished.
     *
     * @return none
     */ 
    public function errorConnectionReset() 
    {

        if ($this->_phase != 2) {
            if (empty($this->page->status)) {
                $this->page->status = 0;
            }
        }
        $this->page->length = strlen($this->page->data);
        $this->page->stream = $this->_debug;
        $this->reset();

        call_user_func_array(
            $this->_callback, 
            Array(
                $this, 
                $this->page, 
                $this->_callbackvars
            )
        );
    }

    /**
     * Sets request variables and then calls _makeRequest()
     *
     * @param string $uri      URI for the http request
     * @param string $method   Method to use for http request
     * @param string $postData Postdata if there's any postdata
     *
     * @return none
     */ 
    public function request($uri='', $method='', $postData='') 
    {
        if (!empty($uri)) {
            $this->setUri($uri);
        }

        if (!empty($method)) {
            $this->setMethod($method);
        }

        if (!empty($postData)) {
            $this->setPostData($postData);
        }

        $this->_makeRequest();
    } // function httpRequest
    
    /**
     * Retrieves the actual page and instantiates HttpPage class
     *
     * @param mixed  $callback     Method to call once the request is done
     * @param array  $callbackvars Array with arguments that the callback function needs
     * @param string $uri          URI to be used for request.
     * @param string $method       Method to be used for request.
     * @param string $postData     POST data 
     *
     * @return boolean Returns boolean true.
     */
    public function getPage( 
        $callback, $callbackvars, 
        $uri='', $method='', $postData=''
    ) {
        if ($this->_phase > -1) {
            return false;
        
        }
        $this->request($uri, $method, $postData, false);
        $this->page = new HttpPage($this->_hostname, $this->_port, $this->_uri);
        $this->_phase = 0;
        $this->_callback = $callback;
        $this->_callbackvars = $callbackvars;

        return true;
    } // function httpGetPage
} // class httpSession

/**
 * HttpPage object
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2003 - 2012
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class HttpPage
{
    public $status;
    public $statusMsg;
    public $url;
    // All header names are lower case
    public $header = Array();
    public $data = '';
    public $response = '';
    public $length = 0;
    public $stream = Array();

    /**
     * The constructor
     *
     * @param string  $host Hostname of request.
     * @param integer $port Port that was used in request
     * @param string  $uri  URI that was used
     */
    public function __construct($host, $port, $uri) 
    {
        $this->url = 'http://' . $host . ($port==80?'':':'.$port) . $uri;
    }
}
