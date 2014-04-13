<?php
/**
 * Special User Days module
 * 
 * PHP version 5
 * 
 * @category  IrcSpecialDays
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @copyright 1970 - 2012
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */

/**
 * Special User Days module
 * 
 * PHP version 5
 * 
 * @category  IrcSpecialDays
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @copyright 1970 - 2012
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */
class SpecialUserDays implements Module
{
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
        
        $this->db = mysql_connect(
            CONFIG_mysql_server, 
            CONFIG_mysql_username, 
            CONFIG_mysql_password
        );
        
        mysql_select_db(CONFIG_mysql_database, $this->db);
        
        /** 
          * Someone with more regex skills please enhance 
          * the regex matches 
          */        
        $parent->attach(
            'suds_'.$this->id, 
            Array($this,'getSystemet'), 
            Array('PRIVMSG'), '/^!systemet(?:\s+(?P<nick>\S+))?\s*$/i'
        );
        $parent->attach(
            'sudp_'.$this->id, 
            Array($this,'getPuben'), 
            Array('PRIVMSG'), '/^!puben(?:\s+(?P<nick>\S+))?\s*$/i'
        );
    }

    /**
     * Get time until the user can go to "Systembolaget" 
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function getSystemet($parent, $data, $extra)
    {
        extract($extra);
        if (!isset($regexp['nick'])) {
            $query = mysql_query(
                sprintf(
                    "SELECT `nick`,`birth` ".
                    "FROM `neotor_bd` ".
                    "WHERE `nick` = '%s'",
                    $nick
                )
            );
        } else {
            $query = mysql_query(
                sprintf(
                    "SELECT `nick`,`birth` ".
                    "FROM `neotor_bd` ".
                    "WHERE `nick` = '%s'",
                    $regexp['nick']
                )
            );
        }
        if (mysql_num_rows($query) >= 1) {
            $user = mysql_fetch_assoc($query);
            if (!empty($user)) {
                $output = $this->getOutput($user, 20, 'Systembolaget');
            }
        } else { 
            $outputnick = (isset($regexp['nick']) ? $regexp['nick'] : $nick);
            $output = sprintf(
                "User \"%s\" does not exist in database.",
                $outputnick
            );
        }
        $this->parent->privmsg($replyto, $output);
    }
    
    /**
     * Get time until the user can go to "Puben" 
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function getPuben($parent, $data, $extra)
    {
        extract($extra);
        if (!isset($regexp['nick'])) {
            $query = mysql_query(
                sprintf(
                    "SELECT `nick`,`birth` ".
                    "FROM `neotor_bd` ".
                    "WHERE `nick` = '%s'",
                    $nick
                )
            );
        } else {
            $query = mysql_query(
                sprintf(
                    "SELECT `nick`,`birth` ".
                    "FROM `neotor_bd` ".
                    "WHERE `nick` = '%s'",
                    $regexp['nick']
                )
            );
        }
        if (mysql_num_rows($query) >= 1) {
            $user = mysql_fetch_assoc($query);
            if (!empty($user)) {
                $output = $this->getOutput($user, 18, 'Puben');
            }
        } else {
            $outputnick = (isset($regexp['nick']) ? $regexp['nick'] : $nick);
            $output = sprintf(
                "User \"%s\" does not exist in database.",
                $outputnick
            );
        }
        $this->parent->privmsg($replyto, $output);
    }
    
    /**
     * Get output to both events, an attempt at DRY
     * 
     * @param array  $user       Array from database
     * @param int    $ageAllowed Age when the user is allowed to go $where
     * @param string $where      Where the user can or waits to go
     * 
     * @return string Output with either the correct countdown string
     * or tell the user that the user already can go to $where.
     */
    function getOutput ($user, $ageAllowed, $where)
    {
        $curAge = $this->getCurrAge($user['birth']);
        if ($curAge < 18) {
            $today      = new DateTime();
            $turn_year  = substr($user['birth'], 0, 4) + $ageAllowed;
            $turn       = new DateTime($turn_year.substr($user['birth'], 4));
            $diff       = $today->diff($turn);
            $time_diff  = $turn->format('U') - $today->format('U');
            $years_left = $diff->y;
            if ($years_left >= 1) {
                $weeks_left = floor(
                    ($time_diff - (31536000 * $years_left)) / 604800
                );
            } else {
                $weeks_left = floor($time_diff / 604800);
            }
            $days_left  = $diff->d;
            $hours_left = $diff->h;
            if ($years_left == 0) {
                $output = sprintf(
                    "%d weeks, %d days and %d hours left until ".
                    "%s can go to \"%s\".",
                    $weeks_left,
                    $days_left,
                    $hours_left,
                    $user['nick'],
                    $where
                );
            } elseif ($years_left >= 1) {
                $output = sprintf(
                    "%d years, %d weeks, %d days and %d hours left ".
                    "until %s can go to \"%s\".",
                    $years_left,
                    $weeks_left,
                    $days_left,
                    $hours_left,
                    $user['nick'],
                    $where
                );
            }
        } else {
            $output = sprintf("%s can already go to \"%s\".", $user['nick'], $where);
        }
        return $output;
    }
    
    /**
     * Gets the users age from when the user is born.
     * 
     * @param string $born String in date format (YYYY-MM-DD). 
     * 
     * @return int Age of the user currently
     */
    protected function getCurrAge($born) 
    {
        $born = array(
            'year'  => substr($born, 0, 4),
            'month' => substr($born, 5, 2),
            'day'   => substr($born, 8, 2)
        );
        $today = new DateTime();
        $age = $today->format('Y') - $born['year'];
        if (   $today->format('m') < $born['month'] 
            || $today->format('m') == $born['month'] 
            && $today->format('d') <= $born['day']
        ) {
            $age = $age - 1;   
        }
        return $age;
    }
}