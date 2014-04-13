<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Skeleton file / example of how to write a module.
 * 
 * PHP version 5
 * 
 * @category  Example
 * @package   Modules
 * @author    Author Author <author@example.com>
 * @copyright 2007-2012 Author Author <author@example.com>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

/**
 * Example/skeleton class to demonstrate how to writea module.
 *
 * PHP version 5
 *
 * @category  Example
 * @package   Modules
 * @author    Author Author <author@example.com>
 * @copyright 2007-2012 Author Author <author@example.com>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */
class Clones implements Module
{
    // {{{ properties

    /**
     * Holds unique id of this instance
     * @var string
     */
    protected $id;

    /**
     * Holds parent object
     * @var object
     */
    protected $parent;
    
    /**
     * Keeps track of bots
     */
    protected $bots = Array();
    protected $server;
    protected $chan;
    protected $amount;
    protected $interval;
    
    // }}}


    /**
     * The constructor
     *
     * @param object $parent The calling object
     * @param array  $params An array of parameters
     */
    public function __construct($parent, $params) 
    {
        $this->id = uniqid();
        $this->parent = $parent;
        $parent->attach(
            'clones_'.$this->id, 
            Array($this,'clones'), 
            Array('PRIVMSG'), '/^!clones/i'
        );
        $parent->attach(
            'clones_send_'.$this->id, 
            Array($this,'sendRaw'), 
            Array('PRIVMSG'), '/^!rawall/i'
        );
        /* För att lägga till en !trigger behöver du säga till botten 
         * att du har en. Det gör du med $parent->attach().
         *
         * Första argumentet till $parent->attach är ett unikt id, 
         * 
         * Andra argumentet är en array med det här objekt och metoden 
         * som ska kallas när den har blivit triggad. Vi gör det med 
         * callbacks efter som neotor är non blocking. helloWorld är 
         * i det här fallet callback. 
         *
         * Det tredje argumentet är vilken typ av data den ska kolla i.
         * Fjärde argumentet är ett reguljärt uttryck för !triggern. */
    }

    /**
     * Example function that prints "HELLO, WORLD".
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function clones($parent, $data, $extra)
    {
        extract($extra);
        
        list(,$amount, $interval, $server, $chan) = explode(' ', $msg);
        
        $this->amount = $amount;
        $this->interval = $interval;
        $this->server = $server;
        $this->chan = $chan;
        
        $this->makeClone();
        
    }
    
    public function makeClone() {
        
        global $networkList;
        global $ircNetworks;
        
        if (count($this->bots) >= $this->amount)
            return;
        
        do {
            $nick = chr(rand(ord('a'), ord('z'))) . 
                    chr(rand(ord('a'), ord('z'))) . 
                    chr(rand(ord('a'), ord('z'))) . 
                    chr(rand(ord('a'), ord('z'))) . 
                    chr(rand(ord('a'), ord('z'))) . 
                    chr(rand(ord('a'), ord('z'))) . 
                    rand(0, 9) . 
                    rand(0, 9) . 
                    rand(0, 9) . 
                    rand(0, 9);
        } while (isset($this->bots[$nick]));
        
        $i = Array(
            'network' => 'net-'.$nick,
            'servers' => $this->server,
            'nickname' => $nick,
            'altnick' => $nick.'_',
            'username' => $nick,
            'realname' => $nick,
            'autojoin' => $this->chan,
            'active' => 1,
            'perform' => '',
            'vhost' => '',
            'ssl' => 0,
            'pass' => ''
        );
        if (count($this->bots) < $this->amount)
            Timer::add2('makeClone', $this->interval, Array($this, 'makeClone'));
        
        $networkList['clone-'.$nick] = $i;
        $ircNetworks['clone-'.$nick] = $this->bots[$nick] = new Irc('clone-'.$nick);
        
    }
    
    public function sendRaw($parent, $data, $extra)
    {
        extract($extra);
        foreach($this->bots as $v) {
            $v->raw(substr($msg, 8));
        }
    }
}






















