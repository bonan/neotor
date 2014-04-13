<?php
require_once('source/service.php');
class qaInstance extends service {
	
	protected $nick;
	protected $ident;
	protected $host;
	protected $realname;
	protected $qachan;
	protected $controlchan;
	protected $settings;
	protected $questions = Array();
	protected $lastq = Array();
	protected $qcount = 0;
	protected $qa;
	
	public function __construct($parent, $modules, $nick, $ident, $host, $realname, $qachan, $controlchan, $qa) {
		parent::__construct($parent, $modules);
		
		// Since we already should be connected when this is invoked.
		$this->parent->detach('service-connected-'.$this->id);
		$this->qa = $qa;
		$this->nick = $nick;
		$this->ident = $ident;
		$this->host = $host;
		$this->realname = $realname;
		$this->qachan = $qachan;
		$this->controlchan = $controlchan;
		$this->settings = Array(
			'channel_questions' => 0,
			'channel_questions_prefix' => '',
			'active' => 1,
			'id_start' => 1000,
			'show_qnick' => 1,
			'show_anick' => 1,
			'pernick_timeout' => 120,
		);
		$this->_init();
	}
	
	protected function _init() {
		$this->register(
			$this->nick,
			$this->ident,
			$this->host,
			$this->realname
		);
		
		$this->raw("JOIN $this->qachan");
		$this->raw("JOIN $this->controlchan");
		$this->serverMode($this->qachan, "+o {$this->nick}");
		$this->serverMode($this->controlchan, "+o {$this->nick}");
		$this->attach('privmsg-qa-'.$this->id, Array($this,'_privmsg'), Array('PRIVMSG'));
	}
	
	public function delete() {
		$this->raw("QUIT :Bot deleted");
		$this->parent->detach($this->id);
		$this->parent->detach('service-connect-'.$this->id);
		$this->parent->detach('service-connected-'.$this->id);
	}
	public function __get($var) {
		if (isset($this->$var))
			return $this->$var;
	}
	
	public function _privmsg($parent, $data, $extra) {
		extract($extra);
		
		if (substr($msg, 0, 1) == chr(1))
			return; // CTCP or ACTION
		
		if ($private) {
			$ret = $this->parseQuestion($nick, $msg);
			$this->privmsg($replyto, $ret);
		} elseif (strtolower($target) == strtolower($this->qachan)) {
			if ($this->get('channel_questions') &&
				strtolower(substr($msg,0,strlen($this->get('channel_questions_prefix')))) == strtolower($this->get('channel_questions_prefix'))) {
				$ret = $this->parseQuestion($nick, substr($msg, strlen($this->get('channel_questions_prefix'))));
				$this->notice($nick, $ret);
			}
		} elseif (strtolower($target) == strtolower($this->controlchan)) {
			if (strpos($msg, ' ') !== false) {
				list($id, $answer) = explode(' ', $msg, 2);
				if (preg_match('/^[0-9]+$/', $id)) {
					$this->parseAnswer($nick, $id, $answer);
				}
			}	
		}
	}
	
	protected function parseQuestion($nick, $msg) {
		$lq = isset($this->lastq[strtolower($nick)])?$this->lastq[strtolower($nick)]:0;
		
		if (!$this->parent->chan($this->qachan)->ison($nick)) {
			return "You must be on {$this->qachan} to ask questions";
		} elseif (!$this->get('active')) {
			return "We're currently not taking any further questions, please wait until a mod says it's time for questions.";
		} elseif (time() - $lq < $this->get('pernick_timeout')) {
			$t = $this->get('pernick_timeout') - (time() - $lq);
			return "You must wait " . $t . " second".($t>1?'s':'')." before asking another question";
		} else {
			$id = $this->newQuestion($nick, $msg);
			return "Thank you! Your question has been added to the queue with id: " . $id;
		}
	}
	
	protected function parseAnswer($nick, $id, $answer) {
		
		if (!isset($this->questions[$id])) {
			return $this->notice($nick, "Question with id $id was not found");
		}
		
		list($qnick, $question, $anick, ) = $this->questions[$id];
		
		if (!empty($anick)) {
			return $this->notice($nick, "Question $id was already answered by $anick");
		}
		
		$this->questions[$id] = Array($qnick, $question, $nick, $answer);
		$this->privmsg($this->qachan, "[Question]".($this->get('show_qnick')?" <$qnick>":"")." $question");
		$this->privmsg($this->qachan, "[Answer  ]".($this->get('show_anick')?" <$nick>":"")." $answer");
		
	}
	
	public function get($setting='') {
		if (empty($setting))
			return $this->settings;
		
		if (isset($this->settings[$setting]))
			return $this->settings[$setting];
		return false;
	}
	public function set($setting, $value='') {
		$this->settings[$setting] = $value;
	}
	
	protected function newQuestion($nick, $question) {
		$this->lastq[strtolower($nick)] = time();
		$this->qcount++;
		$add = $this->get('id_start');
		while(isset($this->questions[$this->qcount+$add]))
			$this->qcount++;
		$id = $this->qcount + $add;
		$this->questions[$id] = Array($nick, $question, '', '');
		$this->privmsg($this->controlchan, "[$id] <$nick> $question");
		return $id;
	}
}
