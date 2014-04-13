<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handles IRC stuff
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   IRC
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

/**
 * Called on start-up, creates all irc network objects
 *
 * @return void
 */
function irc_open_sockets()
{
    global $ircNetworks, $global, $netObj;
    foreach ($global['networks'] as $net) {
        if (!isset($ircNetworks[$net])) {
            $ircNetworks[$net] = new Irc($net);
            foreach ($netObj as $name => $param) {
                new $name($ircNetworks[$net], $param);
            }
        }
    }
} // function irc_open_sockets

/**
 * Handles all IRC related things 
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   IRC
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */
class Irc
{
    // {{{ properties
    /**
     * The network the bot connects to
     * @var string
     */
    protected $network;

    /**
     * The server the bot connects to
     * @var string
     */
    protected $server;

    /**
     * The port the bot connects to.
     * @var integer
     */
    protected $port;

    /**
     * PASS to use upon connection
     * @var string
     */
    protected $pass;

    /**
     * Holds the socket
     * @var resource
     */
    protected $socket;

    /** 
     * Nick to use by bot.
     * @var string
     */
    protected $nick;

    /**
     * The bots current nick
     * @var string
     */
    protected $mynick;

    /**
     * Channel
     * @var string
     */
    protected $chan;

    /** 
     * Anti-flood setting
     * @var boolean
     */
    protected $antiflood;

    /**
     * The buffer used by the bot
     * @var array
     */
    protected $buffer        = Array();

    /**
     * Keeps all the attached methods
     * @var array
     */
    protected $attached      = Array();

    /**
     * Keeps users
     * @var object
     */
    protected $users;

    /**
     * Variable to have the bot know if it
     * is in startup mode
     * @var integer
     */
    protected $start;

    /**
     * Has the bot performed?
     * @var boolean
     */
    protected $performed     = false;
    /**
     * Wheither or not to use rawHandler
     * @var boolean
     */
    public    $botrawHandler = false;

    // }}}

    /**
     * Constructor, gets configuration from network and calls connect())
     * 
     * @param string $network Name of the IRC network
     * @param string $server  Deprecated, don't use this
     * @param string $port    Deprecated, don't use this
     */
    public function __construct ($network, $server = '', $port = '')
    {
        $this->network = $network;
        $this->users = new Users($this);
        $this->start = time();
        global $networkList;

        if (empty($server)) {
            $this->server   = $networkList[$network]['servers'];
            $this->nick     = $networkList[$network]['nickname'];
            $this->altnick  = $networkList[$network]['altnick'];
            $this->ident    = $networkList[$network]['username'];
            $this->realname = $networkList[$network]['realname'];
            $this->perform  = $networkList[$network]['perform'];
            $this->pass     = $networkList[$network]['pass'];
            $this->vhost    = $networkList[$network]['vhost'];
            $this->ssl      = $networkList[$network]['ssl'];

            if (strstr($this->server, ",")) {
                $this->servers = explode(",", $this->server);
            } else {
                $this->servers = Array($this->server);
            }

            $tmpArray = Array();

            foreach ($this->servers as $key => $value) {
                if (strstr($value, ':')) {
                    list ($tmpServer, $tmpPort) = explode(":", $value);
                } else {
                    $tmpServer = $value;
                    $tmpPort   = CONFIG_NETWORK_PORT;
                }
                $tmpArray[] = Array(
                    'server' => $tmpServer,
                    'port'   => $tmpPort
                );
            }

            $this->servers = $tmpArray;
            unset($this->server);
        } else {
            if (empty($port)) {
                $port = CONFIG_NETWORK_PORT;
            }
            $this->realname = CONFIG_DEFAULT_NAME;
            $this->nick     = CONFIG_DEFAULT_NICK;
            $this->ident    = CONFIG_DEFAULT_IDENT;
            $this->server   = $server;
            $this->port     = $port;
            if (!empty($pass)) {
                $this->pass = $pass;
            }
            
            if (!empty($vhost)) {
                $this->vhost = $vhost;
            }
        } //else

        $this->attach(
            'perform', 
            Array(
                $this,'perform'
            ), 
            Array(
                (SERVICE?'PASS':'375')
            )
        );
        $this->attach(
            'ping', 
            Array(
                $this,'ping'
            ), 
            Array(
                'PING'
            )
        );

        $this->connect();
    } // function irc
    
