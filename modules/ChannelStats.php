<?php

/* MYSQL INFO */

/*

CREATE TABLE IF NOT EXISTS `neotor_channelstats_channel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `channel` varchar(60) NOT NULL,
  `active` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `added` (`added`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `neotor_channelstats_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(50) DEFAULT NULL,
  `added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nickname` varchar(60) NOT NULL,
  `words` int(11) DEFAULT '0',
  `lines` int(11) DEFAULT '0',
  `smiles` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `added` (`added`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

*/

/**
 * Handles Channel Stats for neotor.
 *
 * PHP version 5
 *
 * @category  IrcStats
 * @package   Modules
 * @author    Christian Carlsson <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles Channel Stats for neotor.
 *
 * PHP version 5
 *
 * @category  IrcStats
 * @package   Modules
 * @author    Christian Carlsson <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class ChannelStats implements Module
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
     * Holds the settings for this module
     *
     * @var object
     * @access protected
     */

        protected $settings;

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
        
        	mysql_select_db(CONFIG_mysql_database, $this->db);

                $parent->attach('Stats_'.$this->id, Array($this,'stats'), Array('PRIVMSG'), '/^!stats (?P<search>.*)$/i');
		$parent->attach('StatsRecord_'.$this->id, Array($this,'StatsRecord'), Array('PRIVMSG'));

		$this->StatsUpdate();

        }

    /**
     * Query Handeler
     *
     * @param array  $query   Data
     *
     * @return none
     */

        public function query($query) {
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
                                logWrite(L_ERROR, "[CHANNELSTATS] Query Failed (".mysql_errno($this->db).' '.mysql_error($this->db)."): $query\n");
                }

                return $q;
        }

    /**
     * Query Escape Handeler
     *
     * @param array  $str   Data
     *
     * @return none
     */

        function escape($str) {

                if (!isset($this->db) || !@mysql_real_escape_string($str, $this->db)) {
                        $this->db = mysql_connect(CONFIG_mysql_server, CONFIG_mysql_username, CONFIG_mysql_password);
                        mysql_select_db(CONFIG_mysql_database, $this->db);
                }

                return mysql_real_escape_string($str, $this->db);
        }

    /**
     * Stats Handeler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function stats($parent, $data, $extra)
        {
                extract($extra);

                list ($match, $text) = explode(" ", $regexp['search'], 2);


                  if (!$match && !$text) { $this->StatsShow($channel,$nickname); }

                  if ($match && !$text) { $this->StatsHelp($parent,$extra,$match); }


                  if ($match && !$text)
                  {
                        switch($match) {
                                case 'settings':
                                        $this->StatsHelp($parent,$extra,$match);
                                        break;
                                case 'help':
                                        $this->StatsHelp($parent,$extra,$match);
                                        break;
                        }
                  }

                  if ($match && $text)
                  {
                        switch($match) {
                                case 'settings':
                                        $this->StatsSettings($parent,$extra,$match,$text);
                                        break;
                        }
                  }

        }

    /**
     * Stats Help
     *
     * @param object $parent The object that's calling the module
     * @param array  $extra   Data
     * @param object $match  Extra data
     *
     * @return none
     */

        public function StatsHelp($parent, $extra, $match) {

                extract($extra);

                        switch($match) {
                                case 'settings':
                                        $this->parent->privmsg($replyto, "SETTINGS      Activate / Deactivate ChannelStats");
                                        $this->parent->privmsg($replyto, "--------------------------------");
                                        break;
                                default:
                                        $this->parent->privmsg($replyto, "Help Commands (settings)");
                                        break;
                        }

        }


    /**
     * Stats Settings
     *
     * @param object $parent The object that's calling the module
     * @param array  $extra  Extra data such as regexps
     * @param object $match  Extra data
     * @param object $text  Extra data
     *
     * @return none
     */

        public function StatsSettings($parent, $extra, $match, $text) {

                extract($extra);

		$nick = $extra['nick'];
                $channel = $extra['target'];
                        switch($text) {
                                case 'activate':
					if ($parent->chan($target)->hasmode('o', $nick)) {

						$q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."channelstats_channel` WHERE channel = '$channel'");
						$r = mysql_result($q, 0);

						if($r<1) {
							$this->parent->privmsg($replyto, "[CHANNELSTATS: Generating information]");

					                $this->query(sprintf("
					                        INSERT INTO `".CONFIG_mysql_prefix."channelstats_channel`
					                                (`channel`, `active`)
					                        VALUES
					                                ('%s', '%d')
					                ",
					                        $channel,
					                        1
					                ));

                                                        $this->parent->privmsg($replyto, "[CHANNELSTATS: Information Stored, Stats Active]");
							$this->StatsUpdate();
						}

                                                if($r>=1) {

                                                $q2 = $this->query("SELECT active FROM `".CONFIG_mysql_prefix."channelstats_channel` WHERE channel = '$channel'");
                                                $r2 = mysql_result($q, 0);

							if($r2>=1) {
	                                                        $this->parent->privmsg($replyto, "[CHANNELSTATS: Channel is already active]");
							} else {
			                                                $a = $this->query("UPDATE `".CONFIG_mysql_prefix."channelstats_channel` SET active=1 WHERE channel = '$channel'");
	                                                                $this->parent->privmsg($replyto, "[CHANNELSTATS: Channel is now active]");
									$this->settings[$target]["active"] = 1;
							}

						}
					} else {
                                                        $this->parent->privmsg($replyto, "[CHANNELSTATS: You are not an channel operator]");
                                        }
                                        break;
                                case 'deactivate':
                                        if ($parent->chan($target)->hasmode('o', $nick)) {

                                                $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."channelstats_channel` WHERE channel = '$channel'");
                                                $r = mysql_result($q, 0);

                                                if($r<1) {
                                                        $this->parent->privmsg($replyto, "[CHANNELSTATS: This channel has no records]");
						}
                                                if($r>=1) {
                                                                        $a = $this->query("UPDATE `".CONFIG_mysql_prefix."channelstats_channel` SET active=0 WHERE channel = '$channel'");
                                                                        $this->parent->privmsg($replyto, "[CHANNELSTATS: Channel is now deactivated]");
									$this->settings[$target]["active"] = 0;
						}
					}
                                        break;
                                default:
                                        $this->parent->privmsg($replyto, "FAKK YOOO!!!");
                                        break;
                        }

        }


    /**
     * Stats Recorder
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function StatsRecord($parent, $data, $extra)
        {
                extract($extra);

                        $nick = $extra['nick'];
			$private = $extra['private'];
			$target = $extra['target'];
                        $msg = $extra['msg'];

		if ($msg == "!stats") { $this->StatsShow($parent,$data,$extra,$target,$nick); }

		if (($private==0 && isset($target)))
			{
			$smiles = '0';
			$smiles_array = array(
			          ":)",
			          ":D",
			          ":P",
			          "(:"
			);
			foreach ($smiles_array as $i => $value)
				{
				$pos = strpos($msg, $value);
					if ($pos === false) {
						$smiles;
					} else {
						$smiles++;
					}
				}
			$words = str_word_count($msg, 0);

			$usercheck = $this->StatsUserCheck($target,$nick,$words,1,$smiles);

			if($this->settings[$target]["active"] == 1)
			{
				if(isset($usercheck))
				{
					$this->StatsUpdateUser($target,$nick,$words,$smiles);
				} else {
					print("NO\n");
				}

				print("$words - 1 - $smiles");
			} else {
				print("NO\n");
			}

/*                        $this->parent->privmsg($replyto, "FAKK YOOO!!!");*/
			}
        }

    /**
     * Stats Array Update
     *
     * @return none
     */

       public function StatsUpdate()
        {

                $q = $this->query("SELECT channel,active FROM `".CONFIG_mysql_prefix."channelstats_channel` ORDER by id ASC");
                while ($r = mysql_fetch_array($q)) {
                        $this->settings[$r['channel']] = $r;
                }

	}

    /**
     * Stats User Check
     *
     * @param string $channel   channel
     * @param string $nickname  nickname
     * @param int    $words     words
     * @param int    $smiles    smiles
     *
     * @return none
     */

       public function StatsUserCheck($channel, $nickname, $words, $smiles)
        {

		$q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."channelstats_users` WHERE channel = '$channel' AND nickname = '$nickname'");
		$r = mysql_result($q, 0);

		if($r == 1)
		{
			return true;
		} else {
			$this->StatsAddUser($channel, $nickname, $words, 1, $smiles);
		}

        }

    /**
     * Stats Add User
     *
     * @param string $channel   channel
     * @param string $nickname  nickname
     * @param int    $words     words
     * @param int    $smiles    smiles
     *
     * @return none
     */

       public function StatsAddUser($channel, $nickname, $words, $smiles)
        {

	           $this->query(sprintf("
	    		INSERT INTO `".CONFIG_mysql_prefix."channelstats_users`
			(`channel`, `nickname`, `words`, `lines`, `smiles`)
			    VALUES
		            ('%s', '%s', '%d', '%d', '%d')
		           ",
			    $channel,
			    $nickname,
			    $words,
			    1,
			    $smiles
	           ));

        }

    /**
     * Stats Add User
     *
     * @param string $channel   channel
     * @param string $nickname  nickname
     * @param int    $words     words
     * @param int    $smiles    smiles
     *
     * @return none
     */

       public function StatsUpdateUser($channel, $nickname, $words, $smiles)
        {

		$update = $this->query("UPDATE `".CONFIG_mysql_prefix."channelstats_users` SET words=words+$words, `lines`=`lines`+1, smiles=smiles+$smiles WHERE channel = '$channel' AND nickname = '$nickname'");
	}


    /**
     * Stats Show
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     * @param string $channel   channel
     * @param string $nickname  nickname
     *
     * @return none
     */

       public function StatsShow($parent, $data, $extra, $channel, $nickname)
        {
               extract($extra);

                $q = $this->query("SELECT `words`,`lines`,`smiles` FROM `".CONFIG_mysql_prefix."channelstats_users` WHERE channel = '$channel' AND nickname = '$nickname'");
                while ($r = mysql_fetch_array($q)) {
                        $w = $r["words"];
			$l = $r["lines"];
			$s = $r["smiles"];
			$this->parent->privmsg($replyto, "[Stats for $nickname] [Words: $w Lines: $l Smiles: $s]");
                }

        }

}
