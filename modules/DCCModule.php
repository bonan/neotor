<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handles DCC actions for neotor.
 *
 * PHP version 5
 *
 * @category  IrcDCC
 * @package   DCCModule
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles DCC file transfer receive.
 *
 * PHP version 5
 *
 * @category  IrcDCC
 * @package   DCCModule
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @author    Bonan Bonan <bonan@neotor.se>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class DCCGet
{

    // {{{ properties

    /**
     * Holds unique id of this instance
     * @var string
     */
    protected $id;

    /**
     * Callback to use
     * @var array
     */
    protected $CallBack;

    /** 
     * Socket resource
     * @var resource
     */
    protected $socket;

    /**
     * Holds dccdata
     * @var array
     */
    var       $DCCData;
    
    // }}}

    /**
     * The constructor
     *
     * @param array $DCCData  DCCData containing filename,
     *                        filesize, port and ip.
     * @param array $CallBack Array containing object
     *                        callback to be called
     *                        when the whole file has
     *                        been received.
     */
    public function __construct($DCCData, $CallBack)
    {
        global $openStreams, $dcc, $DCCfiles;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->id = uniqid();
        $dcc[$this->id] = $this;
        $this->CallBack = $CallBack;
        $this->DCCData = $DCCData;
        socket_set_nonblock($this->socket); 
        @socket_connect($this->socket, $this->DCCData['IP'], $this->DCCData['Port']);
        $openStreams[$this->id] = Array('type' => 'dcc', 'socket' => $this->socket);

        if (!array_key_exists($this->id, $DCCfiles)) {
            $file = fopen($this->DCCData['FileName'], 'w');
            $DCCfiles[$this->id] = $this->DCCData;
            $DCCfiles[$this->id]['received'] = 0;
            $DCCfiles[$this->id]['filefd']   = $file;
            $DCCfiles[$this->id]['starttime'] = 0;
        }
    }

    /**
     * Removes socket if connection is reset
     *
     * @return none
     */
    public function errorConnectionReset()
    {
        global $openStreams, $dcc, $DCCfiles;

        if (isset($openStreams[$this->id])) {
            unset($openStreams[$this->id]);
        }
        if (isset($dcc[$this->id])) {
            unset($dcc[$this->id]);
        }

        $timedelta = microtime(true) - $DCCfiles[$this->id]["starttime"];
        $DCCfiles[$this->id]['Speed'] 
            = $DCCfiles[$this->id]['FileSize'] / $timedelta;

        fclose($DCCfiles[$this->id]['filefd']);
        call_user_func_array($this->CallBack, Array('id' => $this->id));


        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
        $this->socket = '';
    }

    /**
     * This calls errorConnectionReset
     *
     * @return none
     */
    public function reset()
    {
        $this->errorConnectionReset();
    }

    /**
     * This is called when there is data to be read.
     *
     * @param binary $data Binary data that has been received
     *
     * @return none
     *
     */
    public function get($data)
    {
        global $DCCfiles;
        if ($DCCfiles[$this->id]["received"] < $DCCfiles[$this->id]["FileSize"]) {
            if ($DCCfiles[$this->id]["received"] == 0) {
                $DCCfiles[$this->id]["starttime"] = microtime(true);
            }

            fwrite($DCCfiles[$this->id]["filefd"], $data);
            $DCCfiles[$this->id]["received"] += strlen($data);


            if ($DCCfiles[$this->id]["FileSize"] == $DCCfiles[$this->id]["received"]
            ) {
                // We have the whole file
                $this->errorConnectionReset();
            }
        }
    }
}

/**
 * Handles DCC file transfer receive.
 *
 * PHP version 5
 *
 * @category  IrcDCC
 * @package   DCCModule
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @author    Bonan Bonan <bonan@neotor.se>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */
class DCCModule implements module
{
    protected $id;
    protected $parent;
    protected $socket;

    /**
     * The constructor
     *
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */
    public function __construct($parent, $params)
    {
        $this->id = uniqid();
        $this->parent = $parent;

        $parent->attach(
            'DCCController_'.$this->id,
            Array($this,'dccNotification'),
            Array(
                'PRIVMSG'
            ),
            '/^\x01DCC\sSEND\s'.
            '(?P<filename>[\w\.]+)\s'.
            '(?P<ip>[\d]+)\s'.
            '(?P<port>[\d]+)\s'.
            '(?P<filesize>[\d]+)/i'
        );
    }

    /**
     * This method is called whenever someone is trying to send
     * a file to us.
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function dccNotification($parent, $data, $extra)
    {
        $this->receiveFile($parent, $data, $extra);
    }

    /**
     * Creates a new DCCGet object that receives the file.
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function receiveFile($parent, $data, $extra)
    {
        extract($extra);

        $DCCData = Array(
            'Sender'   => $nick,
            'FileName' => $regexp['filename'],
            'FileSize' => $regexp['filesize'],
            'IP'       => long2ip($regexp['ip']),
            'Port'     => $regexp['port']
        );
        $dccget = new DCCGet($DCCData, Array($this, 'finishedReceiving'));
    }

    /**
     * Helper function to convert from bytes to KiB, MiB and so on.
     *
     * @param integer $val The value to be converted.
     *
     * @return string Returns a formatted string.
     */
    public function toHighestUnit($val)
    {
        $Units = array(
            "B",
            "KiB",
            "MiB",
            "GiB",
            "TiB"
        )
        ;
        $numit = 0;
        while ($val >1023) {
            $val = $val / 1024;
            ++$numit;
        }
        $ret = sprintf("%f %s/s", $val, $Units[$numit]);
        return $ret;
    }

    /**
     * Called by DCCGet object once the file transfer is finished
     *
     * @param string $FileID The id of the file that has been received.
     *
     * @return none
     */
    public function finishedReceiving($FileID) 
    {
        global $DCCfiles;
        $this->parent->privmsg(
            "#neotor", 
            sprintf(
                "Finished receiving %s from %s | %s",
                $DCCfiles[$FileID]['FileName'],
                $DCCfiles[$FileID]['Sender'],
                $this->toHighestUnit($DCCfiles[$FileID]['Speed'])
            )
        );
        unset($DCCfiles[$FileID]);
    }
}
$DCCfiles = Array();