    /**
     * Returns the bot nickname
     * 
     * @return string Nickname of the bot
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * Sets up a connection to the irc server using TCPBufferedClient
     * 
     * @return void
     */
    public function connect()
    {
        global $openStreams;

        $this->performed = false;

        if (empty($this->server) || $this->server['id'] >= count($this->server)) {
            $this->server['id'] = 0;
            $sid = 0;
        } else {
            $sid = $this->server['id'];
            if ($this->server['id'] == count($this->servers)) {
                $sid = 0;
            }
        }

        $this->server = Array(
            'id'     => $sid,
            'server' => $this->servers[$sid]['server'],
            'port'   => $this->servers[$sid]['port']
        );

        $server = $this->server['server'];
        $port   = $this->server['port'];

        logWrite(
            L_SYSTEM, 
            sprintf(
                '[IRC] Connecting to %s (%s:%d)', 
                $this->network, $server, $port
            )
        );

        try {
            $this->socket = new TCPBufferedClient(
                $this->server['server'], $this->server['port'], $this->ssl, $this->vhost
            );

            $this->socket->setCallback('get', $this);
            list ($this->myIp, $this->myPort) = $this->socket->getIpPort();
            $this->connected = 1;
            $this->conTime   = time();
            if (!empty($this->pass)) {
                $this->raw(sprintf("PASS :%s", $this->pass));
            }
            
            $this->mynick = $this->nick;

            if (SERVICE) {
                $this->raw(sprintf("SERVER %s 1 :%s", $this->nick, $this->realname));
            } else {
                $this->raw(sprintf("NICK %s", $this->nick));
                $this->raw(
                    sprintf(
                        "USER %s %s %s :%s", 
                        $this->ident, 
                        $this->myIp, 
                        $this->server['server'], 
                        $this->realname
                    )
                );
            }
            unset($this->lastPing);
            $this->trigger("X_CONNECT", Array('server' => $server, 'port' => $port));
            Timer::add2('ping' . $this->network, 120, Array($this, 'pingServer'));
            logWrite(L_SYSTEM, sprintf('[IRC] Connected to %s', $this->network));
            
        } catch(Exception $e) {
            if (empty($this->reconnect)) {
                $this->reconnect=1;
            } else {
                ++$this->reconnect;
            }
            $this->socket->close();
            $this->server['id']++;
            $this->connected = 0;
            $this->conTime = time();

            Timer::add2('recon'. $this->network, 5, Array($this,'connect'));
            logWrite(
                L_SYSTEM, 
                sprintf(
                    '[IRC] Unable to connect, retrying with next server in list'
                )
            );
            return false;
        }
        
        return true;
    } // function connect


    /**
     * Called when the bot has connected to the network 
     * to perform initial tasks and commands
     * 
     * @return void
     */
    protected function perform()
    {
        if ($this->performed) {
            return false;
        }

        $this->performed = true;

        if (!empty($this->perform)) {
print("PERFORM OK");
            $perform = explode(',', $this->perform);
            foreach ($perform as $raw) {
                $this->raw(
                    str_replace(
                        Array(
                            '%me%',
                            '%time%'
                        ),
                        Array(
                            $this->mynick, 
                            time()
                        ),
                        trim($raw)
                    )
                );
            }
        }
        if (!SERVICE) {
            global $networkList;
            $autojoin = $networkList[$this->network]['autojoin'];
            if (!empty($autojoin)) {
                $this->raw("JOIN $autojoin");
            }
        }
    }

    /**
     * Called when a PING-command is received from the server
     * 
     * @param Irc    $parent Object of irc network that made the request
     * @param string $data   Complete string of the command
     * @param Array  $extra  Array of helper variables, see parseCommand()
     *
     * @return none
     */
    protected function ping($parent, $data, $extra) 
    {
        extract($extra);

        if ($func != 'PING') {
            return false;
        }

        if (SERVICE) {
            if ($target == '') {
                $this->raw('PONG :'.$msg);
            } elseif ($msg == '') {
                $this->raw('PONG '.$target);
            } else {
                $this->raw(":{$this->me()->nick} PONG {$target} :{$msg}");
            }
        } else {
            $this->raw("PONG :{$target}");
        }
        return true;
    }

