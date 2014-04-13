<?php


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handling parcel tracking
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   ParcelTracking
 * @package    Modules
 * @author     NDRS Hackersson <ndrs@g33k.nu>
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

/**
 * Class to handle parcel tracking
 *
 * @category   ParcelTracking
 * @package    Modules
 * @author     NDRS Hackersson <ndrs@g33k.nu>
 * @copyright  2012-2012 neotor group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 */
class TrackPackages implements Module
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
            'trackpackages_'.$this->id, 
            Array($this,'locatepackage'), 
            Array('PRIVMSG'), 
            '/^!paket\s(?P<kollinr>[\d]{9,}SE)$/i'
        );
    }

    /** 
     * Called whenever someone wants to track their package(s).
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function locatepackage($parent, $data, $extra) 
    {
        extract($extra);

        $http = new httpSession('server.logistik.posten.se');
        $http->setMaxLength(4096);
        $http->getPage(
            Array($this, 'simpleQuery'), 
            array_merge($extra, Array('parent' => $parent)), 
            '/servlet/PacTrack?lang=SE&kolliid='.$regexp['kollinr']
        );
    }

    /**
     * Converts xmldata into an array.
     *
     * @param string $data A string containing xml data
     *
     * @return array An array with postal data.
     */
    private function _xmlToArray($data) 
    {
        $DataArray = array();
        $DataArray['events'] = array();

        $XMLObject = new SimpleXMLElement($data);

        if ($XMLObject->body->parcel->internalstatus == '0') {
            // The parcel tracking id could not be found.'
            // Thus we only set a statuscode and return 
            // the Array to SimpleQuery() again.
            $DataArray['statuscode'] = 0;
            return $DataArray;
        } else {
            foreach ($XMLObject->body->parcel->children() as $child) {
                if ($child->getName() == 'event') { 
                    array_push(
                        $DataArray['events'], array(
                            'date'        => (int)    $child->date,
                            'time'        => (string)    $child->time,
                            'id'          => (string)    $child->id,
                            'type'        => (string) $child->type,
                            'location'    => (string) $child->location,
                            'code'        => (int)    $child->code,
                            'description' => (string) $child->description,
                        )
                    );
                } elseif ($child->getName() == 'extraservice') {
                    continue;
                } else {
                    $DataArray[(string) $child->getName()] = dom_import_simplexml(
                        $child
                    )->textContent;
                }
            }
        }


        return $DataArray;
    }

    /**
     * Called when http object is finished working
     * and prints result to channel
     *
     * @param object $http Object with http data.
     * @param object $page Object with the return data.
     * @param array  $vars Array with extra stuff
     *
     * @return none
     */
    public function simpleQuery($http, $page, $vars)
    {
        extract($vars);

        $ParcelArray = $this->_xmlToArray($page->data);
        echo $page->data;

        $event = end($ParcelArray['events']);
        switch ((int) $ParcelArray['statuscode']) {
        case 7:
            // Package retrieved from post office
            $date = DateTime::createFromFormat(
                'Ymd', 
                $event['date']
            );
            $time = DateTime::createFromFormat(
                'Hi', 
                $event['time']
            );
            $this->parent->privmsg(
                $replyto, 
                sprintf(
                    "Ditt paket från %s hämtades ut %s klockan %s.", 
                    $ParcelArray['customername'], 
                    $date->format('Y-m-d'), 
                    $time->format('H:i')
                )
            );
            break;
        case 6:
            // Package has arrived at post office. Has not been picked up yet.
            if ($event['location'] == 'Posten') {
                $this->parent->privmsg(
                    $replyto,
                    sprintf(
                        "Ditt paket från %s finns att hämta på %s. Vikt: %s kg.",
                        $ParcelArray['customername'],
                        $ParcelArray['events'][count(
                            $ParcelArray['events']
                        )-2]['location'],
                        $ParcelArray['actualweight']
                    )
                );
            }
            break;
        case 5:
            $parent->privmsg(
                $replyto,
                sprintf(
                    'Ditt paket från %s är under transport, '.
                    'nuvarande position: %s. Vikt: %s kg.',
                    $ParcelArray['customername'],
                    $event['location'],
                    $ParcelArray['actualweight']
                )
            );
            break;

        case 3:
            $parent->privmsg(
                $replyto,
                sprintf(
                    'Ditt paket från %s är under transport, '.
                    'nuvarande position: %s. Vikt: %s kg.',
                    $ParcelArray['customername'],
                    $event['location'],
                    $ParcelArray['actualweight']
                )
            );
            break;
        case 0:
            // Package has not yet been sent, there could be some info in here.
            if ($event['location'] == 'Posten') {
                $parent->privmsg(
                    $replyto, 
                    sprintf(
                        'Paket från %s: %s.',
                        $ParcelArray['customername'],
                        $event['description']
                    )
                );
            }
            break;
        }
    }

}
