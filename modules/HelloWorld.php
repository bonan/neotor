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
class HelloWorld implements Module
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
            'trackpackages_'.$this->id, 
            Array($this,'helloWorld'), 
            Array('PRIVMSG'), '/^!helloworld$/i'
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
    public function helloWorld($parent, $data, $extra)
    {
        extract($extra);
        $this->parent->privmsg($replyto, "HELLO, WORLD!");
    }
}