    /**
     * Called via callback from TCPBufferedClient 
     * when new data is fetched from the server
     * 
     * @param SocketHandler $socket Reference to the SocketHandler 
     *                              handling the connection
     * @param string        $data   The received data
     * @param integer       $error  TCP Error code, 0 on no error
     *
     * @return void
     */
    public function get ($socket, $data, $error)
    {
        if ($error > 0) {
            $this->errorConnectionReset();
        }
        
        
        $data = str_replace(Array("\r", "\n"), Array("",""), $data);
        if (!empty($data)) {
            $e = explode(' ', $data);

            if ($e[1] == '433') {
                // Nick in use
                if (empty($this->altnick) || $this->mynick == $this->altnick) {
                    $this->mynick = $this->nick . '_';
                } else {
                    $this->mynick = $this->altnick;
                }
                $this->raw('NICK '.$this->mynick);
            }
            logWrite(L_IRCALL, $data);
            $this->gotRaw($data);
        }
    } // function get

    /**
     * Called when a connection is closed, cleans up and tries to reconnect
     * 
     * @return void
     */
    public function errorConnectionReset()
    {
        if (!empty($this->network)) {
            logWrite(
                L_SYSTEM, 
                sprintf(
                    '[IRC] Got disconnected from %s, trying to reconnect in 30 secs',
                    $this->network
                )
            );
            $this->conTimerId = Timer::add2(
                'recon'. $this->network, 
                30, 
                Array(
                    $this, 'connect'
                ), 
                Array(
                    $this->network
                )
            );
        }
    }


    /**
     * Helper method to join a channel
     * 
     * @param string $channel Name of the channel
     * @param string $key     Key for password-protected channels
     * @param string $nick    Which nick is joining (only used in service-mode)
     *
     * @return void
     */
    public function joinchan ($channel, $key='', $nick='')
    {
        if (SERVICE) {
            if (!empty($nick) && isChan($channel)) {
                $this->raw(":$nick JOIN $channel");
                $this->gotRaw(":$nick JOIN $channel");
            }
        } else {
            if (isChan($channel)) {
                $this->raw(
                    sprintf(
                        "JOIN %s%s", 
                        $channel, 
                        (empty($key)?'':" :".$key)
                    )
                );
            }
        }
    } // function joinchan

    /**
     * Helper method to part a channel
     * 
     * @param string $channel Name of the channel
     * @param string $nick    Which nick is parting (only used in service-mode)
     *
     * @return void
     */
    public function partchan ($channel, $nick='')
    {
        if (SERVICE) {
            if (!empty($nick) 
                && isChan($channel) 
                && $this->user($nick)->ison($channel)
            ) {
                $this->raw(":$nick JOIN $channel");
                $this->gotRaw(":$nick PART $channel");
            }
        } else {
            if (isChan($channel)) {
                if (isset($this->chan[$channel])) {
                    $this->raw("PART {$channel}");
                }
            }
        }
    } // function partchan

    /**
     * TBH, I don't know what this does or why it's even here :E
     * 
     * @param mixed   $data     Data
     * @param integer $silent   it's quiet
     * @param integer $priority Low
     *
     * @return void
     */
    public function botraw($data, $silent = 0, $priority = 3)
    {
        if (SERVICE && is_array($this->botrawHandler)) {
            call_user_func($this->botrawHandler, $data, $silent, $priority);
        } else {
            $this->raw($data, $silent, $priority);
        }
    }

    /**
     * Sends a raw command to the irc server
     * 
     * @param string  $data     String to send
     * @param integer $silent   Not implemented yet
     * @param integer $priority Not implemented yet
     *
     * @return void
     */
    public function raw($data, $silent = 0, $priority = 3)
    {
        if (SERVICE) {
            $extra = $this->parseCommand($data);

            if (in_array(
                $extra['func'], 
                Array(
                    'JOIN',
                    'PART', 
                    'QUIT', 
                    'NICK', 
                    'MODE', 
                    'SERVER'
                )
            )
            ) {
                $str = '';
                foreach ($extra as $k=>$v) {
                    if (is_array($v)) {
                        $str.="[$k:{ ";
                        foreach ($v as $kk=>$vv) {
                            $str.= "[$kk:$vv] ";
                        }
                        $str.="}] ";
                    } else {
                        if (!empty($v)) {
                            $str.="[$k:$v] ";
                        }
                    }
                }

                logWrite(L_DEBUG, '[DEBUG:REPLAY] '.$str);
                
                $this->users->ircraw($this, $data, $extra);
            }
        }
        $this->socket->put($data."\r\n");
        logWrite(L_DEBUG, '>> ' . $data);
        return true;
    } // function raw

