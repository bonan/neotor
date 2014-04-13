<?php

/**
 * Class SmsService
 */
class SmsService {

    /**
     * @var SmsService
     */
    private static $instance;
    /**
     * @var Resource
     */
    protected $db;

    /**
     * @var SMS[]
     */
    protected $network = Array();

    /**
     * @var int
     */
    protected $lastsms = 0;

    /**
     *
     */
    private function __construct() {
        Timer::add2('smsservice', 1, Array($this, 'newSms'));
    }

    /**
     * @param $query
     * @return resource
     */
    private function query($query) {
        if (!isset($this->db) || !mysql_ping($this->db)) {
            $this->db = mysql_connect(CONFIG_mysql_server, CONFIG_mysql_username, CONFIG_mysql_password);
            mysql_select_db(CONFIG_mysql_database, $this->db);
        }
        if (!$q = mysql_query($query, $this->db)) {
            if (mysql_errno($this->db) == 2006) {
                mysql_close($this->db);
                $this->db = mysql_connect(CONFIG_mysql_server, CONFIG_mysql_username, CONFIG_mysql_password);
                mysql_select_db(CONFIG_mysql_database, $this->db);
                if ($q = mysql_query($query, $this->db)) {
                    $f = true;
                }
            }
            if (empty($f))
                logWrite(L_ERROR, "[SMS] Query Failed (".mysql_errno($this->db).' '.mysql_error($this->db)."): $query\n");
        }

        return $q;
    }

    /**
     * @param string $str
     * @return string
     */
    private function escape($str) {

        if (!isset($this->db) || !@mysql_real_escape_string($str, $this->db)) {
            $this->db = mysql_connect(CONFIG_mysql_server, CONFIG_mysql_username, CONFIG_mysql_password);
            mysql_select_db(CONFIG_mysql_database, $this->db);
        }

        return mysql_real_escape_string($str, $this->db);
    }

    /**
     * @return SmsService
     */
    public static function getInstance() {
        if (!isset(self::$instance) || !is_object(self::$instance)) {
            self::$instance = new SmsService();
        }

        return self::$instance;
    }


    /**
     * @param $name
     * @param SMS $obj
     */
    public function addNetwork($name, SMS $obj) {
        $this->network[strtolower($name)] = $obj;
    }

    /**
     * @param $number
     * @param $text
     * @param int $dreport
     * @return bool
     */
    public function sendSms($number, $text, $dreport = 0) {
        if (preg_match(SMS::$number, $number) == 0)
            return false;

        if (time()-$this->lastsms < 8)
            return false;
        $this->lastsms = time();

        $this->query(sprintf(
            "INSERT INTO `outbox` (`number`, `text`, `dreport`) VALUES ('%s', '%s', %d)",
            $this->escape($number),
            $this->escape($text),
            $dreport == 0 ? 0 : 1
        ));

        return true;

    }

