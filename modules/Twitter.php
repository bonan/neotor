<?php

/**
 * Handles Twitter actions for neotor.
 *
 * PHP version 5
 *
 * @category  Irc
 * @package   Modules
 * @author    oldmagic oldmagic <oldmagic@zenet.org>
 * @copyright 2012 oldmagic <oldmagic@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Calls twitter api and returns data.
 *
 * PHP version 5
 *
 * @category  Irc
 * @package   Modules
 * @author    oldmagic oldmagic <oldmagic@zenet.org>
 * @copyright 2012 oldmagic <oldmagic@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class Twitter implements Module
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
        $parent->attach('Twitter_' . $this->id, array($this, 'twitter'), array('PRIVMSG'),
            '/^!twitter (?P<search>.*)$/i');
    }

    /**
     * Opens twitter httpSession
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

    public function twitter($parent, $data, $extra)
    {
        extract($extra);
        $http = new httpSession('search.twitter.com');
        $http->getPage(array($this, 'twitterResult'), $extra, '/search.json?q=' .
            urlencode($regexp['search']) . '&result_type=recent&rpp=1');
    }

    /**
     * Prints httpSession twitter results
     *
     * @param object $http Object with http data.
     * @param object $page Object with the return data.
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

    public function twitterResult($http, $page, $extra)
    {
        extract($extra);
        if ($page->status != 200)
            return;
        $results = json_decode($page->data);
        foreach ($results->results as $r) {
            print_r($r);
            $from_user = $r->from_user;
            $to_user = $r->to_user;
            $text = $r->text;
            $id = $r->id_str;
            if ($to_user) {
                $this->parent->privmsg($replyto, "$from_user: RT @$to_user - $text ( $id@$from_user )");
            } else {
                $this->parent->privmsg($replyto, "$from_user: $text ( $id@$from_user )");
            }
        }

    }
}