    /**
     * Sends a PRIVMSG type message to a target (nick or channel)
     * 
     * @param string  $target The target (channel/nick)
     * @param string  $data   The message
     * @param boolean $silent True if message shouldn't be logged
     * @param string  $nick   Nick that sent the message (service-mode only)
     *
     * @return void
     */
    public function privmsg($target, $data, $silent = false, $nick = '')
    {
        if (CONFIG_CHARSET_CONVERT) {
            $data = iconv(CONFIG_CHARSET_IN, CONFIG_CHARSET_OUT.'//IGNORE', $data);
        }

        $data = str_replace(Array("\r","\n"), Array(" ", " "), $data);

        if (SERVICE) {
            if (empty($nick)) { 
                throw new Exception('No source specified'); 
            }
            $this->raw(sprintf(':%s PRIVMSG %s :%s', $nick, $target, $data), 1);
        } else {
            $this->raw(sprintf('PRIVMSG %s :%s', $target, $data), 1);
        }

        if ($silent == false) {
            logWrite(
                L_PRIVMSG, 
                sprintf(
                    '<%s> %s', 
                    (SERVICE?$nick:$this->nick), 
                    $data
                ), 
                $target
            );
        }
    } // function privMsg
    
    /**
     * Sends a NOTICE type message to a target (nick or channel)
     * 
     * @param string  $target The target (channel/nick)
     * @param string  $data   The message
     * @param boolean $silent True if message shouldn't be logged
     * @param string  $nick   Nick that sent the message (service-mode only)
     *
     * @return void
     */
    public function notice($target, $data, $silent = 0, $nick = '')
    {
        if (CONFIG_CHARSET_CONVERT) {
            $data = iconv(CONFIG_CHARSET_IN, CONFIG_CHARSET_OUT.'//IGNORE', $data);
        }
        
        if (SERVICE) {
            if (empty($nick)) { 
                throw new Exception('No source specified'); 
            }
            $this->raw(sprintf(':%s NOTICE %s :%s', $nick, $target, $data), 1);
        } else {
            $this->raw(sprintf('NOTICE %s :%s', $target, $data), 1);
        }
        if ($silent == 0) {
            logWrite(
                L_PRIVMSG, 
                sprintf(
                    '-%s- %s', 
                    (SERVICE?$nick:$this->nick), 
                    $data
                ), 
                $target
            );
        }
    } // function privmsg

    /**
     * Called when a PONG is received from the server, telling us it's still alive
     * 
     * @param Irc    $parent Object of irc network that made the call
     * @param string $data   Entire command
     * @param Array  $extra  Array of helper variables, see parseCommand()
     *
     * @return void
     */
    protected function pongServer($parent, $data, $extra) 
    {
        extract($extra);
        $this->lastPing = time();
        if (is_numeric($msg)) {
            $this->serverLag = ($msg - utime());
            $this->detach('pongServer');
        }
    }

    /**
     * Pings the server to check if it's still alive
     * 
     * @return void
     */
    public function pingServer()
    {
        if (empty($this->lastPing)) {
            $this->lastPing = time();
        }

        if ($this->lastPing < time()-130) {
            unset($this->lastPing);
            $this->errorConnectionReset();
            return;
        }
        $this->pingTime = utime();
        $this->raw("PING :".$this->pingTime);
        $this->attach('pongServer', Array($this, 'pongServer'), Array('PONG'));
        Timer::add2('ping'.$this->network, 120, Array($this, 'pingServer'));
        return;
    } // function pingServer