    /**
     * @param $nick
     * @param $channel
     * @param $network
     * @return bool
     */
    public function getNumber($nick, $channel, $network) {
        $sql = sprintf("
            SELECT
              number
            FROM smsUsers
            WHERE nick = '%s'
            AND channel = '%s'
            AND network = '%s'
            AND (confirm IS NULL OR confirm = '')
            ", $this->escape($nick), $this->escape($channel), $this->escape($network));

        $q = $this->query($sql);

        if ($r = mysql_fetch_assoc($q)) {
            return $r['number'];
        }

        return false;

    }

    /**
     * @return resource
     */
    public function newSms() {
        $q = $this->query("SELECT 'inbox' AS tblname,id,number,text,insertdate FROM inbox WHERE processed = 0 UNION SELECT 'multipartinbox' as tblname,id,number,text,insertdate FROM multipartinbox WHERE processed = 0");

        $id = Array(
            'inbox' => Array(),
            'multipartinbox' => Array()
        );
        while($r = mysql_fetch_object($q)) {

            logWrite(L_DEBUG, "[SMS] New SMS from $r->number: $r->text");

            $number = preg_replace('/^(\+|00)46/', '0', $r->number);

            if (preg_match(SMS::$number, $number) == 0) {
                if (isset($this->network[SMS::$unknownTarget[0]])) {
                    $this->network[SMS::$unknownTarget[0]]->unknownMessage($r->number, $r->text);
                    $id[$r->tblname][]= $r->id;
                }
                continue;
            }

            $user = $this->getUserByNumber($number);

            if (count($user) == 0) {
                if (isset($this->network[SMS::$unknownTarget[0]])) {
                    $this->network[SMS::$unknownTarget[0]]->unknownMessage($r->number, $r->text);
                    $id[$r->tblname][] = $r->id;
                }
                continue;
            }

            $ch = $r->text;
            $text = '';

            if (strpos($r->text, ' ') !== false)
                list ($ch, $text) = explode(' ', $r->text, 2);


            $fromNick = '';
            $toNetwork = '';
            $toChannel = '';
            $msg = $r->text;

            foreach($user as $u) {
                if (empty($fromNick)) {
                    $fromNick = $u['nick'];
                    $toChannel = $u['channel'];
                    $toNetwork = $u['network'];
                }

                $matchChan = strtolower(ltrim($u['channel'], '#'));
                $matchWord = strtolower(ltrim($ch, '#'));

                if (strlen($matchWord)>0 && substr($matchChan, 0, strlen($matchWord)) == $matchWord) {
                    $fromNick = $u['nick'];
                    $toChannel = $u['channel'];
                    $toNetwork = $u['network'];
                    $msg = $text;
                    break;
                }
            }

            if (isset($this->network[strtolower($toNetwork)])) {
                if ($this->network[strtolower($toNetwork)]->newSms($fromNick, $toChannel, $msg)) {
                    $this->insertHistory($fromNick, '', $toChannel, $toNetwork, $number, 0);
                }
                $id[$r->tblname][] = $r->id;

            } else {
                logWrite(L_DEBUG, "[SMS] New SMS for $toNetwork/$toChannel: Network not found");
            }

        }

        foreach($id as $table => $ids) {
            if (count($ids) > 0) {
                $this->query("UPDATE $table SET processed=1 WHERE id IN (".implode(',',$ids).")");
            }
        }

        Timer::add2('smsservice', 2, Array($this, 'newSms'));
    }

    /**
     * @param $number
     * @return array
     */
    public function getUserByNumber($number) {
        $q = $this->query(sprintf("
            SELECT
                u.id,
                u.nick,
                u.network,
                u.channel,
                h.id AS 'historyId'
            FROM smsUsers u
            LEFT JOIN smsHistory h ON
                h.toNick = u.nick AND
                h.network = u.network AND
                h.channel = u.channel
            WHERE
                u.number IN ('%s', '%s')
                AND (u.confirm IS NULL OR u.confirm = '')
            ORDER BY
                historyId DESC",
            $this->escape($number),
            $this->escape('+46'.substr($number,1))
        ));
        $ret = Array();
        while ($r = mysql_fetch_assoc($q)) {
            $ret[]=$r;
        }

        return $ret;
    }

    /**
     * @param $nick
     * @param $channel
     * @param $network
     * @param $number
     * @param string $confirm
     */
    public function regUser($nick, $channel, $network, $number, $confirm = '') {

        $this->query(sprintf(
            "DELETE FROM `smsUsers` WHERE `nick`='%s' AND `channel`='%s' AND `network`='%s'",
            $this->escape($nick),
            $this->escape($channel),
            $this->escape($network)
        ));


        $this->query(sprintf(
            "INSERT INTO `smsUsers`
            (`nick`, `channel`, `network`, `number`, `confirm`)
            VALUES ('%s', '%s', '%s', '%s', '%s')",
            $this->escape($nick),
            $this->escape($channel),
            $this->escape($network),
            $this->escape($number),
            $this->escape($confirm)
        ));

    }

    /**
     * @param $nick
     * @param $code
     * @return bool
     */
    public function verifyUser($nick, $code) {
        $q = $this->query(sprintf(
            "SELECT * FROM `smsUsers` WHERE `nick` = '%s' AND `confirm` = '%s'",
            $this->escape($nick),
            $this->escape($code)
        ));

        if ($r = mysql_fetch_object($q)) {
            $this->query("UPDATE `smsUsers` SET `confirm` = '' WHERE `id` = '{$r->id}'");
            return true;
        }
        return false;
    }

    /**
     * @param $fromNick
     * @param $toNick
     * @param $channel
     * @param $network
     * @param $number
     * @param $outgoing
     */
    public function insertHistory($fromNick, $toNick, $channel, $network, $number, $outgoing) {
        $this->query(sprintf("
            INSERT INTO smsHistory
              (fromNick, toNick, channel, network, number, outgoing, timestamp, delivered)
            VALUES
              ('%s', '%s', '%s', '%s', '%s', '%s', NOW(), 0)",
            $this->escape($fromNick),
            $this->escape($toNick),
            $this->escape($channel),
            $this->escape($network),
            $this->escape($number),
            $this->escape($outgoing)
        ));
    }

    /**
     * @param $network string Network name
     * @param $channel string Channel name
     * @return Array list of users
     */
    public function listUsers($network, $channel) {
        $q = $this->query(sprintf("
            SELECT nick FROM `smsUsers` WHERE 
                `network` = '%s' AND 
                `channel` = '%s' AND 
                (`confirm` IS NULL OR `confirm` = '')
            ",
            $this->escape($network),
            $this->escape($channel)
        ));

        $nicks = Array();
        while ($r = mysql_fetch_object($q))
            if (!empty($r->nick))
                $nicks[] = $r->nick;
        
        return $nicks;
    }

}
