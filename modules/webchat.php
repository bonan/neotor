<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Views activate webchat users.
 *
 * PHP version 5
 *
 * @category  webchat
 * @package   Modules
 * @author    bonan bonan <bonan@zenet.org>
 * @copyright 2012 bonan <bonan@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Views activate webchat users.
 *
 * PHP version 5
 *
 * @category  webchat
 * @package   Modules
 * @author    bonan bonan <bonan@zenet.org>
 * @copyright 2012 bonan <bonan@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class webchat
{

    /**
     * The unique id of this module
     *
     * @var string
     */

    protected $id;

    /**
     * The caller of this module
     *
     * @var object
     * @access protected
     */

    protected $parent;

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
        $parent->attach('webchat_' . $this->id, array($this, 'webchatinfo'), array('PRIVMSG'),
            '/^!webchat$/i');
    }

    /**
     * Webchat info (search: ident@ on users)
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

    public function webchatinfo($parent, $data, $extra)
    {
        extract($extra);
        $wc = 0;
        $ch = array();

        if (strtolower($target) == '#opers') {
            foreach ($this->parent->users() as $u) {
                if ($u->ident == 'webchat') {
                    foreach ($u->channels as $c) {
                        if (!isset($ch[strtolower($c->name)]))
                            $ch[strtolower($c->name)] = 0;

                        $ch[strtolower($c->name)]++;
                    }
                    $wc++;
                }
            }


            $this->parent->privmsg($replyto, '[WEBCHAT USERS: ' . $wc . ']');
            foreach ($ch as $n => $i) {
                if ($i < 3)
                    continue;
                $this->parent->privmsg($replyto, '[' . $n . '] ' . $i);
            }

        }

    }
}