    /**
     * Sends a QUIT command to the irc server and closes the socket
     * 
     * @param string $quitmsg Quit message
     *
     * @return booolean True on success
     */
    public function quit($quitmsg)
    {
        global $ircNetworks;
        $this->raw(sprintf('QUIT :%s', $quitmsg));
        logWrite(
            L_SYSTEM, 
            sprintf(
                '[IRC] Disconnecting from %s (%s:%s)', 
                $this->network, 
                $this->server['server'], 
                $this->server['port']
            )
        );
        $this->socket->close();
        if (isset($ircNetworks[$this->network])) {
            unset($ircNetworks[$this->network]);
        }
        return true;
    } // function quit

    /**
     * Parses the received command and creates an array of helper variables
     * available to every attach():ed methods
     * Helper variables are:
     * src      - The source of the message (server, nick or nick!ident@host)
     * func     - The function or numeric
     * nick     - Nickname of the source
     * ident    - Ident of the source
     * host     - Host of the source
     * msg      - The message (converted to local encoding)
     * action   - Boolean, true if /me was used
     * target   - The target of the message (usually channel name or our nickname)
     * replyto  - What we should reply to (usually channel name or source nickname)
     * private  - Boolean, true if the message was only seen by us
     * 
     * @param string $data The raw data received from the server
     *
     * @return Array Array of named helper variables 
     */
    public function parseCommand($data) 
    {
        $extra = Array(
            'src'     => '',
            'func'    => '',
            'nick'    => '',
            'ident'   => '',
            'host'    => '',
            'msg'     => '',
            //'msg_orig' => '',
            'action'  => false,
            'target'  => '',
            'replyto' => '',
            'private' => false
        );
        if (substr($data, 0, 1) == ':') {
            list($src, $func) = explode(' ', $data, 2);
            $data2 = '';
            if (false !== strpos($func, ' ')) {
                list($func, $data2) = explode(' ', $func, 2);
            }
            $extra['src'] = ( substr($src, 0, 1)==':' ? substr($src, 1) : $src );
        } else {
            $src = '';
            list($func, $data2) = explode(' ', $data, 2);
        }
        
        $extra['func'] = $func;

        if (preg_match(
            '/^:(?P<nick>[^!\s]*)!(?P<ident>[^@\s]*)@(?P<host>.*)$/', 
            $src, 
            $out
        ) > 0
        ) {
            $extra['nick'] = $out['nick'];
            $extra['ident'] = $out['ident'];
            $extra['host'] = $out['host'];
        } elseif (
            SERVICE 
            && preg_match(
                '/^:(?P<nick>[^\.!\s]*)$/', 
                $src, 
                $out
            ) > 0
        ) {
            $extra['nick'] = $out['nick'];
        }

        if (false !== strpos($data2, ' :')) {
            list($data2, $extra['msg']) = explode(' :', $data2, 2);
        }

        if (false !== strpos(trim($data2), ' ')) {
            $extra['args'] = explode(' ', trim($data2));
        } else {
            $extra['args'][0] = trim($data2);
        }
        
        if (isset($extra['args'][0])) {
            $extra['target'] = array_shift($extra['args']);
            $extra['replyto'] = $extra['target'] = ( 
                substr(
                    $extra['target'], 0, 1
                )==':' ? 
                substr(
                    $extra['target'], 1
                ) : $extra['target'] 
                );
        }

        if (preg_match('/^[^#&?0-9]/', $extra['target']) == 1) {
            $extra['replyto'] = $extra['nick'];
            $extra['private'] = true;
        }

        if ($func == 'PRIVMSG') {
            if (preg_match(
                "/^".chr(1)."ACTION (.*)".chr(1)."$/", 
                $extra['msg'], 
                $out
            ) == 1
            ) {
                $extra['msg'] = $out[1];
                $extra['action'] = true;
            }
        }

        //$extra['msg_orig'] = $extra['msg'];
        $extra['msg'] = $this->charsetDecode($extra['msg']);
        return $extra;
    }

    /**
     * Called whenever raw data is received from the server. 
     * Calls parseCommand() and all attach():ed methods 
     * 
     * @param string $data The data that was received
     *
     * @return void
     */
    public function gotRaw($data) 
    {

        $extra = $this->parseCommand($data);
        extract($extra);

        foreach ($this->attached as $id => $val) {
            list($filter, $method, $regexp) = $val;
            if (empty($filter) || in_array($func, $filter)) {
                if (!empty($regexp)) {
                    if (preg_match($regexp, $msg, $extra['regexp']) == 0) {
                        continue;
                    }
                }
                try {
                    call_user_func_array($method, Array($this, $data, $extra));
                }
                catch (Exception $e) {
                    logWrite(L_ERROR, "Uncaught exception from module: ".$e->getMessage());
                }
            }
        }
    }


