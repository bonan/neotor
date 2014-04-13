<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handles SVN stuff for neotor.
 *
 * PHP version 5
 *
 * @category  IrcSVN
 * @package   Modules
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

/**
 * Handles SVN stuff for neotor.
 *
 * PHP version 5
 *
 * @category  IrcSVN
 * @package   Modules
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */
class SVNController implements Module
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
     * The db handler of this module
     *
     * @var object
     */

    protected $db;

    /**
     * Holds latest entry of this module
     *
     * @var object
     */

    protected $LatestEntry;

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
            'SVNController_'.uniqid(),
            Array($this,'showTopList'),
            Array('PRIVMSG'),
            '/^!svn\sshow\stoplist$/i'
        );
        $parent->attach(
            'SVNController_'.$this->id,
            Array($this,'showRevision'),
            Array('PRIVMSG'),
            '/^!svn\sshow\srevision\s(?P<revision>[\d]{1,})$/i'
        );

        Timer::add2(
            'SVNController_'.uniqid(), 
            CONFIG_SVNCONTROLLER_INTERVAL, 
            Array($this, 'getNewRevisions')
        );
    }

    /**
     * Shows the top committers for the particular svn repository
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function showTopList($parent, $data, $extra)
    {
        echo "IN showTopList()\n";
        extract($extra);
        mysql_select_db(CONFIG_mysql_database, $this->db);
        $result = mysql_query(
            'SELECT author, count(*) as commits '.
            'FROM svnlog '.
            'GROUP BY author '.
            'ORDER BY commits DESC '.
            'LIMIT 10;'
        );
        $ListNum = 1;
        $parent->privmsg($replyto, "TOP LIST, IN ORDER OF MOST COMMITS");
        while ($row = mysql_fetch_assoc($result)) {
            $parent->privmsg(
                $replyto, 
                sprintf(
                    '%d. %s (%d)', 
                    $ListNum,
                    $row['author'],
                    (int) $row['commits']
                )
            );
            ++$ListNum;
        }
    }

    /**
     * Get's latest revision logged in database
     *
     * @return int The latest (highest) id in database
     */
    private function _getLatestFromDB()
    {
        mysql_select_db(CONFIG_mysql_database, $this->db);
        $result = mysql_query(
            "SELECT revision FROM svnlog ORDER BY revision DESC LIMIT 1;"
        );
        if (mysql_num_rows($result) == 0) {
            return 0;
        } else {
            $LastEntry = mysql_fetch_assoc($result);
            return (int) $LastEntry['revision'];
        }
    }

    /**
     * Shows data about specific revision
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function showRevision($parent, $data, $extra)
    {
        extract($extra);
        mysql_select_db(CONFIG_mysql_database, $this->db);
        $result = mysql_query(
            'SELECT author, commitmessage FROM svnlog WHERE revision='.
            $regexp['revision']
        );
        if (mysql_num_rows($result) == 0) {
            $parent->privmsg(
                CONFIG_SVNCONTROLLER_CHANNEL, 
                sprintf(
                    '[SVN] Sorry, revision %d was not found',
                    (int) $regexp['revision']
                )
            );
        } else {
            $Entry = mysql_fetch_assoc($result); 
            $parent->privmsg(
                CONFIG_SVNCONTROLLER_CHANNEL,
                sprintf(
                    "[SVN] r%d: %s | %s",
                    (int) $regexp['revision'],
                    $Entry['author'],
                    stripslashes($Entry['commitmessage'])
                )
            );
        }
    }

    /**
     * Calls the svn binary and requests all revisions from the repo.
     *
     * @return object Returns object or boolean false
     *
     */
    private function _getAllXMLFromSVN()
    {
        $XMLData = shell_exec('svn log --xml');
        return $this->_makeXMLObject($XMLData);
    }

    /**
     * Makes an object from the XML data. Makes it easier to process.
     *
     * @param string $XMLData A string containing the XML data
     *
     * @return object Returns object or boolean false.
     */
    private function _makeXMLObject($XMLData)
    {
        try {
            $XMLObject = new SimpleXMLElement($XMLData);
            return $XMLObject;
        } catch (Exception $e) {
            echo 'I POOPED MYSELF'."\n";
            return false;
        }
    }

    /**
     * Fixes carriage returns, newlines and double spaces
     *
     * @param string $Text The text that needs to be fixed
     *
     * @return string Returns the processed string.
     */
    private function _fixText($Text)
    {
        $Text = preg_replace("/\r\n/", " ", $Text);
        $Text = preg_replace("/\n/", " ", $Text);
        $Text = preg_replace("/\s\s/", "", $Text);
        $Text = mysql_real_escape_string($Text);

        return $Text;
    }

    /**
     * Inserts data to database.
     *
     * @param object $LogEntry And XML object containing data
     *
     * @return none
     */
    private function _insertToDB($LogEntry)
    {
        mysql_select_db(CONFIG_mysql_database, $this->db);
        mysql_query(
            sprintf(
                'INSERT INTO svnlog '.
                '(revision, author, date, commitmessage) '.
                'VALUES (%d,"%s","%s","%s")',
                $LogEntry->attributes()->revision,
                (string) $LogEntry->author,
                (string) $LogEntry->date,
                $this->_fixText((string) $LogEntry->msg)
            )
        );
    }

    /**
     * Fetches revisions from a specific revision all the way to the newest revision
     *
     * @param int $Revision The revision to begin looking from.
     *
     * @return object An XML object containing all the revisions
     */
    private function _getXMLObjectFromRevision($Revision)
    {
        $XMLData = shell_exec('svn log --xml -r '.$Revision.':HEAD');
        return $this->_makeXMLObject($XMLData);
    }

    /**
     * Checks for new revisions in the repository
     *
     * @return none
     */
    public function getNewRevisions()
    {
        $this->LatestEntry = $this->_getLatestFromDB();

        if ($this->LatestEntry == 0) {
            if ($XMLObject = $this->_getAllXMLFromSVN()) {
                foreach ($XMLObject->children() as $LogEntry) {
                    $LogMessage =  $this->_fixText((string) $LogEntry->msg);
                    $this->_insertToDB($LogEntry);
                    $this->parent->privmsg(
                        CONFIG_SVNCONTROLLER_CHANNEL,
                        sprintf(
                            "[SVN] New commit | r%d | %s | %s", 
                            (int) $LogEntry->attributes()->revision, 
                            (string) $LogEntry->author, 
                            stripslashes($LogMessage)
                        )
                    );
                }
            }
        } else {
            if ($XMLObject = $this->_getXMLObjectFromRevision($this->LatestEntry)) {
                if (count($XMLObject->children()) != 1) {
                    foreach ($XMLObject->children() as $LogEntry) {
                        $LogMessage =  $this->_fixText((string) $LogEntry->msg);
                        if ($LogEntry->attributes()->revision == $this->LatestEntry
                        ) {
                            continue;
                        } else {
                            $this->_insertToDB($LogEntry);
                            $this->parent->privmsg(
                                CONFIG_SVNCONTROLLER_CHANNEL, 
                                sprintf(
                                    "[SVN] New commit | r%d | %s | %s", 
                                    (int) $LogEntry->attributes()->revision, 
                                    (string) $LogEntry->author, 
                                    stripslashes($LogMessage)
                                )
                            );
                        }
                    }
                }
            }
        }
        Timer::add2(
            'SVNController_'.uniqid(), 
            CONFIG_SVNCONTROLLER_INTERVAL, 
            Array($this, 'getNewRevisions')
        );
    }
}
