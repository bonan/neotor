<?php

$partyline = Array(0 => 0, 'cmd' => Array());

class PartyLine
{
    var $callback;
    var $i;	
    var $auth;
    var $host;
    var $ip;
    var $port;
    var $idle;
    var $init;
    var $c = Array();

    function partyline($i, $type, $callback)
    {
        global $version, $console;
        $this->init = time();		// Set initial-time.
        $this->idle = time();		// Reset idle-counter.
        $this->c['callback'] = &$callback;
        //$this->ip = $ip;
        //$this->port = $port;
        //$this->host = $host;
        $this->i = $i;
        $this->type = $type;
        $this->auth = Array(
            'authed'   => 0,
            'username' => '',
            'password' => ''
        );		// Set standard auth-vars.

        if ($type == 'console') {	// Console requires no login
            $this->auth = Array(
                'authed'   => 1, 
                'username' => 'console', 
                'password' => ''
            );
        } else {
            $this->put(
                sprintf(
                    "Welcome to neotor %s\r\n
                    This service requires authentication\r\n",
                    $version
                ),
                1
            );		// Output to client.
            $this->put("Username: ", 1, 1);		// Ask for username.
        }
    }

    function quit()
    {
        global $partyline;

        logWrite(
           L_TELNET,
           sprintf(
                '[TELNET:%d] User \'%s\' logged out.',
                0,
                $this->auth['username']
            )
        );

        $this->c['callback']->quit();
        if ($this->auth['authed'] == 1) {
            logWrite(
                L_PARTYLINE, 
                sprintf(
                    '[PARTYLINE:%d] User \'%s\' logged off.', 
                    $this->i, 
                    $this->auth['username']
                )
            );
        }
        return;
    }

    function error_connection_reset()
    {
        global $partyline;
        if ($this->auth['authed'] == 1) {
            logWrite(
                L_PARTYLINE, 
                sprintf(
                    '[PARTYLINE:%d] User \'%s\' logged off.', 
                    $this->i, 
                    $this->auth['username']
                )
            );
        }
        return;
    }

    function exec($cmd, $args)
    {

        if ($cmd == '.quit') { 
            $this->quit();
            return; 
        }

        if ($cmd == '.network') { 
            $this->network = $args;
        }

        if ($cmd == '.raw' && $this->type == 'console') {
            global $ircNetworks;

            if (isset($this->network) && isset($ircNetworks[$this->network])) {
                $ircNetworks[$this->network]->raw($args);
            } else {//  If no network is set, use the first in list
                foreach ($ircNetworks as $key=>$value) {
                    $ircNetworks[$key]->raw($args);
                    break;
                }
            }
        }
        if ($cmd == '.msg' || $cmd == '.say') {
            list($chan, $msg) = explode(" ", $args, 2);
            if ($this->auth['username'] == 'prebot'
                && $this->auth['password'] == '1337'
            ) {
                global $ircNetworks;
                if (isset($this->network) && isset($ircNetworks[$this->network])) {
                    $ircNetworks[$this->network]->privmsg($chan, $msg);
                } else { // if no network is set, use the first in list

                    foreach ($ircNetworks as $key=>$value) {
                        $ircNetworks[$key]->privmsg($chan, $msg);
                        break;
                    }
                }
            }
            $this->put("Access denied");
        }

        /*
            FIXME:
            Create some kind of command-handling here.
        */
        return;
    }

    function get($data)
    {
        $data = str_replace("\r", "", str_replace("\n", "", $data));

        if (!empty($data)) {
            $this->idle = time();		// Reset idle-counter

            if ($this->auth['authed'] == 0) {
                if (empty($this->auth['username'])) {
                    $this->auth['username'] = $data;
                    $this->put('Password: ', 1, 1);
                    return;
                } else {
                    /*
                        FIXME:
                        Password entered, check against database.this
                    */

		    if ($this->auth['username'] != CONFIG_telnet_username && $this->auth['password'] != CONFIG_telnet_password)
			$this->quit();

                    $this->put('Auth ok.', 1);
                    $this->put(
                        sprintf(
                            '%s%s (%s) logged in.', 
                            timestamp(), 
                            $this->auth['username'], 
                            $this->host
                        ), 
                        1
                    );
                    $this->auth['authed'] = 1;
                    $this->auth['password'] = $data;
                    logWrite(
                        L_TELNET, 
                        sprintf(
                            '[TELNET:%d] User \'%s\' logged in.', 
                            0, 
                            $this->auth['username']
                        )
                    );
                    return;
                }
            } else {
                $cmd = '';
                $args = '';
                if (strstr($data, ' ')) {
                    list($cmd, $args) = explode(' ', $data, 2);
                } else {
                    $cmd = $data;
                }
                $this->exec($cmd, $args);
                return;
            }
        }
    }

    function put($data, $silent = 0, $norn = 0)
    {
        return $this->c['callback']->put($data, $silent, $norn);
    }

    function newCmd($cmd, $callback)
    {
        global $partyline;
        if (!isset($partyline['cmd'][$cmd])) {
            $partyline['cmd'][$cmd] = $callback;
        } else {
            return false;
        }
    }

}


class Stdin
{
    function stdin($socket)
    {
        global $partyline, $openFiles;
        $this->socket = $socket;
        $partyline[$this->partyI=++$partyline[0]] = new partyline(
            $partyline[0], 
            "console", 
            $this, 
            "0.0.0.0", 
            "console", 
            0
        );
        $this->partyline = $partyline[$this->partyI];
    }

    function get($data)
    {
        $this->partyline->get($data);
    }

    function put($data, $silent = 0, $norn = 0)
    {
        fputs($this->socket, $data. ($norn==0?"\r\n":""));
    }

    function quit()
    {
        return false;
    }

}

//partyline::addCmd('quit', '$this->quit();');

?>
