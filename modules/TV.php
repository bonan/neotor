<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handles TV actions for neotor.
 *
 * PHP version 5
 *
 * @category  IrcTV
 * @package   Modules
 * @author    oldmagic oldmagic <oldmagic@zenet.org>
 * @copyright 2012 oldmagic <oldmagic@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles TV actions for neotor.
 *
 * PHP version 5
 *
 * @category  IrcTV
 * @package   Modules
 * @author    oldmagic oldmagic <oldmagic@zenet.org>
 * @copyright 2012 oldmagic <oldmagic@zenet.org>
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class TV implements Module
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
        $parent->attach('TV_' . $this->id, array($this, 'tv'), array('PRIVMSG'),
            '/^!tv (?P<search>.*)$/i');
    }

    /**
     * Command Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     */

    public function tv($parent, $data, $extra)
    {
        extract($extra);

        list($match, $text) = explode(" ", $regexp['search'], 2);
        if ($match && !$text) {
            $this->TVHelp($extra, $match);
        }

        if ($match && $text) {
            switch ($match) {
                case 'search':
                    $this->TVSearch($extra, $text);
                    break;
                case 'details':
                    $this->TVDeatils($extra, $text);
                    break;
                case 'info':
                    $this->TVInfo($extra, $text);
                    break;
                case 'list':
                    $this->TVList($extra, $text);
                    break;
            }
        }
    }

    /**
     * Checks for listed commands.
     *
     * @param array  $extra  Extra data such as regexps
     * @param $match includes the acually search word
     *
     */

    public function TVHelp($extra, $match)
    {

        extract($extra);

        switch ($match) {
            case 'search':
                $this->parent->privmsg($replyto, "SEARCH      Lists TV shows from search text");
                $this->parent->privmsg($replyto, "--------------------------------");
                break;
            case 'details':
                $this->parent->privmsg($replyto, "DETAILS     Views details from a specific TV show");
                $this->parent->privmsg($replyto, "--------------------------------");
                $this->parent->privmsg($replyto, "Syntax: !tv details buffy");
                break;
            case 'info':
                $this->parent->privmsg($replyto, "INFO        Lists TV show info");
                $this->parent->privmsg($replyto, "--------------------------------");
                $this->parent->privmsg($replyto, "Syntax: !tv info ID");
                break;
            case 'list':
                $this->parent->privmsg($replyto, "LIST        Lists episodes from a TV show");
                $this->parent->privmsg($replyto, "--------------------------------");
                $this->parent->privmsg($replyto, "Syntax: !tv list ID");
                break;
            case 'justnu':
                $this->TVJustnu($extra, $text);
                break;
            default:
                $this->parent->privmsg($replyto, "Help Commands (search|details|info|list)");
                break;
        }

    }

    /**
     * Requests the information from httpSession.
     *
     * @param array  $extra  Extra data such as regexps
     * @param $text includes the acually search word
     *
     */

    public function TVSearch($extra, $text)
    {

        extract($extra);

        $http = new httpSession('services.tvrage.com');
        $http->getPage(array($this, 'TVSearch_results'), $extra,
            '/feeds/search.php?show=' . urlencode($text) . '');


    }

    /**
     * Reviews the information.
     *
     * @param array  $http includes data.
     * @param array  $page  includes the httpSession data.
     * @param array  $extra  Extra data such as regexps
     *
     */

    public function TVSearch_results($http, $page, $extra)
    {
        extract($extra);
        if ($page->status != 200)
            return;
        $results = new SimpleXMLElement($page->data);
        foreach ($results->show as $r) {
            $showid = $r->showid;
            $title = $r->name;
            $link = $r->link;
            $started = $r->started;
            $ended = $r->ended;
            $seasons = $r->seasons;
            $status = $r->status;
            $genres = '';
            foreach ($r->genres->genre as $genre) {
                $genres .= " $genre ";
            }
            print ("$showid - $title - $link - $started - $ended - $seasons - $status\n");
            print ($genres);
            $this->parent->privmsg($replyto, "[$title] ID: $showid - $link -- Seasons: $seasons - Start/End: $started/$ended -- Status: $status");
            //$this->parent->privmsg($replyto, "[$genres]");
        }

    }

    /**
     * Reviews the information.
     *
     * @param array  $http includes data.
     * @param array  $page  includes the httpSession data.
     * @param array  $extra  Extra data such as regexps
     *
     */

    public function TVResult($http, $page, $extra)
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

    /**
     * Requests the information from httpSession.
     *
     * @param array  $extra  Extra data such as regexps
     *
     */

    public function TVJustnu($extra)
    {

        extract($extra);

        $http = new httpSession('omtv.se');
        $http->getPage(array($this, 'TVJustnu_results'), $extra, '/rss/justnu/');


    }

    /**
     * Reviews the information.
     *
     * @param array  $http includes data.
     * @param array  $page  includes the httpSession data.
     * @param array  $extra  Extra data such as regexps
     *
     */

    public function TVJustnu_results($http, $page, $extra)
    {
        extract($extra);
        if ($page->status != 200)
            return;
        $results = new SimpleXMLElement($page->data);
        foreach ($results->channel->item as $r) {
            $title = $r->title;
            $description = $r->description;
            print ("[$title] $description");
            $this->parent->privmsg($replyto, "[$title: $description] ");
        }

    }

}
