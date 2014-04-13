<?php

/**
 * Handles Proxy/Socket Scanning for neotor.
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   Modules
 * @author    Christian Carlsson <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles Proxy/Socket Scanning for neotor.
 *
 * PHP version 5
 *
 * @category  IRC
 * @package   Modules
 * @author    Christian Carlsson <oldmagic@zenet.org>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class Scanner implements Module
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
     * Holds the dnsbl lookups for this module
     *
     * @var array
     * @access protected
     */

        protected $dnsbl_lookup=array(
				"ircbl.ahbl.org",
				"cbl.abuseat.org",
				"tor.dnsbl.sectoor.de",
				"rbl.efnet.org"
		  );

    /**
     * Holds the ports to be scanned for this module
     *
     * @var array
     * @access protected
     */

        protected $scan_ports=array(
                                "80",
                                "3128",
                                "8080",
                                "63000",
				"8000",
				"808",
				"6588",
				"4480",
				"65506"
                  );

    /**
     * The counter of this module
     *
     * @var object
     * @access protected
     */

        protected $count=array(
                           "ircbl.ahbl.org" => "0",
                           "cbl.abuseat.org" => "0",
                           "tor.dnsbl.sectoor.de" => "0",
                           "rbl.efnet.org" => "0",
                  );

    /**
     * The total counter of this module
     *
     * @var object
     * @access protected
     */

        protected $totalcount=array(
                           "ircbl.ahbl.org" => "0",
                           "cbl.abuseat.org" => "0",
                           "tor.dnsbl.sectoor.de" => "0",
                           "rbl.efnet.org" => "0",
                  );


    /**
     * The proxy check of this module
     *
     * @var object
     * @access protected
     */

        protected $isproxy = 0;

    /**
     * The proxy check of this module
     *
     * @var object
     * @access protected
     */

        protected $listed = 0;


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

                $parent->attach('notice'.$this->id, Array($this,'_notice'), Array('NOTICE'));
		$parent->attach('Stats_'.$this->id, Array($this,'Stats'), Array('PRIVMSG'));
        }

    /**
     * Scan Handeler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function _notice($parent, $data, $extra)
        {
                extract($extra);

		list ($test, $test2) = explode("@", $extra['msg'], 2);

		if (preg_match("/\bconnecting\b/i", $test)) { 
			$target = str_replace(")", "", $test2);
			$target_ip = gethostbyname($target);
			$this->dnsbllookup($target_ip);
//			$this->checkopenports($target_ip);
		}

		if ($extra["msg"] == "BOTMOTD File not found") {
                        $this->parent->privmsg("NickServ :identify *****");
                        $this->parent->raw("OPER **** ****");
                        $this->parent->raw("MODE ProxyScan +apWs cefFkGhjmnNopqsSv");
                        $this->parent->raw("JOIN #services");
		}
        }

       public function dnsbllookup($ip)
        {

		if ($ip) {
		  $reverse_ip=implode(".",array_reverse(explode(".",$ip)));
		  foreach($this->dnsbl_lookup as $host){
		    ++$this->totalcount[$host];
		    if(checkdnsrr($reverse_ip.".".$host.".","A")){
		      ++$this->count[$host];
	              $this->listed = 1;
		    }
		  }
		}
		if ($this->listed == 1) {
			$this->parent->privmsg("#services", "IP DNSBL Targeted: $ip\n");
			$this->parent->privmsg("OperServ", "AKILL ADD +1h *@$ip [Exp/Proxy] Open proxies are not allowed on ZEnet. See http://zenet.org/kline/ for more information.");
		}
		$this->listed = 0;
	}


       public function checkopenports($ip)
        {

		$timeout = 2;
		$flag = 0;

		foreach($this->scan_ports as $port) {
			$fp = @fsockopen($ip,$port,$errno,$errstr,$timeout);

			if (!$fp) {
				return false; 
			}
                        if ($fp) {
				$flag = 1;
				fwrite($fp,"CONNECT irc.zenet.org:6667\n");
				fwrite($fp,"NICK proxycheck\n");
				fwrite($fp,"USER proxycheck proxycheck proxycheck :proxycheck\n");
			}

			if ($flag == 1) {
				$this->isproxy = 1;
	                        $this->parent->privmsg("#oldmagic", "$ip / $port / $this->isproxy");
			}
                        fclose($fp);
		}

	}


    /**
     * Stats Holder
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

       public function Stats($parent, $data, $extra)
        {
                extract($extra);

                $nick = $extra['nick'];
		$target = $extra['target'];
		$msg = $extra['msg'];

		if ($msg == "!dnsbl-stats") {
                  $this->parent->privmsg($target, "[STATS]");
		  foreach ($this->count as $dnsbl => $stats) {
                     $this->parent->privmsg($target, "$dnsbl -> $stats");
		  }
		}
	}

}
