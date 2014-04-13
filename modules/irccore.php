<?php

/**
 * irccore
 * 
 * @package   
 * @author 
 * @copyright 
 * @version 2012
 * @access public
 */
class irccore
{

	protected $parent;

	/**
	 * irccore::__construct()
	 * 
	 * @param mixed $parent
	 * @return
	 */
	public function __construct($parent)
	{
		$this->id = uniqid();
		$this->parent = $parent;
		$this->parent->botrawHandler = array($this, 'botraw');
		$this->parent->attach('irccore-ircraw-' . $this->id, array($this, 'ircraw'),
			array(
				'MOTD',
				'STATS',
				'VERSION',
				'RULES',
				'WHOIS'));
	}

	/**
	 * irccore::ircraw()
	 * 
	 * @param mixed $parent
	 * @param mixed $data
	 * @param mixed $extra
	 * @return
	 */
	public function ircraw($parent, $data, $extra)
	{
		switch ($extra['func']) {
			case 'MOTD':
				$this->_motd($extra);
				break;
			case 'WHOIS':
				$this->_whois($extra);
				break;

		}
	}

	/**
	 * irccore::botraw()
	 * 
	 * @param mixed $data
	 * @param integer $silent
	 * @param integer $priority
	 * @return
	 */
	public function botraw($data, $silent = 0, $priority = 3)
	{

		$extra = $this->parent->parseCommand($data);
		extract($extra);

		$h = false;

		switch ($func) {
			case 'MOTD':
				if (!$target || strtolower($target) == strtolower($this->parent->me()->nick)) {
					$this->_motd($extra);
					$h = true;
				}
				break;

			case 'WHOIS':
				if (!$target || strtolower($target) == strtolower($this->parent->me()->nick) ||
					$this->parent->user($target) && 
					strtolower($this->parent->user($target)->server) == strtolower($this->parent->me()->nick)) {
					$this->_whois($extra);
					$h = true;
				}
				break;

			case 'STATS': // uUf
			
				$cmd = $target;
				$target = (isset($args[0]) ? $args[0] : $msg );
			
				if (!$target || strtolower($target) == strtolower($this->parent->me()->nick)) {
					$h = true;
					$this->_stats($extra);
				}
				break;

		}


	}

	/**
	 * irccore::_numeric()
	 * 
	 * @param mixed $target
	 * @param mixed $numeric
	 * @param mixed $msg
	 * @param bool $raw
	 * @return
	 */
	protected function _numeric($target, $numeric, $params, $msg = null, $exec = true)
	{
		$f = ":{$this->parent->me()->nick} $numeric $target " . (is_array($params) ?
			implode(' ', $params) . ' ' : (empty($params) ? '' : $params . ' ')) . ($msg !== null ?
			':' . $msg : '');
		if ($exec) {
			if (strtolower($this->parent->user($target)->server) == strtolower($this->
				parent->me()->nick)) {
				$this->parent->gotRaw($f);
			} else {
				$this->parent->raw($f);
			}
			return true;
		}

		return $f;
	}

	protected function _getChanList($nick, $who = '', $all = false)
	{
		$user = $this->parent->user($nick);
		$chans = $user->channels;

		$chanStr = '';
		foreach ($chans as $c) {
			$p = '';
			$m = $c->getmode($nick);
			foreach ($this->parent->modes() as $pp) {
				if (in_array($pp, $m)) {
					$p = $pp->prefix;
					if (!empty($p))
						break;
				}
			}
			if (!$c->hasmode('', 'ps') || (!empty($who) && $this->parent->user($who)->ison($c->
				name))) {
				$chanStr .= $p . $c->name . ' ';
			} elseif ($all) {
				$chanStr .= '?' . $p . $c->name . ' ';
			}
		}
		$chanStr = trim($chanStr);
		return $chanStr;
	}

	protected function _whois($extra)
	{
		extract($extra);

		$user = $this->parent->user($target);
		if (!$user) {
			$this->_numeric($nick, 401, $target, "No such nick/channel");
			$this->_numeric($nick, 318, $target, "End of /WHOIS list.");
			return;
		}

		$this->_numeric($nick, 311, array(
			$user->nick,
			$user->ident,
			$user->host,
			'*'), $user->realname);
		$chanStr = $this->_getChanList($user->nick, $nick);
		if (!empty($chanStr))
			$this->_numeric($nick, 319, array($user->nick), $chanStr);
		$this->_numeric($nick, 312, array($user->nick, $user->server), $this->parent->
			server($user->server)->desc);
		$this->_numeric($nick, 318, array($user->nick), 'End of /WHOIS list.');

	}

	/**
	 * irccore::_motd()
	 * 
	 * @param mixed $parent
	 * @param mixed $data
	 * @param mixed $extra
	 * @return void
	 */
	protected function _motd($extra)
	{
		extract($extra);
		$motd = $this->_getMotd();

		$this->_numeric($nick, 375, '', "- {$this->parent->me()->nick} Message of the Day -");
		foreach ($motd as $line) {
			$this->_numeric($nick, 372, '', '- ' . $line);
		}
		$this->_numeric($nick, 376, '', "End of /MOTD command.");
	}

	/**
	 * irccore::_getMotd()
	 * 
	 * @return array Array of lines in motd file.
	 */
	protected function _getMotd()
	{
		$motd = <<< EOF
NeoServ services 1.0

By: bonan <bonan@zenet.org>
EOF;

		// Load file here.
		if (defined('CONFIG_core_motdfile')) {
			$motd = file_get_contents(CONFIG_core_motdfile);
		}

		return explode("\n", $motd);

	}

}
