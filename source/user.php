<?php 
/**
 * This takes care of IRC related tasks.
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

/**
 * This handles the avalible channel modes.
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


class IRCMode
{
    // {{{  properties

    /**
     * The mode
     *
     * @var string
     */
    protected $mode;

    /**
     * Prefixed used for certain mode
     *
     * @var string
     */
    protected $prefix;

    /**
     * Which other modes are included in this mode
     * (i.e. +o includes +v)
     * 
     * @var string
     */
    protected $includes;

    /**
     * Type of mode
     * A = Mode that adds or removes a nick or address to a list. Always has a parameter. 
     * B = Mode that changes a setting and always has a parameter. 
     * C = Mode that changes a setting and only has a parameter when set. 
     * D = Mode that changes a setting and never has a parameter.
     * U = Mode that has a prefix and is set on a user.
     *
     * @var string
     */
    protected $type;

    // }}}

    /** 
     * Gets value of property.
     *
     * @param string $var Name of propery.
     *
     * @return string
     */
    public function __get($var) 
    {
        return $this->$var;
    }

    /**
     * Sets property to specified value
     *
     * @param string $var   Name of property
     * @param string $value The value of property.
     *
     * @return void
     */
    public function __set($var,$value) 
    {
        $this->$var = $value;
    }


    /** 
     * The constructor
     *
     * @param string $mode     Mode
     * @param string $prefix   Prefix
     * @param string $includes Includes
     * @param string $type     Type
     *
     * @return void
     */
    public function __construct($mode,$prefix,$includes,$type) 
    {
        $this->mode     = $mode;
        $this->prefix   = $prefix;
        $this->includes = $includes;
        $this->type     = $type;
    }
}


/**
 * An object for connected IRCServers.
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


class IRCServer
{
    // {{{  properties

    /**
     * Name of server
     *
     * @var string
     */
    protected $name;

    /**
     * Description of server
     *
     * @var string
     */
    protected $desc;

    /**
     * Version of server
     *
     * @var string
     */
    protected $ver;

    /**
     * Holds the calling object.
     *
     * @var string
     */
    protected $parent;

    // }}}

    /**
     * The constructor
     * 
     * @param object $parent The calling object
     * @param strin  $name   Name of server.
     *
     * @return void
     */

    public function __construct($parent, $name) 
    {
        $this->parent = $parent;
        $thsi->name = $name;
    }

    /**
     * Sets description
     *
     * @param string $desc description
     *
     * @return object Returns object.
     */
    public function setDesc($desc) 
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Sets version of server.
     *
     * @param string $ver Version
     *
     * @return object Returns object
     */
    public function setVer($ver) 
    {
        $this->ver = $ver;
        return $this;
    }

    /**
     * Gets choosen property.
     *
     * @param string $var name of property
     *
     * @return mixed
     */
    public function __get($var) 
    {
        if (isset($this->$var) && $var != 'parent') {
            return $this->$var;
        }
        return false;
    }
}