    /**
     * Trigger a non-server related event
     * 
     * @param string $event Name of the event
     * @param array  $extra Array of helper variables, if any
     *
     * @return void
     */
    public function trigger($event, $extra) 
    {
        foreach ($this->attached as $id=>$val) {
            list($filter, $method, $regexp) = $val;
            if (empty($filter) || !is_array($filter) || in_array($event, $filter)) {
                call_user_func_array($method, Array($this, $event, $extra));
            }
        }
    }

    /**
     * Returns the IRCUser object that corresponds to the bot
     * 
     * @return IRCUser User object
     */
    public function me() 
    {
        return $this->users->me;
    }

    /**
     * Returns a IRCUser object for a certain nickname
     * 
     * @param string  $nick   Nickname
     * @param boolean $create True if the user object should be 
     *                        created if it doesn't already exist
     *
     * @return IRCUser User object
     */
    public function user($nick, $create=false) 
    {
        return $this->users->user($nick, $create);
    }
    /**
     * Returns an array of all known users
     * 
     * @return Array Array of known users
     */
    public function users() 
    {
        return $this->users->users();
    }
    /**
     * Returns the IRCChannel object for a specified channel
     * 
     * @param string  $chan   Channel name
     * @param boolean $create True if the channel object should be 
     *                        created if it doesn't already exist
     *
     * @return IRCChannel     Channel object
     */
    public function chan($chan, $create=false) 
    {
        return $this->users->chan($chan, $create);
    }
    /**
     * Returns an array of all known channels
     * 
     * @return Array Array of known channels
     */
    public function chans() 
    {
        return $this->users->chans();
    }
    /**
     * Returns the IRCServer object for a specified server
     * 
     * @param string  $name   Name of the server
     * @param boolean $create True if the server object should be 
     *                        created if it doesn't already exist
     *
     * @return IRCServer      Server object
     */
    public function server($name, $create=false) 
    {
        return $this->users->server($name, $create);
    }
    /**
     * Returns an array of known irc servers
     * 
     * @return Array Array of known irc servers
     */
    public function servers() 
    {
        return $this->users->servers();
    }
    /**
     * Returns the IRCMode object for a specified mode 
     * 
     * @param char $mode Letter corresponding to mode
     *
     * @return IRCMode Mode object
     */
    public function mode($mode) 
    {
        return $this->users->mode($mode);
    }
    /**
     * Returns an array of all available irc modes
     * 
     * @return Array Array of available irc modes
     */
    public function modes() 
    {
        return $this->users->modes();
    }

    /**
     * Converts from UTF-8 or ISO-8859-1 to the local charset
     * 
     * @param string $data Data to convert
     *
     * @return string String converted to local charset
     */
    public function charsetDecode($data) 
    {
        if (CONFIG_CHARSET_CONVERT 
            && function_exists('iconv') 
            && function_exists('mb_detect_encoding')
        ) {
                return iconv(
                    mb_detect_encoding(
                        $data, 
                        "UTF-8, ISO-8859-1", 
                        true
                    ), 
                    CONFIG_CHARSET_IN.'//IGNORE', 
                    $data
                );
        }
        return $data;
    }

    /**
     * Attaches a method to certain triggers or commands from the server
     * Useful in modules to get a callback when certain things happen
     * attach(id, 'newPerson', Array('JOIN')) would call the function
     * newPerson() whenever a JOIN is received from the server.
     * 
     * @param string $id     Unique name
     * @param mixed  $method Callback method, string or array(object, method)
     * @param array  $filter Array of commands or triggers
     * @param string $regexp Regexp to validate against message on server commands
     *
     * @return void
     */
    public function attach($id, $method, $filter = '', $regexp = '') 
    {
        $this->attached[$id] = Array($filter, $method, $regexp);
    }

    /**
     * Detaches a method callback
     * 
     * @param string $id The unique name that was used for attach())
     *
     * @return void
     */
    public function detach($id) 
    {
        unset($this->attached[$id]);
    }

    public function getNetworkName() {
        return $this->network;
    }
}
