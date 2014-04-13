<?php
/**
 * Birthday module
 * 
 * PHP version 5
 * 
 * @category  IrcBirthday
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 1970 - 2012, 2014
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */

/**
 * Birthday module
 * 
 * PHP version 5
 * 
 * @category  IrcBirthday
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 1970 - 2012, 2014
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */
class Birthday implements Module
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

        /** 
          * Someone with more regex skills please enhance 
          * the regex matches 
          */        
        $parent->attach(
            'bd_'.$this->id, 
            Array($this,'fetchData'), 
            Array('PRIVMSG'), '/^!bd(?:\s+(?P<nick>\S+))?\s*$/i'
        );
        
        $parent->attach(
            'bdset_'.$this->id, 
            Array($this,'takeRegistration'), 
            Array('PRIVMSG'), '/^!bdset(?:\s+(?P<date>\S+))?\s*$/i'
        );
        
        $parent->attach(
            'bdstats_'.$this->id, 
            Array($this,'fetchStats'), 
            Array('PRIVMSG'), '/^!bdstats$/i'
        );
    }

    /**
     * Fetches users birthday data and outputs it
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function fetchData($parent, $data, $extra)
    {
        extract($extra);
        if (!isset($regexp['nick'])) {
            $output = $this->fetchUserInfo($nick);
        } else {
            $output = $this->fetchUserInfo($regexp['nick']);
        }
        if ($output == false && isset($regexp['nick'])) {
            $output = sprintf(
                "User \"%s\" does not exist in the database",
                $regexp['nick']
            );
        } elseif ($output == false && !isset($regexp['nick'])) {
            $this->takeRegistration($parent, $data, $extra);
        }
        $this->parent->privmsg($replyto, $output);
    }
    
    /**
     * Take registration from users who want to be in the database 
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function takeRegistration($parent, $data, $extra) 
    {
        extract($extra);
        if ($regexp[0] == '!bd') {
            $output = sprintf(
                'User %s is not registered, '.
                'please use !bdset YYYYMMDD to set your birthday.',
                $nick
            );
        } elseif (isset($regexp['date'])) {
            $birth = array(
                'year'  => substr($regexp['date'], 0, 4),
                'month' => substr($regexp['date'], 4, 2),
                'day'   => substr($regexp['date'], 6, 2)
            );
            if (($birth['year'] >= date('Y')) 
                || ($birth['year'] < 1920)
                || (!checkdate($birth['month'], $birth['day'], $birth['year']))
            ) {
                $output = 'Invalid date, please use format YYYYMMDD';
            } else {
				$db = mysql_connect(
					CONFIG_mysql_server, 
					CONFIG_mysql_username, 
					CONFIG_mysql_password
				);
				
				mysql_select_db(CONFIG_mysql_database, $db);
			
                $q = mysql_query(
                    sprintf(
                        "SELECT `birth` FROM `neotor_bd` WHERE `nick` = '%s'",
                        $nick
                    )
                );
                $numrow = mysql_num_rows($q);
                if ($numrow >= 1) {
                    $q = mysql_query(
                        sprintf(
                            "UPDATE `neotor_bd` SET `birth` = '%d-%d-%d'".
                            " WHERE `nick` = '%s'",
                            mysql_real_escape_string($birth['year']),
                            mysql_real_escape_string($birth['month']),
                            mysql_real_escape_string($birth['day']),
                            mysql_real_escape_string($nick)
                        )
                    );
                    if ($q) {
                        $output = 'Updated birthday of '.$nick.' in database';
                    }
                } elseif ($numrow == 0) {
                    $q = mysql_query(
                        sprintf(
                            "INSERT INTO `neotor_bd` (`id`, `nick`, `birth`)".
                            " VALUES (NULL, '%s', '%d-%d-%d')",
                            mysql_real_escape_string($nick),
                            mysql_real_escape_string($birth['year']),
                            mysql_real_escape_string($birth['month']),
                            mysql_real_escape_string($birth['day'])
                        )
                    );
                    if ($q) {
                        $output = $nick.' added to database';
                    }
                }
				
				mysql_close($db);
            }
        } else {
            $output = sprintf(
                '%s: Please use !bdset YYYYMMDD to set your birthday.',
                $nick
            );
        }
        $this->parent->privmsg($replyto, $output);
    }
    
    /**
     * The function that fetches the users information
     * 
     * @param string $nick Nick of user you want to get birthday from
     * 
     * @return string String with users birthday or bootlean false
     *                if not exists
     */
    protected function fetchUserInfo($nick)
    {
		$db = mysql_connect(
					CONFIG_mysql_server, 
					CONFIG_mysql_username, 
					CONFIG_mysql_password
		);
				
		mysql_select_db(CONFIG_mysql_database, $db);
	
        $nick = mysql_real_escape_string($nick);
        if (!$q = mysql_query(
            sprintf(
                "SELECT `birth` FROM `neotor_bd`".
                " WHERE `nick` = '%s'",
                $nick
            )
        )) {
			mysql_close($db);
            return 'Error';
        } 
        if (mysql_num_rows($q) >= 1) {
            $row = mysql_fetch_assoc($q);
            $data = $row['birth'];
            $data = $this->getBirthday($data, $nick);
			mysql_close($db);
            return $data;
        } else {
			mysql_close($db);
            return false;
        }
    }
    
    /**
     * This is where the birthday is formatted and outputted
     * 
     * @param string $born YYYY-MM-DD formatted string taken from database
     * @param string $nick Nick of the user you are getting the birthday from
     * 
     * @return string Outputs weeks, hours, nick, new age, 
     *                date of birthday, days left
     */
    protected function getBirthday($born, $nick)
    {
        // Take out year, month and day from $born variable
        $born = array(
            'year'  => substr($born, 0, 4),
            'month' => substr($born, 5, 2),
            'day'   => substr($born, 8, 2)
        );
        $today      = new DateTime();
        $birthday   = new DateTime(
            date('Y').'-'.$born['month'].
            '-'.$born['day'].' 00:00:00'
        );
        $age        = $this->getAge($born['year']); 
        if ($today->format('U') > $birthday->format('U')) {
            $birthday->add(new DateInterval('P1Y'));
        } elseif ($today->format('Y-m-d') == $birthday->format('Y-m-d')) {
            return sprintf(
                '%s has birthday TODAY! Congratz :) %s is now %d years old.',
                $nick,
                $nick,
                $age
            ); 
        }
        $diff       = $today->diff($birthday);
        $time_diff  = $birthday->format('U') - $today->format('U');
        $weeks_left = floor($time_diff / 604800);
        $days_left  = $diff->d;
        $hours_left = $diff->h;
        if ($days_left % 5 != 0) {
            $left = $weeks_left.' weeks, '.
            $days_left.' days and '.$hours_left.' hours';
        } elseif ($hours_left == 0) {
            $left = $weeks_left.' weeks';
        } else {
            $left = $weeks_left.' weeks and '.$hours_left.' hours';
        }
        $ageafter   = $this->getAge($born['year']) + 1;
        $ordinal    = $this->getOrdinal($ageafter);
        $date       = $born['day'].' '.
                        date(
                            'M', mktime(
                                0, 0, 0,
                                $born['month'], $born['day'],
                                date('Y') + 1
                            )
                        );
        $total_days_left = $diff->days + 1;
        return sprintf(
            "%s left until %s's %s%s birthday. (%s, %s days)",
            $left,
            $nick,
            $ageafter,
            $ordinal,
            $date,
            $total_days_left
        );
    }
    
    /**
     * Get current age from DATE
     * 
     * @param string $born String containing a date (YYYY-MM-DD)
     * 
     * @return string Current age
     */
    protected function getAge($born) 
    {
        // Take out year, month and day from $born variable
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
    
    /**
     * Gets the ordinal of the integer inputted in $num
     * 
     * @param int $num Number you want to get the ordinal from
     * 
     * @return string Returns the ordinal of the int inputted (st,nd,rd,th)
     */
    protected function getOrdinal($num)
    {
        $num   = (int) $num;
        $last  = substr($num, -1, 1);
        switch($last) {
        case "1":
            $ord="st";
            break;
        
        case "2":
            $ord="nd";
            break;
        
        case "3":
            $ord="rd";
            break;
        
        default:
            $ord="th";
        }
        return $ord;
    }
    
    /**
     * Fetching stats from birthday database 
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function fetchStats($parent, $data, $extra) 
    {
        extract($extra);
		$db = mysql_connect(
					CONFIG_mysql_server, 
					CONFIG_mysql_username, 
					CONFIG_mysql_password
		);
				
		mysql_select_db(CONFIG_mysql_database, $db);
		
        // Get total amount of users, youngest, oldest from database
        $stats = mysql_fetch_assoc(
            mysql_query(
                "SELECT COUNT(`id`) AS total,".
                "MIN(`birth`) AS min,".
                "MAX(`birth`) AS max ".
                "FROM `neotor_bd`"
            )
        );
        // Oldest
        $oldest = mysql_fetch_assoc(
            mysql_query(
                sprintf(
                    "SELECT `nick` FROM `neotor_bd`".
                    "WHERE `birth` = '%s'",
                    $stats['min']
                )
            )
        );
        // Youngest
        $youngest = mysql_fetch_assoc(
            mysql_query(
                sprintf(
                    "SELECT `nick` FROM `neotor_bd`".
                    "WHERE `birth` = '%s'",
                    $stats['max']
                )
            )
        );
        // Total and average age
        $totalage = 0;
        $q = mysql_query("SELECT `birth` FROM `neotor_bd");
        while ($rad = mysql_fetch_assoc($q)) {
            $totalage = $totalage + $this->getAge($rad['birth']);
        }
        $stats['oldest']    = $oldest['nick'];
        $stats['youngest']  = $youngest['nick'];
        $stats['totalage']  = $totalage;
        $stats['average']   = $totalage/$stats['total'];
        $output = sprintf(
            "There is a total of %d users in the birthday database. ".
            "%s (%d years) is the oldest user in the channel ".
            "and %s (%d years) is the youngest user in the channel. ".
            "The total age of all users is %d years ".
            "(average age is %d years).",
            $stats['total'],
            $stats['oldest'],
            $this->getAge($stats['min']),
            $stats['youngest'],
            $this->getAge($stats['max']),
            $stats['totalage'],
            $stats['average']
        );
		
		mysql_close($db);
		
        $this->parent->privmsg($replyto, $output);
    }
}     
