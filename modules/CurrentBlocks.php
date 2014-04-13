<?php


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Getting current block height from cryptocurrencies
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   currentBlocks
 * @package    Modules
 * @author     WaiXan Hackersson <waixan@waixan.se>
 * @copyright  2012-2012 neotor group
 * @license    http://neotor.se/License BSD License
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/PackageName
 */

/*
* Place includes, constant defines and $_GLOBAL settings here.
* Make sure they have appropriate docblocks to avoid phpDocumentor
* construing they are documented by the page-level docblock.
*/

class currentBlocks implements Module
{

    // {{{ properties

    /**
     * Holds a unique id for this instance
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
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */
    public function __construct($parent, $params) 
    {
        $this->id = uniqid();
        $this->parent = $parent;
        $parent->attach(
            'currentblocks_'.$this->id, 
            Array($this,'getCurrentCount'), 
            Array('PRIVMSG'), 
            '/^!blocks\s(?<curr>btc|ltc)$/i'
        );
    }

    /** 
     * Called whenever someone wants to check current block height.
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function getCurrentCount($parent, $data, $extra) 
    {
        extract($extra);
        switch($extra['regexp']['curr']){
            case 'btc':
                $http = new HttpSession('blockexplorer.com', 80);
                $http->setMaxLength(1024);
                $http->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31');
                $http->getPage(
                    array($this, 'outputData'),
                    array_merge($extra, array('curr' => 'BTC')),
                    '/q/getblockcount'
                );
                break;
            case 'ltc':
                $http = new HttpSession('litecoinscout.com', 80);
                $http->setMaxLength(1024);
                $http->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31');
                $http->getPage(
                    array($this, 'outputData'),
                    array_merge($extra, array('curr' => 'LTC')),
                    '/chain/Litecoin/q/getblockcount'
                );
                break;
        }
    }
    
    public function outputData($http, $page, $vars) {
        extract($vars);
        if ($page->status != 200) {
            echo $page->data;
        }

        /* TODO Return wrong LTC block count, http return content gets prefixed by a 6 and suffixed by a 0 */

        $this->parent->privmsg(
                $replyto, 
                sprintf(
                    "%s: %d blocks",
                    $curr, $page->data
                )
        );
    }

}
