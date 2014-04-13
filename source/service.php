<?php
/**
 * Handles services operations
 *
 * PHP version 5
 *
 */


/**
 * 
 *
 *
 */
abstract class Service
{

    protected $parent;
    protected $modules = Array();
    protected $attached = Array();
    protected $me;

    abstract protected function init();
    public function __construct($parent, $modules) 
    {
        $this->modules = $modules;
        $this->parent = $parent;
        $this->id = get_class($this).uniqid();
        $this->parent->attach($this->id, Array($this, 'gotRaw'));
        $this->parent->attach(
            'service-connect-'.$this->id, 
            Array($this, 'connecting'), 
            Array('X_CONNECT')
        );
        $this->parent->attach(
            'service-connected-'.$this->id, 
            Array($this, 'connected'), 
            Array('EOS')
        );
        if (is_array($modules)) {
            foreach ($modules as $k=>$v) {
                if ($k == get_class($this)) {
                    continue;
                }
                $this->modules[$k] = new $k($this, $k);
            }
        }
    }


    public function register($nick, $ident, $host, $realname)
    {
        $this->parent->raw(
            sprintf(
                'NICK %s 1 %s %s %s %s 0 :%s',
                $nick,
                time(),
                $ident,
                $host,
                $this->parent->getNick(),
                $realname
            )
        );
        return $this->_me = $this->parent->user(
            $nick
        )->check(
            $ident, $host, $realname
        );
    }

    public function connecting($parent, $data, $extra) 
    {
        $this->parent->attach(
            'service-connected-'.$this->id, 
            Array($this, '_connected'), 
            Array('EOS')
        );
    }

    public function connected($parent, $data, $extra) 
    {
        $this->init();
        $this->parent->detach('service-connected-'.$this->id);
    }

    public function gotRaw($parent, $data, $extra) 
    {
        extract($extra);
        if (empty($extra['func'])) {
            return;
        }

        $func = $extra['func'];
        foreach ($this->attached as $id => $val) {
            list($filter, $method, $regexp, $all) = $val;
            if (empty($filter) || in_array($func, $filter)) {
                if (!$all) {
                    if (!empty($regexp)) {
                        if (preg_match($regexp, $msg, $extra['regexp']) == 0) {
                            continue;
                        }
                    }

                    // Filter out what this bot shouldn't see
                    switch($func) {
                    case 'JOIN':
                    case 'PART':
                    case 'MODE':
                    case 'TOPIC':
                    case 'KICK':
                    case 'PRIVMSG':
                    case 'NOTICE':
                        if (strtolower($target) != strtolower($this->me()->nick)
                            && strtolower($nick) != strtolower($this->me()->nick)
                            && !$this->me()->ison($target)
                        ) {
                            break 2;
                        }
                        break;
                    case 'QUIT':
                    case 'NICK':
                        if (empty($nick)) {
                            break 2;
                        }

                        $ison = false;
                        foreach ($this->me()->channels as $c) {
                            $ison |= $c->ison(
                                $nick
                            )||
                            ($func=='NICK'&&$c->ison($target));
                        }

                        if ($nick != $this->me()->nick && !$ison) {
                            break 2;
                        }
                        break;

                    case 'SERVER':
                    case 'PASS':
                    case 'PROTOCTL':
                    case 'AWAY':
                    case 'SETHOST':
                    case 'NETINFO':
                    case 'EOS':
                    case 'SMO':
                    case 'SVSMODE':
                    case 'PONG':
                    case 'SENDSNO':
                    case 'STATS':
                    case 'VERSION':
                    case 'MOTD':
                        break 2;
                    default:
                        break;
                        // Just accept 
                    }
                }
                call_user_func_array($method, Array($this, $data, $extra));
            }
        }
    }

    public function raw($data, $silent = 0, $priority = 3) 
    {
        $data = ':'.$this->me()->nick.' '.$data;
        $this->parent->raw($data);
    }

    public function serverMode($target, $mode) 
    {
        $this->parent->raw(":{$this->parent->getNick()} MODE $target $mode");
    }

    public function privmsg($target, $msg, $silent = 0, $nick = '') 
    {
        $this->parent->privmsg($target, $msg, $silent, $this->me()->nick);
    }

    public function notice($target, $msg, $silent = 0, $nick = '') 
    {
        $this->parent->notice($target, $msg, $silent, $this->me()->nick);
    }

    public function attach($id, $method, $filter = '', $regexp = '', $all = false) 
    {
        $this->attached[$id] = Array($filter, $method, $regexp, $all);
    }

    public function detach($id) 
    {
        unset($this->attached[$id]);
    }

    public function me() 
    {
        return $this->_me;
    }

    public function user($nick) 
    {
        return $this->parent->user($nick);
    }

    public function chan($chan) 
    {
        return $this->parent->chan($chan);
    }

    public function users() 
    {
        return $this->parent->users();
    }

    public function chans() 
    {
        return $this->parent->chans();
    }

    /**
     * Returns text from reading a file
     *
     * @param string $handle Handles fopen $length is the length
     * @return
     */

    public function fread( $handle, $length ) {
        if( ( $ret = fread( $handle, $length ) ) === '' )
                {
                return false;
                }
        return $ret;
    }
}
