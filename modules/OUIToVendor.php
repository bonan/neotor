<?php


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handling OUI to vendor lookups
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  OUI
 * @package   Modules
 * @author    NDRS Hackersson <ndrs@g33k.nu>
 * @copyright 2012-2012 neotor group
 * @license   http://neotor.se/License BSD License
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Class to handle OUI to vendor lookups
 *
 * @category  OUI
 * @package   Modules
 * @author    NDRS Hackersson <ndrs@g33k.nu>
 * @copyright 2012-2012 neotor group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PackageName
 */
class OUIToVendor implements Module
{
    // {{{ properties

    /** 
     * Unique id for this instance.
     * @var string
     */
    protected $id;

    /**
     * Holds parent object
     * @var object
     */
    protected $parent;

    /**
     * Holds database object
     * @var object
     */
    protected $db;
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
        $this->db = mysql_connect(
            CONFIG_mysql_server, 
            CONFIG_mysql_username, 
            CONFIG_mysql_password
        );

        $parent->attach(
            'OUIToVendor_'.$this->id,
            Array($this,'getVendor'),
            Array('PRIVMSG'),
            '/^!oui\s(?P<oui>(?:[0-9A-F]{2}[:\.]*?){3}):?(?P<mac>(?1))?/i'
        );
    }

    /**
     * Looks for new oui data if it doesn't exist in database yet
     *
     * @param object $parent The calling object.
     * @param array  $extra  Extra data
     *
     * @return none
     */
    public function getNewVendorData($parent, $extra)
    {
        extract($extra);
        $http = new httpSession('www.macvendorlookup.com');
        $http->setMaxLength(4096);
        $http->getPage(
            Array($this, 'SaveVendor'),
            array_merge($extra, array('parent' => $parent)),
            '/api/'.CONFIG_MACVENDOR_APIKEY.'/'.$regexp['oui']
        );
    }

    /**
     * Gets vendordata from database
     *
     * @param int $oui The oui to look for
     *
     * @return array Array with vendor data.
     */
    private function _getData($oui)
    {
        mysql_select_db(CONFIG_mysql_database, $this->db);
        $result = mysql_query(
            sprintf(
                "SELECT company FROM oui where oui='%s' LIMIT 1;", 
                $oui
            )
        );
        if (mysql_num_rows($result) == 0) {
            return false;
        } else {
            $VendorData = mysql_fetch_assoc($result);
            return $VendorData['company'];
        }
    }


    /**
     * Called whenever someone issues !oui <oui> in a channel
     *
     * @param object $parent The calling object.
     * @param array  $data   Data
     * @param array  $extra  Extra data
     *
     * @return none
     */
    public function getVendor($parent, $data, $extra)
    {
        extract($extra);

        $oui = preg_replace('/[^0-9A-F]/i', '', $regexp['oui']);
        $oui = str_replace(array('.',':'), '', $oui);

        if (!$VendorData = $this->_getData($oui)) {
            $this->getNewVendorData($parent, $extra);
        } else {
            $parent->privmsg(
                $replyto,
                sprintf(
                    "%s belongs to %s",
                    $oui,
                    $VendorData
                )
            );
        }
    }

    /**
     * Saves vendordata from oui lookup
     *
     * @param object $http http object
     * @param object $page page object containing data from http call
     * @param array  $vars Variables needed.
     *
     * @return none
     */
    public function saveVendor($http, $page, $vars)
    {
        extract($vars);

        if (false === strpos($page->data, 'Error:') && $page->data != "none") {
            $VendorData = json_decode($page->data, $assoc = true);
            $VendorData = $VendorData[0];
            mysql_select_db(CONFIG_mysql_database, $this->db);
            mysql_query(
                sprintf(
                    'INSERT INTO oui '.
                    '(oui, company, department, address1, address2, country) '.
                    "VALUES ('%s','%s','%s','%s','%s','%s')",
                    $VendorData['oui'],
                    $VendorData['company'],
                    $VendorData['department'],
                    $VendorData['address1'],
                    $VendorData['address2'],
                    $VendorData['country']
                )
            );
            $parent->privmsg(
                $replyto, 
                sprintf(
                    '%s belongs to %s', 
                    $VendorData['oui'], 
                    $VendorData['company']
                )
            );
        } else {
            $parent->privmsg($replyto, trim($page->data));
        }
    }
}