/**
 * Class that keeps track of known irc users and channels
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
class Users
{

    // {{{  properties

    /**
     * Contains unique id of this instance
     *
     * @var string
     */
    protected $id;

    /**
     * Contains known channels
     *
     * @var array
     */
    protected $users    = Array();

    /**
     * Contains known channels
     * @var array
     */
    protected $channels = Array();

    /**
     * Contains known servers
     *
     * @var array
     */
    protected $servers  = Array();

    /**
     * Contains the calling object
     *
     * @var object
     */
    protected $parent;

    /**
     * Is the bots own IRCUser object (used when doing Irc->me()->...)
     *
     * @var string
     */
    protected $me;

    /**
     * List of prefixes
     *
     * @var array
     */
    protected $prefix   = Array();

    /**
     * List of modes
     * 
     * @var array
     */
    protected $mode     = Array();

    // }}}

    /**
     * Handles joins
     * 
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleJoin($extra) 
    {
        extract($extra);

        if (strpos($target, ',') !== false) {
            $targets = explode(',', $target);
        } else {
            $targets = Array($target);
        }

        foreach ($targets as $target) {
            if ($this->user($nick) == $this->me && !SERVICE) {
                //$this->parent->raw('WHO '.$target);
                //$this->parent->raw('MODE '.$target);
                foreach ($this->mode as $o) {
                    // Get BAN list
                    if ($o->type == 'A' && $o->mode == 'b') {
                        //$this->parent->raw('MODE '.$target.' +'.$o->mode);
                    }
                }
            }
            if (!empty($nick)) {
                $this->user($nick)->check($ident, $host);
                $this->chan($target)->add($this->user($nick)); 
            }
        }
    }

    /**
     * Handles introduction of new servers (service only)
     * 
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleServer($extra) 
    {
        extract($extra);
        $ver = '';
        if (preg_match('/^U\d\d\d\d/', $msg) == 1) {
            list ($ver, $msg) = explode(' ', $msg, 2);
        }
        $this->server($target)->setDesc($msg)->setVer($ver);
    }

    /**
     * Handles parts
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handlePart($extra) 
    {
        extract($extra);
        $this->chan($target)->remove($this->user($nick));
    }

    /**
     * Handles user quits.
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleQuit($extra) 
    {
        extract($extra);
        $this->user($nick)->quit();
    }

    /**
     * Handles user getting killed
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleKill($extra) 
    {
        extract($extra);
        $this->user($target)->quit();
    }

    /**
     * Handles user getting SVSKilled
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleSvskill($extra)
    {
        extract($extra);
        $this->user($target)->quit();
    }

    /**
     * Handles user changing nick
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleNick($extra) 
    {
        extract($extra);
        if ($nick == '' && SERVICE) {
            $this->user($target)
                ->check($args[2], $args[3], $msg)
                ->setServer($args[4])
                ->setSignon($args[1]);
        } elseif ($nick == '') {
            return;
        } else {
            $this->user($nick)->nick($target);
            $this->users[strtolower($target)] = $this->user($nick);
            $this->removeUser($nick);
        }
    }

    /**
     * Handles kicks
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleKick($extra) 
    {
        extract($extra);
        $knick = $args[0];
        // $msg == reason
        $this->chan($target)->remove($this->user($knick));
    }

    /**
     * Handles topic changes
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleTopic($extra) 
    {
        extract($extra);
        $this->chan($target)->topic($msg)->topicBy($nick);
    }

    /**
     * Handles /topic reply - topic string (received on join)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle332($extra) 
    {
        extract($extra);
        $this->chan($args[0])->topic($msg);
    }

    /**
     * Handles /topic reply - set by & date (received on join)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle333($extra) 
    {
        extract($extra);
        $this->chan($args[0])->topicBy($args[1], $args[2]);
    }

    /**
     * Handles protocol information (when connecting as a service)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleProtoctl($extra) 
    {
        if (!SERVICE) {
            return;
        }

        if (empty($this->me)) {
            $n = $this->parent->getNick();
            $this->me = $this->user($n)
                ->check(
                    substr($n, 0, strpos($n, '.')), 
                    substr($n, strpos($n, '.')+1), 
                    $this->server($n)->desc
                )
                ->setServer($n)
                ->setSignon(time());
        }
        $extra['args'][] = $extra['target'];
        $extra['args'][] = "PREFIX=" . '(qaohv)~&@%+';
        $extra['target'] = $this->me->nick;
        $this->handle005($extra);
    }

    /**
     * Handles connect info and parses available channel modes 
     *
     * @param array $extra Array of stuff
     *
     * @return boolean Returns boolean true upon completion
     */

    protected function handle005($extra) 
    {
        extract($extra);
        if (!SERVICE && empty($this->me) && !empty($target)) {
            $this->me = $this->user($target);
        }


        foreach ($args as $k=>$v) {
            if (false!==strpos($v, '=')) {
                list($var,$value) = explode('=', $v, 2); 
            } else {
                $var = $v;
            }


            switch($var) {
            case 'PREFIX':
                preg_match('/^\((\w+)\)(.*)$/', $value, $out);
                for ($i=0;$i<strlen($out[1]);$i++) {
                    $mode = $out[1][$i];
                    $prefix = $out[2][$i];
                    $this->mode[$mode] = new IRCMode(
                        $mode, $prefix, substr($out[1], $i + 1), 'U'
                    );
                    $this->prefix[$prefix] = $this->mode[$mode];
                }
                break;
            case 'CHANMODES':
                $modes = Array();
                list(
                    $modes['A'], 
                    $modes['B'], 
                    $modes['C'], 
                    $modes['D']) = explode(',', $value);
                foreach ($modes as $k=>$v) {
                    for ($i=0;$i<strlen($v);$i++) {
                        $this->mode[$v[$i]] = new IRCMode($v[$i], '', '', $k);
                    }
                }
                break;
            }
        }
        return true;
    }

    /**
     * Handles /names reply (received on join)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle353($extra) 
    {
        extract($extra);
        $users = explode(' ', $msg);
        foreach ($users as $nick) {
            if (trim($nick) == '') {
                continue;
            }
            if ($p = $this->prefix($nick[0])) {
                $this->chan($args[1])->add($this->user(substr($nick, 1)), $p);
            } else {
                $this->chan($args[1])->add($this->user($nick));
            }
        }
    }

    /**
     * Handles /who reply
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle352($extra) 
    {
        extract($extra);
	if ($msg == '' || count($args) < 5)
	    return false;

	list(,$name) = explode(' ', $msg, 2);
        $this->user($args[4])->check($args[1], $args[2], $name);
    }

    /**
     * Handles /mode reply (received on join)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle324($extra) 
    {
        extract($extra);
        $chan = array_shift($args);
        $this->parseMode($chan, $args);
    }

    /**
     * Handles /mode +b reply (to build a list of active bans)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handle367($extra) 
    {
        extract($extra);
        $m = '+b';
        $a = $args[1];
        $chan = $args[0];
        $this->parseMode($chan, Array($m,$a));
    }

    /**
     * Handles mode changes
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleMode($extra) 
    {
        extract($extra);

        if ($private) {
            return; // Ignore modes set on myself
        }

        return $this->parseMode($target, $args);
    }

    /**
     * Handles a received SETHOST (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleSethost($extra) 
    {
        extract($extra);
        $this->user($nick)->check('', $target);
    }

    /**
     * Handles a received SETIDENT (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleSetident($extra) 
    {
        extract($extra);
        $this->user($nick)->check($target);
    }

    /**
     * Handles a received SETNAME (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleSetname($extra) 
    {
        extract($extra);
        $this->user($nick)->check('', '', $msg);
    }

    /**
     * Handles a received CHGHOST (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleChghost($extra) 
    {
        extract($extra);
        $this->user($target)->check('', $args[0]);
    }

    /**
     * Handles a received CHGIDENT (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleChgident($extra) 
    {
        extract($extra);
        $this->user($target)->check($args[0]);
    }

    /**
     * Handles a received CHGNAME (Used by service to keep track of users)
     *
     * @param array $extra Array of stuff
     *
     * @return void
     */
    protected function handleChgname($extra) 
    {
        extract($extra);
        $this->user($target)->check('', '', $msg);
    }

    /**
     * Parses a mode string and stores the changes to the affected objects 
     *
     * @param string $chan Channel name
     * @param Array  $args Array of arguments to MODE command
     *
     * @return void
     */
    protected function parseMode($chan, $args) 
    {
        // [DEBUG:REPLAY] 
        // [src:NeoServ] 
        // [func:MODE] 
        // [nick:NeoServ] 
        // [target:#neotor] 
        // [replyto:#neotor] 
        // [args:{ [0:+o] [1:NeoServ] }]
        $modestr = array_shift($args);

        for ($i=0;$i<strlen($modestr);$i++) {
            $m = $modestr[$i];

            if ($m == '+') { 
                $p = 1; 
                continue; 
            }

            if ($m == '-') { 
                $p = 0;
                continue;
            }

            if (false !== $o = $this->mode($m)) {
                if ($o->type == 'U' 
                    || $o->type == 'A' 
                    || $o->type == 'B' 
                    || $o->type == 'C' 
                    && $p
                ) {
                    $a = array_shift($args);
                } else {
                    $a = false;
                }

                if ($p) {
                    $this->chan($chan)->addmode($o, $a);
                } else {
                    $this->chan($chan)->removemode($o, $a);
                }
            }
        }
    }

    /**
     * The construct
     *
     * @param Irc $parent Parent IRC object
     *
     * @return void
     */
    public function __construct($parent) 
    {
        $this->id = uniqid();
        $this->parent = $parent;
        $this->parent->attach(
            $this->id, 
            Array($this, 'ircraw'), 
            Array(
                '005','PROTOCTL', // Connect info
                '324','329', // MODE, CREATED
                '367','368', // BANS
                '332','333', // TOPIC
                '353','366', // NAMES
                '352','315', // WHO
                '311','307','319','312','301','313','317','318', // WHOIS
                '352','315', // WHO
                'JOIN','PART','KICK','MODE','TOPIC', // CHANNEL CHANGES
                'QUIT','NICK','KILL','SVSKILL', // USER CHANGES
                'SETHOST','SETIDENT','SETNAME', // 
                'CHGHOST','CHGIDENT','CHGNAME', // 
                'PRIVMSG','NOTICE', // USED TO FILL IN THE BLANKS FROM /NAMES
                'SERVER'
            )
        );
    }

    /**
     * Removes a user from $users
     *
     * @param string $nick Nickname to remove
     *
     * @return void
     */
    public function removeUser($nick) 
    {
        $lnick = strtolower($nick);
        unset($this->users[$lnick]);
    }

    /**
     * Removes a channel from $channels
     *
     * @param string $channel Name of channel
     *
     * @return void
     */
    public function removeChannel($channel) 
    {
        $lchan = strtolower($channel->name);
        unset($this->channels[$lchan]);
    }

    /**
     * Get properties
     *
     * @param string $var Name of property to get
     *
     * @return mixed Value of property
     */
    public function __get($var) 
    {
        if ($var == 'me') {
            return $this->me;
        }
    }

    /**
     * Returns the specified IRCPrefix object
     *
     * @param string $prefix Symbol for prefix to get
     *
     * @return IRCPrefix The requested prefix object
     */
    public function prefix($prefix) 
    {
        if (is_object($prefix)) {
            if (get_class($prefix) == 'IRCMode') {
                return $prefix;
            } else {
                return false;
            }
        }
        return ( !empty($prefix) 
            && isset($this->prefix[$prefix]) ? $this->prefix[$prefix] : false 
        );
    }

    /**
     * Returns the specified IRCMode object
     *
     * @param string $mode Mode letter
     *
     * @return IRCMode The requested mode object
     */
    public function mode($mode) 
    {
        if (isset($this->mode[$mode])) {
            return $this->mode[$mode];
        } else {
            return false;
        }
    }

    /**
     * Callback for attach(), parses out what function was received and
     * calls the associated handler-method
     *
     * @param Irc    $parent Parent irc object
     * @param string $data   Raw data that was received
     * @param Array  $extra  Array of helper variables 
     *
     * @return void
     */
    public function ircraw($parent, $data, $extra) 
    {
        extract($extra);
        $lnick = strtolower($nick);

        if (isset($this->users[strtolower($nick)]) && !empty($nick)) {
            $this->user($nick)->check($ident, $host);
        }

        $lfunc = ucfirst(strtolower($func));

        if (method_exists($this, $f = "handle$lfunc")) {
            $this->$f($extra);
        }
    }

    /**
     * Returns an array of known users as IRCUser objects
     *
     * @return Array Array of IRCUser objects
     */
    public function users() 
    {
        return $this->users;
    }

    /**
     * Returns an array of known channels as IRCChannel objects
     *
     * @return Array Array of IRCChannel objects
     */
    public function chans() 
    {
        return $this->channels;
    }

    /**
     * Returns an array of known servers as IRCServer objects
     *
     * @return Array Array of IRCServer objects
     */
    public function servers() 
    {
        return $this->servers;
    }

    /**
     * Returns an array of known modes as IRCMode objects
     *
     * @return Array Array of IRCMode objects
     */
    public function modes() 
    {
        return $this->mode;
    }

    /**
     * Returns a user object for a specific nickname
     *
     * @param string  $nick   Nickname
     * @param Boolean $create Create IRCUser object if non-existant
     * 
     * @return IRCUser Object of the requested user
     */
    public function user($nick, $create = true) 
    {
        if ($nick == '') {
            return false;
        }

        foreach ($this->users as $k => $v) {
            if (strtolower($k) == strtolower($nick)) {
                return $v;
            }
        }
        return ($create ? 
            $this->users[strtolower($nick)] = new IRCUser(
                $this, $nick, '', ''
            ) : false
        );
    }

    /**
     * Returns a channel object for a specific channel name
     *
     * @param string  $name   Channel name
     * @param Boolean $create Create IRCChannel object if non-existant
     * 
     * @return IRCChannel Object of the requested channel
     */
    public function chan($name, $create = true) 
    {
        if ($name == '') {
            return false;
        }
        if (isset($this->channels[strtolower($name)])) {
            return $this->channels[strtolower($name)];
        }
        return ($create ? 
            $this->channels[strtolower($name)] = new IRCChannel(
                $this, $name
            ) : false
        );
    }

    /**
     * Returns a server object for a specific server
     *
     * @param string  $name   Server name
     * @param Boolean $create Create IRCServer object if non-existant
     * 
     * @return IRCServer Object of the requested server
     */
    public function server($name, $create = true) 
    {
        if ($name == '') {
            return false;
        }

        if (isset($this->servers[strtolower($name)])) {
            return $this->servers[strtolower($name)];
        }

        return ($create ? 
            $this->servers[strtolower($name)] = new IRCServer(
                $this, $name
            ) : false
        );
    }

    /**
     * Helper function to trigger an event in the Irc object
     *
     * @param string $event Name of the triggered event
     * @param mixed  $extra Extra data to send to attach():ed methods
     * 
     * @return void
     */
    public function trigger($event, $extra) 
    {
        $this->parent->trigger($event, $extra);
    }

}

