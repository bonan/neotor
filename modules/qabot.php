<?php
/**
 
 CREATE TABLE `neoQaBot` (`nick` VARCHAR(30), PRIMARY KEY(`nick`), `ident` VARCHAR(15), `host` VARCHAR(63), `realname` VARCHAR(255), `qachan` VARCHAR(100), `controlchan` VARCHAR(100));
 CREATE TABLE `neoQaSetting` (`bot` VARCHAR(30), `setting` VARCHAR(100), PRIMARY KEY(`bot`, `setting`), `value` VARCHAR(255));
 CREATE TABLE `neoQaQuestion` ()
 
 */


require_once('modules/qabot/instance.php');

class qabot extends service {
	
	public static $trigger = "!qa";
	protected $db;
	protected $bots = Array();
	protected $init = false;
	
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
				logWrite(L_ERROR, "[QABOT] Query Failed (".mysql_errno($this->db).' '.mysql_error($this->db)."): $query\n");
		}
		
		return $q;
	}
	function escape($str) {
		
		if (!isset($this->db) || !@mysql_real_escape_string($str, $this->db)) {
			$this->db = mysql_connect(CONFIG_mysql_server, CONFIG_mysql_username, CONFIG_mysql_password);
			mysql_select_db(CONFIG_mysql_database, $this->db);
		}
		
		return mysql_real_escape_string($str, $this->db);
	}
	protected function _init() {
		$this->register(
			'QA',
			'qa',
			'zenet.org',
			'ZEnet Q&A'
		);
		$this->raw('JOIN #opers');
		$this->serverMode('#opers', '+o QA');
		$this->attach('ctcp' . $this->id, Array($this, '_ctcp'), Array('PRIVMSG'), "/^\x01(?P<type>[^\s]+)(?:\s(?P<message>.*))?\x01$/i");
		$this->attach('privmsg' . $this->id, Array($this, '_privmsg'), Array('PRIVMSG'));
		
		if ($this->init == false) {
		
			$this->init = true;
			$q = $this->query("SELECT * FROM `".CONFIG_mysql_prefix."QaBot`");
			
			while ($r = mysql_fetch_assoc($q)) {
				$botnick = $r['nick'];
				$botident = $r['ident'];
				$bothost = $r['host'];
				$botrealname = $r['realname'];
				$qachan = $r['qachan'];
				$controlchan = $r['controlchan'];
				
				$this->bots[$botnick] = new qaInstance($this->parent, Array(), $botnick, $botident, $bothost, $botrealname, $qachan, $controlchan, $this);
				$q2 = $this->query("SELECT * FROM `".CONFIG_mysql_prefix."QaSetting` WHERE `bot` = '".$this->escape($botnick)."'");
				while ($r2 = mysql_fetch_assoc($q2)) {
					$this->bots[$botnick]->set($r2['setting'], $r2['value']);
				}
			}
		}
		
	}
	
	public function _ctcp($parent, $data, $extra) {
		extract($extra);
		
		$type = strtoupper($extra['regexp']['type']);
		$message = (empty($extra['regexp']['message']) ? '' : $extra['regexp']['message']);
		
		switch($type) {
			case 'VERSION':
				$this->raw("NOTICE {$nick} :\x01{$type} QABot v1.0 by <bonan@zenet.org>\x01");
				break;
			case 'PING':
				$this->raw("NOTICE {$nick} :\x01{$type} {$message}\x01");
				break;
		}
	}
	
	public function _privmsg($parent, $data, $extra) {
		extract($extra);
		
		if (false === strpos($msg, ' '))
			$a = Array($msg);
		else
			$a = explode(' ', $msg);
	
		if ($action || substr($msg,0,1) == chr(1))
			return;
		if (!$private) {
			// Check for trigger for non-private
			$t = trim(array_shift($a));
			if (strtolower($t) != strtolower(qabot::$trigger)) {
				return;
			}
		}
		
		$f = '';
		if (count($a) > 0)
			$f = array_shift($a);
		
		$f = strtolower(preg_replace('/[^a-z0-9]/i', '_', $f));
		
		if (method_exists($this, 'msg'.$f)) {
			$f = 'msg'.$f;
		} else {
			$f = 'msgunknown';
		}
		
		$this->$f($extra, $a);
	
	}
	
	protected function msg($extra, $a) {
		// Send help
		extract($extra);
		if ($private) {
			$this->privmsg($replyto, "Usage: /msg ".$this->me()->nick." (list|new|set|del|help)");
		} else {
			$this->privmsg($replyto, "Usage: ".qabot::$trigger." (list|new|set|del|help)");
		}
	}
	protected function msgunknown($extra, $a) {
		// Do nothing
		$this->msg($extra,$a);
	}
	protected function msghelp($extra, $a) {
		extract($extra);
		if (count($a) == 0 || empty($a[0])) {
			$this->privmsg($replyto, "This bot is used to create and manage bots for Q&A Sessions on the ZEnet network");
			$this->privmsg($replyto, "Before creating a bot, you need to establish a Q&A-channel and a control channel");
			$this->privmsg($replyto, "For example: #qa and #qa.admin");
			$this->privmsg($replyto, "Users ask questions either by asking directly in the channel or by /msg:ing the bot");
			$this->privmsg($replyto, "a question. Questions get posted with a numeric identifier to the control channel,");
			$this->privmsg($replyto, "where anyone present can post an answer by prefixing it with the number.");
			$this->privmsg($replyto, "----------- COMMANDS -----------");
			$this->privmsg($replyto, "LIST      Lists active Q&A bots");
			$this->privmsg($replyto, "NEW       Create new Q&A bots");
			$this->privmsg($replyto, "SET       Settings for Q&A bot");
			$this->privmsg($replyto, "DEL       Delete Q&A bot");
			$this->privmsg($replyto, "HELP      Show this help, suffix command for additional help");
			return;
		}
		
		$arg = array_shift($a);
		
		switch(strtolower($arg)) {
			case 'list':
				$this->privmsg($replyto, "LIST      Lists active Q&A bots");
				$this->privmsg($replyto, "--------------------------------");
				$this->privmsg($replyto, "This command takes no arguments");
				break;
			case 'new':
				$this->privmsg($replyto, "NEW       Create new Q&A bots");
				$this->privmsg($replyto, "--------------------------------");
				$this->privmsg($replyto, "Create a new Q&A bot. Syntax:");
				$this->privmsg($replyto, "Syntax: NEW botname chan controlchan [ident [host [realname]]]");
				break;
			case 'set':
				$this->privmsg($replyto, "SET       Settings for Q&A bot");
				$this->privmsg($replyto, "--------------------------------");
				$this->privmsg($replyto, "Update settings for the Q&A bot");
				$this->privmsg($replyto, "Syntax: SET botname [setting [value]]");
				$this->privmsg($replyto, "Use with only botname to get current settings");
				break;
			case 'del':
				$this->privmsg($replyto, "DEL       Delete Q&A bot");
				$this->privmsg($replyto, "--------------------------------");
				$this->privmsg($replyto, "Deletes an Q&A bot");
				$this->privmsg($replyto, "Syntax: DEL botname");
				break;
			default:
				$this->privmsg($replyto, "Unknown help topic");
				break;
		}
		
		
	}
	
	protected function msglist($extra, $a) {
		extract($extra);
		
		if (count($this->bots) == 0) {
			$this->privmsg($replyto, "There are no currently active bots");
			return;
		}
		
		$this->privmsg($replyto, "------- List of bots: -------");
		foreach($this->bots as $bot) {
			$this->privmsg($replyto, str_pad($bot->nick, 20, ' ') . $bot->qachan);
		}
		$this->privmsg($replyto, '-------  End of list  -------');
	}
	protected function msgnew($extra, $a) {
		extract($extra);
		
		if (count($a) < 3) {
			$this->privmsg($replyto, "Usage: NEW botname chan controlchan [ident [host [realname]]]. See HELP NEW for details.");
			return false;
		}
		$botnick = array_shift($a);
		$botident = $botnick;
		$bothost = 'zenet.org';
		$botrealname = 'ZEnet Q&A Bot';
		$qachan = array_shift($a);
		$controlchan = array_shift($a);
		if (count($a) > 0) $botident = array_shift($a);
		if (count($a) > 0) $bothost = array_shift($a);
		if (count($a) > 0) $botrealname = implode(' ', $a);
		
		foreach($this->parent->users() as $user) {
			if (strtolower($user->nick) == strtolower($botnick)) {
				$this->privmsg($replyto, "Nick {$botnick} is already in use");
				return false;
			}
		}
		
		$this->query(sprintf("
			INSERT INTO `".CONFIG_mysql_prefix."QaBot`
				(`nick`, `ident`, `host`, `realname`, `qachan`, `controlchan`)
			VALUES
				('%s', '%s', '%s', '%s', '%s', '%s')
		",
			$this->escape($botnick),
			$this->escape($botident),
			$this->escape($bothost),
			$this->escape($botrealname),
			$this->escape($qachan),
			$this->escape($controlchan)
		));
		
		$this->bots[$botnick] = new qaInstance($this->parent, Array(), $botnick, $botident, $bothost, $botrealname, $qachan, $controlchan, $this);
		
	}
	protected function msgset($extra, $a) {
		extract($extra);
		if (count($a) == 0) {
			return $this->privmsg($replyto, "Usage: SET botname [setting value]");
		}
		$botname = array_shift($a);
		foreach($this->bots as $b) {
			if (strtolower($botname) == strtolower($b->nick)) {
				$bot = $b;
			}
		}
		if (!isset($bot) || !is_object($bot))
			return $this->privmsg($replyto, "Unknown bot, usage: SET botname [setting value]");
			
		if (count($a) > 0) {
			$set = array_shift($a);
			$value = '';
			if (count($a) > 0) {
				$value = implode(' ', $a);
			}
		}
		
		if (empty($set)) {
			$this->privmsg($replyto, "------- List of settings: -------");
			$s = $bot->get();
			foreach($s as $name=>$value) {
				$this->privmsg($replyto, str_pad($name, 28, ' ') . " =    " . $value);
			}
			$this->privmsg($replyto, "-------    End of list    -------");
		} else {
			$s = $bot->get();
			if (isset($s[$set])) {
				$this->query(sprintf("REPLACE INTO `".CONFIG_mysql_prefix."QaSetting` (`bot`, `setting`, `value`) VALUES ('%s', '%s', '%s')",
					$this->escape($bot->nick),
					$this->escape($set),
					$this->escape($value)
				));
				$bot->set($set, $value);
				$this->privmsg($replyto, "$set set to '".$bot->get($set)."'");
			} else {
				$this->privmsg($replyto, "Unknown setting: $set");
			}
		}
		
	}
	protected function msgdel($extra, $a) {
		extract($extra);
		if (count($a) > 0) {
			$botname = array_shift($a);
			foreach($this->bots as $i=>$bot) {
				if (strtolower($bot->nick) == $botname) {
					$this->query(sprintf("DELETE FROM `".CONFIG_mysql_prefix."QaBot` WHERE `nick` = '%s'", $this->escape($bot->nick)));
					$this->query(sprintf("DELETE FROM `".CONFIG_mysql_prefix."QaSetting` WHERE `bot` = '%s'", $this->escape($bot->nick)));
					$bot->delete();
					unset($this->bots[$i]);
					$this->privmsg($replyto, "Bot $botname deleted");
					return;
				}
			}
			$this->privmsg($replyto, "Unknown bot, usage: DEL botname");
			
			
		} else {
			$this->privmsg($replyto, "Usage: DEL botname");
		}
	}
}
