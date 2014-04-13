<?php

/**
 * Handles IdleRPG for neotor.
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   Modules
 * @author    oldmagic <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles IdleRPG for neotor.
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   Modules
 * @author    oldmagic <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class IdleRPG implements Module
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
     * Holds the quests for this module
     *
     * @var object
     * @access protected
     */

        protected $quests;

    /**
     * Holds the items for this module
     *
     * @var object
     * @access protected
     */

        protected $items;


    /**
     * Holds the battles for this module
     *
     * @var object
     * @access protected
     */

        protected $battles;

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

		$parent->attach('Commands_'.$this->id, Array($this,'commands'), Array('PRIVMSG'));
                $parent->attach('Raws_'.$this->id, Array($this,'raws'), Array('JOIN','PART','QUIT','MODE'));

		Timer::add2(
		   'Idlecheck_'.uniqid(),
		    CONFIG_idlerpg_interval,
		    Array($this, 'checkIdle')
		);

		$this->Load();

	   print("Loaded IdleRPG Module\n");

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
                                logWrite(L_ERROR, "[IDLERPG] Query Failed (".mysql_errno($this->db).' '.mysql_error($this->db)."): $query\n");
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
     * Load Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function Load()
       {
	/**
	 * Load all quests
	*/
	$q = $this->query("SELECT * FROM `".CONFIG_mysql_prefix."quests`");
	while ($row = mysql_fetch_array($q)) {
	 $id    = $row["id"];
	 $info  = $row["info"];
	 $text  = $row["text"];
	 $level = $row["level"];
	 $req   = $row["req"];
	 $dur   = $row["time"];
	 $this->quests[$id]["info"]    = $info;
	 $this->quests[$id]["text"]    = $text;
	 $this->quests[$id]["level"]   = $level;
	 $this->quests[$id]["req"]     = $req;
	 $this->quests[$id]["time"]    = $dur;
         $this->quests[$id]["active"]  = 0;
	}
       }


    /**
     * Commands Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function commands($parent, $data, $extra)
       {
          extract($extra);
          $msg = $extra['msg'];
	  $msg_handler = explode(" ", $msg);
	  $cmd = $msg_handler[0];
	if ($extra['private'] == 1) {
          switch($cmd) {
            case 'REGISTER':
             $this->PrivateHandler($parent,$data,$extra,'REGISTER');
             break;
            case 'LOGIN':
             $this->PrivateHandler($parent,$data,$extra,'LOGIN');
             break;
            case 'LOGOUT':
             $this->PrivateHandler($parent,$data,$extra,'LOGOUT');
             break;
            case 'NEWPASS':
             $this->PrivateHandler($parent,$data,$extra,'NEWPASS');
             break;
            case 'REMOVEME':
             $this->PrivateHandler($parent,$data,$extra,'REMOVEME');
             break;
          }
	}

	/**
	 * Checks for penalties
	*/
        else {
print_r($extra);
	   if ($this->checkLogin($extra['nick']) == 1) {
	    if ($this->settings[$nick]["admin"] == 1) {
	     if ($msg == "!quests") {
              $this->parent->privmsg($replyto, "List All Quests");
	      foreach ($this->quests as $quest) {
               $this->parent->privmsg(
                CONFIG_idlerpg_channel,
                 sprintf(
                 "[%s] %s",
                 $quest["info"],
                 $quest["text"]
                )
               );
               $this->parent->privmsg(
                CONFIG_idlerpg_channel,
                 sprintf(
                 "[%s] Level: %d Player Req: %d Time: %d",
                 $quest["info"],
                 $quest["level"],
                 $quest["req"],
                 $quest["time"]
                )
               );
               $this->parent->privmsg($replyto, "----------------------------");
              }
	     }
	    }
	   }
	  $this->Penalties($parent,$data,$extra);
	}


       }


    /**
     * Raws Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function raws($parent, $data, $extra)
       {
          extract($extra);
          $cmd = $extra['func'];
          switch($cmd) {
            case 'JOIN':
             $this->Penalties($parent,$data,$extra);
             break;
            case 'PART':
             $this->Penalties($parent,$data,$extra);
             break;
            case 'MODE':
             $this->Penalties($parent,$data,$extra);
             break;
            case 'QUIT':
             $this->Penalties($parent,$data,$extra);
             break;
            case 'REMOVEME':
             $this->PrivateHandler($parent,$data,$extra,'REMOVEME');
             break;
          }
       }


    /**
     * Private Messages Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function PrivateHandler($parent, $data, $extra, $cmd)
       {
          extract($extra);
	  $nick = $extra['nick'];
          $msg = $extra['msg'];
          $msg_handler = explode(" ", $msg);
	  $cmd = strtoupper($cmd);

	  if ($cmd == "REGISTER")
	  {
            $nickname = $msg_handler[1];
            $password = $msg_handler[2];
            $charclass = $msg_handler[3];
	    $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nickname'");
	    $r = mysql_result($q, 0);
	    if($r != 0) {
	     $this->parent->privmsg($nick, "[ERROR: This nickname is already registerd]");
	    }
	    else {
	     $this->query(sprintf("
	      INSERT INTO `".CONFIG_mysql_prefix."idlerpg`
	       (`nickname`, `password`, `charclass`, `level`)
	      VALUES
	       ('%s', '%s', '%s', '%d')
	     ",
	      $this->escape($nickname),
	      $this->escape($password),
	      $this->escape($charclass),
	      $this->escape(1)
	     ));
	    }
          }

          if ($cmd == "LOGIN")
          {
            $nickname = $msg_handler[1];
            $password = $msg_handler[2];
            $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
            $r = mysql_result($q, 0);
            if($r == 0) {
             $this->parent->privmsg($nick, "[ERROR: This nickname is NOT registerd]");
            }
            if($this->settings[$nick]["login"] == 1) {
             $this->parent->privmsg($nick, "[ERROR: Already logged in]");
            }
            else {
             $q = $this->query("SELECT * FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
	      while ($row = mysql_fetch_array($q)) {
		if($row["password"] != $password) {
		 $this->parent->privmsg($nick, "[ERROR: Wrong password]");
		 return;
                }
		$this->settings[$nick]["login"] = 1;
                $this->settings[$nick]["class"] = $row["charclass"];
                $this->settings[$nick]["level"] = $row["level"];
                $this->settings[$nick]["time"] = $row["time"];
                $this->settings[$nick]["admin"] = $row["admin"];
                $this->settings[$nick]["quest"] = 0;
                $this->settings[$nick]["battle"] = 1;
	        $this->settings[$nick]["battletime"] = 60;
		if ($nick == "oldmagic") { $this->battles[$nickname]["opponent"] = "dragon"; }
                if ($nick == "dragon") { $this->battles[$nickname]["opponent"] = "oldmagic"; }
		$this->parent->privmsg($nick, "[LOGIN: Welcome Back $nick, The ".$row['charclass']."]");
		$this->parent->raw("MODE ".CONFIG_idlerpg_channel." +v $nick");
	      }
            }
          }

          if ($cmd == "LOGOUT")
          {
            $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
            $r = mysql_result($q, 0);
            if($r == 0) {
             $this->parent->privmsg($nick, "[ERROR: This nickname is NOT registerd]");
            }
            if($this->settings[$nick]["login"] == 0) {
             $this->parent->privmsg($nick, "[ERROR: Already logged out]");
            }
            else {
             $this->settings[$nick]["login"] = 0;
             $this->parent->privmsg($nick, "[LOGOUT: See you soon $nick");
             $this->parent->raw("MODE ".CONFIG_idlerpg_channel." -v $nick");
            }
          }

          if ($cmd == "NEWPASS")
          {
            $password = $this->escape($msg_handler[1]);
            $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
            $r = mysql_result($q, 0);
            if($r == 0) {
             $this->parent->privmsg($nick, "[ERROR: This nickname is NOT registerd]");
            }
            if($this->settings[$nick]["login"] == 0) {
             $this->parent->privmsg($nick, "[ERROR: You need to login in order to execute this command]");
            }
            else {
             $q = $this->query("UPDATE `".CONFIG_mysql_prefix."idlerpg` SET password = '$password' WHERE nickname = '$nick'");
             $this->parent->privmsg($nick, "[NEWPASS: You have changed your password]");
            }
          }

          if ($cmd == "REMOVEME")
          {
            $password = $msg_handler[1];
            $q = $this->query("SELECT count(id) FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
            $r = mysql_result($q, 0);
            if($r == 0) {
             $this->parent->privmsg($nick, "[ERROR: This nickname is NOT registerd]");
            }
            if($this->settings[$nick]["login"] == 0) {
             $this->parent->privmsg($nick, "[ERROR: You need to login in order to execute this command]");
            }
            else {
             $q = $this->query("SELECT * FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
              while ($row = mysql_fetch_array($q)) {
                if($row["password"] != $password) {
                 $this->parent->privmsg($nick, "[ERROR: Wrong password]");
                 return;
                }

                $this->settings[$nick]["login"] = 0;
                $this->query("DELETE FROM `".CONFIG_mysql_prefix."idlerpg` WHERE nickname = '$nick'");
                $this->parent->raw("MODE ".CONFIG_idlerpg_channel." -v $nick");
              }
            }
          }


       }


    /**
     * Penalties Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function Penalties($parent, $data, $extra)
       {
          extract($extra);
          $nick = $extra['nick'];
          $cmd = $extra['func'];

	   if($this->settings[$nick]["login"] == 1) {
	    print("$nick is logged in, make him/her suffer! CMD: $cmd\n");
            switch($cmd) {
             case 'PART':
              $this->Pmath($nick,$this->settings[$nick]["time"],$this->settings[$nick]["level"], 'PART');
              $this->parent->notice($nick, "[Penalties: You have been rewared with more time till next level. ACTION: PART][TIME: ".$this->settings[$nick]['time']."]");
              break;
             case 'MODE':
              $this->Pmath($nick,$this->settings[$nick]["time"],$this->settings[$nick]["level"], 'MODE');
              $this->parent->notice($nick, "[Penalties: You have been rewared with more time till next level. ACTION: MODE]");
              break;
             case 'QUIT':
              $this->Pmath($nick,$this->settings[$nick]["time"],$this->settings[$nick]["level"], 'QUIT');
              break;
             case 'PRIVMSG':
              $this->Pmath($nick,$this->settings[$nick]["time"],$this->settings[$nick]["level"], 'PRIVMSG');
              break;
            }
           }
       }

    /**
     * Penalties Math Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function Pmath($nick, $time = 0, $level, $action)
       {
	switch($action)
	{
	 case 'PRIVMSG':
	 $add = 10*pow(1.16, $level);
	 break;
	 case 'PART':
         $add = 200*pow(1.16, $level);
	 break;
	 case 'MODE':
         $add = 250*pow(1.16, $level);
	 break;
	 case 'QUIT':
	 $add = 20*pow(1.16, $level);
	 break;
	 case 'default':
	 $add = 600*pow(1.16, $level);
	 break;
	}
	$this->settings[$nick]["time"] = $time + $add;
        $this->query("UPDATE `".CONFIG_mysql_prefix."idlerpg` set `time` = ".$this->settings[$nick]['time']." WHERE nickname = '$nick'");
       }

    /**
     * Checks the idletime and much more
     *
     * @return none
     */
       public function checkIdle()
       {
	$minus = 3;
	$q = $this->query("SELECT `nickname` FROM `".CONFIG_mysql_prefix."idlerpg`");
         while ($row = mysql_fetch_array($q)) {
	  $nickname = $row["nickname"];

	/**
	 * See if the nickname is logged in
	 */
	  $login = $this->checkLogin($nickname);

	  if ($login == 1) {
        /**
         * Get the level for nickname
         */
           $level = $this->settings[$nickname]["level"];
	/**
	 * Get the idletime for nickname
	 */
	   $idletime = $this->settings[$nickname]["time"];
	   $this->settings[$nickname]["time"] = $idletime - $minus;
	   printf("Idletime: %s\n", $this->settings[$nickname]["time"]);
        /**
         * Check if the nickname has any active quest
         */
          $quest = $this->checkQuest($nickname);

        /**
         * Check if the nickname has any active battles
         */

          $battle = $this->checkBattle($nickname);

	/**
	 * Here comes the magic code
	 */
          if ($idletime <= 0) {
	   $this->settings[$nickname]["level"] = $level + 1;
	   $this->Pmath($nickname,$this->settings[$nickname]["time"],$this->settings[$nickname]["level"]);
           $this->parent->privmsg(
             CONFIG_idlerpg_channel,
              sprintf(
               "[LEVELUP] %s The %s has reached level %d",
               $nickname,
               $this->settings[$nickname]["class"],
               $this->settings[$nickname]["level"]
              )
           );
           $this->query("UPDATE `".CONFIG_mysql_prefix."idlerpg` SET `time` = ".$this->settings[$nickname]['time']." WHERE nickname = '$nickname'");
           $this->query("UPDATE `".CONFIG_mysql_prefix."idlerpg` SET `level` = ".$this->settings[$nickname]['level']." WHERE nickname = '$nickname'");
	  }
	  else {
	   printf("%s - %s - %d - %d\n", $nickname, $this->settings[$nickname]["class"], $this->settings[$nickname]["level"], $this->settings[$nickname]["time"]);
	  }
	  }

	 }

        Timer::add2(
         'Idlecheck_'.uniqid(),
         CONFIG_idlerpg_interval,
         Array($this, 'checkIdle')
       );

       }

    /**
     * Check if logged in
     *
     * @return true or false
     */

       public function checkLogin($nickname)
       {
	if ($this->settings[$nickname]["login"] == 1) {
	 return true;
	}
	else {
	 return false;
	}
       }

    /**
     * Check active quest
     *
     * @return true or false
     */

       public function checkQuest($nickname)
       {
       }

    /**
     * Handle battles
     *
     * @return true or false
     */

       public function checkBattle($nickname)
       {
	print("Checks for Battles\n");
	if ($this->settings[$nickname]["battle"] == 1) {
	 if ($this->battleTime($nickname) == 1) {
	  /**
	   * Check the battle attacks
	   */
	  $this->battleAttack($nickname, $this->battles[$nickname]["opponent"]);
	 }
	}
       }

    /**
     * Handle battle attacks
     *
     * @return none
     */

       public function battleAttack($nickname, $opponent)
       {
	/**
	 * Get the player levels and damage
	 * Call the battle calc
	 */
	$player1 = $this->settings[$nickname]["level"];
	$player2 = $this->settings[$opponent]["level"];
        $pl1_dmg = $player1 * 2;
        $pl2_dmg = $player2 * 2;
	$this->battleCalc($nickname, $opponent, $pl1_dmg, $pl2_dmg);
       }

    /**
     * Handle battle attack calc
     *
     * @return none
     */

       public function battleCalc($player1, $player2, $attack1, $attack2)
       {
	$rand = rand(1,2);
	if ($attack1 > $attack2) {
         $this->battleMessage($player1, $player2, $attack1, $attack2, 0);
	} elseif ($attack1 < $attack2) {
         $this->battleMessage($player2, $player1, $attack2, $attack1, 0);

	 /**
	  * Make a random winner
	  */
	} else {
	 if ($rand == 1) {
          $this->battleMessage($player1, $player2, $attack1, $attack2, 1);
	 } else {
	  $this->battleMessage($player2, $player1, $attack2, $attack1, 0);
	 }
	}
       }

    /**
     * Handle battle messages
     *
     * @return none
     */

       public function battleMessage($player1, $player2, $attack1, $attack2, $random = 0)
       {
        $this->settings[$player1]["battletime"] = 60;
        $this->settings[$player2]["battletime"] = 60;
	if ($random == 1) {
          $this->parent->privmsg(
           CONFIG_idlerpg_channel,
            sprintf(
             "[Random Battle] %s challenged %s in a duel and lost.",
             $player1,
             $player2
            )
          );
          $this->parent->privmsg(
           CONFIG_idlerpg_channel,
            sprintf(
             "[Random Battle] %s used a total damage of %d against %s's total damage of %d.",
             $player1,
             $attack1,
             $player2,
             $attack2
            )
          );
	} else {
          $this->parent->privmsg(
           CONFIG_idlerpg_channel,
            sprintf(
             "[Battle] %s challenged %s in a duel and lost.",
             $player1,
             $player2
            )
          );
          $this->parent->privmsg(
           CONFIG_idlerpg_channel,
            sprintf(
             "[Battle] %s used a total damage of %d against %s's total damage of %d.",
             $player1,
             $attack1,
             $player2,
             $attack2
            )
          );
	}
       }

    /**
     * Handle battle times
     *
     * @return true or false
     */

       public function battleTime($nickname)
       {
        if ($this->settings[$nickname]["battletime"] >= 1) {
	 $this->settings[$nickname]["battletime"] = $this->settings[$nickname]["battletime"] - 3;
	 return false;
        }
	else {
	 return true;
	}
       }


}