/**
 * Stores information about a known user on irc, such as nickname, ident, host, joined channels
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
class IRCUser
{
    /**
     * Nickname
     *
     * @var string
     */
    protected $nick     = '';
    /**
     * The users ident
     *
     * @var string
     */
    protected $ident    = '';
    /**
     * The users hostname
     *
     * @var string
     */
    protected $host     = '';
    /**
     * The users real host (only used by service)
     *
     * @var string
     */
    protected $realhost = '';
    /**
     * The users realname
     *
     * @var string
     */
    protected $realname = '';
    /**
     * Channels that the user is known in
     *
     * @var Array
     */
    protected $channels = Array();
    /**
     * Parent users object
     *
     * @var Users
     */
    protected $parent;
    /**
     * What server the user is connected to (service only)
     *
     * @var string
     */
    protected $server   = '';
    /**
     * Signon time for the user - unix timestamp (service only)
     *
     * @var Integer
     */
    protected $signon   = 0;

    /**
     * Called on construction of the object, sets properties and triggers a X_USER_CREATE event
     * 
     * @param Users $parent Users object
     * @param string $nick  Nickname
     * @param string $ident Users ident
     * @param string $host  Users hostname
     * @return void
     */
    public function __construct($parent, $nick, $ident='', $host='') 
    {
        $this->parent = $parent;
        $this->nick = $nick;
        $this->ident = $ident;
        $this->host = $host;
        $this->parent->trigger("X_USER_CREATE", Array('user' => $this));
        //logWrite(L_DEBUG, "Created user $nick");
    }

    /**
     * Called on destruction of the object, triggers a X_USER_REMOVE event
     * 
     * @return void
     */
    public function __destruct() 
    {
        $this->parent->trigger("X_USER_REMOVE", Array('user' => $this));
        //logWrite(L_DEBUG,"User {$this->nick} deleted");
    }

    /**
     * Returns the specified property
     * 
     * @param string $var Property name
     * @return mixed Value of property
     */
    public function __get($var) 
    {
        if ($var == 'parent') {
            return false;
        }

        if (isset($this->$var)) {
            return $this->$var;
        }

        return false;
    }

    /**
     * Adds the user to a specified channel
     * 
     * @param IRCChannel $channel Channel object
     * @return IRCUser User object (for chaining)
     */
    public function add($channel) 
    {
        $chan = strtolower($channel->name);
        $this->channels[$chan] = $channel;
        return $this;
    }

    /**
     * Sets what server the user is connected to
     * 
     * @param IRCServer $server Server object
     * @return IRCUser User object (for chaining)
     */
    public function setServer($server) 
    {
        $this->server = $server;
        return $this;
    }

    /**
     * Sets the sign on time for a user
     * 
     * @param integer $signon
     * @return IRCUser User object (for chaining)
     */
    public function setSignon($signon) 
    {
        $this->signon = $signon;
        return $this;
    }

    /**
     * Returns true if the user is on a specified channel
     * 
     * @param string $channel Channel name
     * @return Boolean true if the user is on the specified channel
     */
    public function ison($channel) 
    {
        $chan = strtolower($channel);
        if (isset($this->channels[$chan])) {
            return true;
        }

        return false;
    }

    /**
     * Remove the user from a specified channel
     * 
     * @param IRCChannel $channel Channel object
     * @param Boolean    $recurse True if the user should be removed from the channel object
     * @return IRCUser User object (for chaining)
     */
    public function remove($channel, $recurse=true) 
    {
        $chan = strtolower($channel->name);
        if (isset($this->channels[$chan])) {
            unset($this->channels[$chan]);
            if ($recurse) {
                $channel->remove($this, false);
            }
        }

        if (count($this->channels) == 0 && $this != $this->parent->me && !SERVICE) {
            $this->quit(1);
        }
        return $this;
    }

    /**
     * Called when a user quits and should be removed
     * 
     * @param integer $skipchannels Deprecated
     * @return IRCUser User object (for chaining)
     */
    public function quit($skipchannels = 0) 
    {
        foreach ($this->channels as $chan) {
            $this->remove($chan);
        }
        $this->parent->removeuser($this->nick);
        return $this;
    }

    /**
     * Called on nick change
     * 
     * @param string $new New nickname
     * @return IRCUser User object (for chaining)
     */
    public function nick($new) 
    {
        foreach ($this->channels as $chan) {
            $chan->rename($this, $new);
        }
        $this->nick = $new;
        return $this;
    }

    /**
     * Sets a users ident, host and realname
     * 
     * @param string $ident     ident of the user
     * @param string $host      hostname of the user
     * @param string $realname  realname of the user
     * @return IRCUser User object (for chaining)
     */
    public function check($ident = '', $host = '', $realname='') 
    {
        if (!empty($ident)) {
            $this->ident = $ident;
        }
        if (!empty($host)) {
            $this->host = $host;
            if (empty($this->realhost)) {
                $this->realhost = $host;
            }
        }
        if (!empty($realname)) {
            $this->realname = $realname;
        }
        return $this;
    }
}

/**
 * Stores information about a known irc channel, such as what users are in it, what modes are set, topic, etc
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
class IRCChannel
{
    /**
     * Parent object
     *
     * @var Users
     */
    protected $parent;
    /**
     * Channel name
     *
     * @var string
     */
    protected $name;
    /**
     * Topic string
     *
     * @var string
     */
    protected $topic;
    /**
     * Nickname that set the topic and time that the topic was set
     *
     * @var Array
     */
    protected $topicBy;
    /**
     * Channel modes
     *
     * @var Array
     */
    protected $mode;
    /**
     * Array of known users in the channel
     *
     * @var Array
     */
    protected $users = Array();

    /**
     * Called on creation of the object, sets properties and triggers a X_CHAN_ADD event
     * 
     * @param Users  $parent Users object
     * @param string $name   Channel name
     * @return void
     */
    public function __construct($parent, $name) 
    {
        //logWrite(L_DEBUG,'Created channel '.$name);
        $this->parent = $parent;
        $this->name = $name;
        $this->parent->trigger("X_CHAN_ADD", Array('chan'=>$this));
    }


    /**
     * Called on destruction of the object, triggers a X_CHAN_REMOVE event
     * 
     * @return void
     */
    public function __destruct() 
    {
        $this->parent->trigger("X_CHAN_REMOVE", Array('chan'=>$this));
        //logWrite(L_DEBUG,"Channel {$this->name} deleted");
    }

    /**
     * Stores a new topic string
     * 
     * @param string $newTopic Topic string
     * @return IRCChannel Channel object (for chaining)
     */
    public function topic($newTopic) 
    {
        $this->topic = $newTopic;
        return $this;
    }

    /**
     * Stores who the topic is set by, and time it's set
     * 
     * @param string $nick Nickname
     * @param integer $time Unix timestamp
     * @return IRCChannel Channel object (for chaining)
     */
    public function topicby($nick,$time=0) 
    {
        $this->topicBy = Array($nick,$time?$time:time());
        return $this;
    }

    /**
     * Checks if a user has a specified mode or if the mode is set
     * 
     * @param string $mode Mode letter
     * @param string $nick Nickname
     * @return Boolean true if mode is set
     */
    public function hasmode($mode, $nick = '') 
    {
        if (empty($nick)) {
            for ($i=0;$i<strlen($mode);$i++) {
                if (isset($this->mode[$mode])) {
                    return true;
                }
            }
            return false;
        }
        if (is_object($nick)) {
            $nick = $nick->nick;
        }
        $lnick = strtolower($nick);

        foreach ($this->users[$lnick][1] as $m) {
            if ($m->mode == $mode || false!==strpos($m->includes, $mode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the specified user is on the channel
     * 
     * @param string $nick Nickname
     * @return Boolean true if user is joined to the channel
     */
    public function ison($nick) 
    {
        $nick = strtolower($nick);
        if (isset($this->users[$nick])) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of modes the specified user has
     * 
     * @param string $nick Nickname
     * @return Array Array of IRCMode
     */
    public function getmode($nick) 
    {
        if (is_object($nick)) {
            $nick = $nick->nick;
        }

        $lnick = strtolower($nick);

        return $this->users[$lnick][1];
    }

    /**
     * Returns true if the user has op in the channel
     * 
     * @param string $nick Nickname to check
     * @return Boolean True if the user has op status
     */
    public function isop($nick) 
    {
        return $this->hasmode($nick, 'o');
    }

    /**
     * Returns the value of the specified property
     * 
     * @param string $var What property to fetch
     * @return mixed Value of property
     */
    public function __get($var) 
    {
        if ($var == 'parent') {
            return false;
        }

        if (isset($this->$var)) {
            return $this->$var;
        }

        return false;
    }

    /**
     * Adds a user to the channel
     * 
     * @param IRCUser $user
     * @param string  $mode
     * 
     * @return IRCChannel Channel object (for chaining)
     */
    public function add($user, $mode='') 
    {
        $lnick = strtolower($user->nick);
        if (!isset($this->users[$lnick])) {
            $this->parent->trigger(
                "X_CHANUSER_ADD", 
                Array(
                    'user' => $user ,
                    'chan' => $this
                )
            );
        }
        //logWrite(L_DEBUG,"Added {$user->nick} to channel {$this->name}");
        $this->users[$lnick] = Array(
            $user, 
            is_object($mode) ? 
            Array(
                $mode->mode => $mode
            ) : Array()
        );
        $user->add($this);

        return $this;
    }

    /**
     * Removes a user from the channel
     *
     * @param IRCUser $user    User object
     * @param Boolean $recurse true if the channel should be removed from the user
     *
     * @return IRCChannel Channel object (for chaining)
     */
    public function remove($user, $recurse = true) 
    {

        $lnick = strtolower($user->nick);

        if (isset($this->users[$lnick])) {
            $this->parent->trigger(
                "X_CHANUSER_REMOVE", 
                Array(
                    'user' => $user, 
                    'chan' => $this
                )
            );
            //logWrite(L_DEBUG,"Removed {$user->nick} from channel {$this->name}");
            unset($this->users[$lnick]);
            if ($recurse) {
                $user->remove($this, false);
            }
        }

        if ($user == $this->parent->me && !SERVICE) {
            foreach ($this->users as $k=>$v) {
                $v[0]->remove($this, false);
            }
            $this->parent->removeChannel($this);
        }

        if (SERVICE && count($this->users) == 0) {
            $this->parent->removeChannel($this);
        }

        return $this;
    }

    /**
     * Renames users
     *
     * @param IRCUser $user User object
     * @param string  $new  New nick
     *
     * @return IRCChannel Channel object (for chaining)
     */
    public function rename($user, $new) 
    {
        $lnick = strtolower($user->nick);
        $lnew = strtolower($new);

        if ($lnew != $lnick) {
            $this->users[$lnew] = $this->users[$lnick];
            unset($this->users[$lnick]);
        }
        return $this;
    }

    /**
     * Adds mode
     *
     * @param IRCMode $mode Mode
     * @param string  $arg  Argument to use
     *
     * @return void
     */
    public function addmode($mode, $arg) 
    {
        switch($mode->type) {
        case 'U':
            $lnick = strtolower($arg);
            $this->users[$lnick][1][$mode->mode] = $mode;
            break;
        case 'A':
            if (!isset($this->mode[$mode->mode])) {
                $this->mode[$mode->mode] = Array('mode' => $mode, 'list' => Array());
            }
            $this->mode[$mode->mode]['list'][] = $arg;
            break;
        case 'B':
        case 'C':
            $this->mode[$mode->mode] = Array('mode' => $mode, 'value' => $arg);
            break;
        case 'D':
            $this->mode[$mode->mode] = Array('mode' => $mode);
            break;
        }
    }

    /**
     * Removes mode on user
     *
     * @param IRCMode $mode Mode to be removed.
     * @param string  $arg  Argument
     *
     * @return void
     */
    public function removemode($mode, $arg) 
    {
        switch($mode->type) {
        case 'U':
            $lnick = strtolower($arg);
            if (isset($this->users[$lnick][1][$mode->mode])) {
                unset($this->users[$lnick][1][$mode->mode]);
            }
            break;
        case 'A':
            if (isset($this->mode[$mode->mode]['list'])) {
                foreach ($this->mode[$mode->mode]['list'] as $k=>$v) {
                    if ($v==$arg) {
                        unset($this->mode[$mode->mode]['list'][$k]);
                    }
                }
            }
            break;
        case 'B':
        case 'C':
        case 'D':
            if (isset($this->mode[$mode->mode])) {
                unset($this->mode[$mode->mode]);
            }
            break;
        }
    }
}
