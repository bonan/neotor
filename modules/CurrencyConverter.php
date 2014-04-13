<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Handles currency conversions for neotor
 *
 * PHP version 5
 *
 * @category  CurrencyConverter
 * @package   Modules
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @author    Bonan Bonan <bonan@neotor.se>
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */


/**
 * Handles currency conversions for neotor
 *
 * PHP version 5
 *
 * @category  CurrencyConverter
 * @package   Modules
 * @author    NDRS NDRS <ndrsofua@gmail.com>
 * @author    Bonan Bonan <bonan@neotor.se>
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 1970 - 2012
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class CurrencyConverter implements Module
{
    
    // {{{ properties

    /**
     * For rate cacheing
     *
     * @var array
     */
    private static $rateCache = Array();

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

    protected $cache = Array('btc' => Array('obj' => '', 'time' => 0));

    // }}}

    // {{{ setFoo()

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
        $parent->attach(
            'CurrencyConverter_'.$this->id,
            array($this, 'callConverter'),
            array('PRIVMSG'),
            '/^(?P<amount>[\d\.]{1,}'.
            (class_exists('Math') ? '|'.Math::$mathRegexp : '').
            ')\s(?P<FromCurrency>[a-zA-Z]{3,4})\sin\s(?P<ToCurrency>[a-zA-Z]{3,4})$/'
        );
    }

    /**
     * CallConverter
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none 
     */
    public function callConverter($parent, $data, $extra)
    {
        extract($extra);
        if (class_exists('Math')) {
      		try {
                $regexp['amount'] = Math\calc(str_replace(',', '.', $regexp['amount']));
    		} catch(Exception $e) {
    		}
            
        }

        $fromCurrency = strtolower($regexp['FromCurrency']);
        $toCurrency = strtolower($regexp['ToCurrency']);
        $amount = $regexp['amount'];
        $extraData = array_merge(
            $extra,
            Array(
                'parent' => $parent,
                'origFrom' => $fromCurrency,
                'origTo' => $toCurrency,
                'origAmount' => $amount,
                'fromCurrency' => $fromCurrency,
                'toCurrency' => $toCurrency,
                'amount' => $amount
            )
        );
        
        if ($fromCurrency == $toCurrency)
            return;
        
        if ($fromCurrency == "btc") {
			//$this->parent->privmsg($replyto, "API closed down for BTC, pm jny about a new BTC api pls.");
            $this->mtgoxCurrency($extraData);
        } else {
            $this->yahooCurrency($extraData);
        }

    }
	

	
    /**
     * Initiates a mtgox currency conversion
     *
     * @param array  $vars Array with extra stuff
     *
     * @return non
     */
    public function mtgoxCurrency($extra)
    {
        if (($this->cache['btc']['time'] + 120) >= time()) {
        //    return $this->replyMtgox(null,$this->cache['btc']['obj'],$extra);
        }

        $http = new HttpSession('b.epg.io', 80);
        $http->setMaxLength(4096);
        $http->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.152 Safari/537.22');
        $http->getPage(
            array($this, 'replyMtgox'),
            $extra,
            '/sandbox/btce'
        );
    }
     
    /**
     * Initiates a yahoo currency conversion
     *
     * @param array  $vars Array with extra stuff
     *
     * @return non
     */
    public function yahooCurrency($extra)
    {
        if ($extra['toCurrency'] == "btc") {
            $extra['toCurrency'] = 'usd';
        }

        extract($extra);

        list($cr,$cn) = self::_cacheName($fromCurrency,$toCurrency);

        if (isset(self::$rateCache[$cn]) && self::$rateCache[$cn][1] > time()-3600) {
            $exR = self::$rateCache[$cn][0];
            if ($cr) $exR = 1/$exR;

            echo "yahooCurrency [$fromCurrency-$toCurrency] [rate:$exR] [cache:$cn]".PHP_EOL;
            $newamount = $exR * $amount;
            if ($origTo == "btc") {
                $vars["amount"] = $newamount;
                $vars["fromCurrency"] = "usd";
                $vars["toCurrency"] = "btc";
                $this->mtgoxCurrency($vars);
            } else {
                $this->sendReponse($parent, $replyto, $origAmount, $origFrom, $newamount, $origTo);
            }
            return;
        } 


        // http://download.finance.yahoo.com/d/quotes.csv?s=AUDUSD=X&f=l1
        $http = new httpSession('download.finance.yahoo.com');
        $http->setMaxLength(4096);
        $http->getPage(
            array($this, 'replyYahoo'),
            $extra,
            '/d/quotes.csv?s='.
            strtoupper($fromCurrency).
            strtoupper($toCurrency).
            '=X&f=l1'
        );
    }

    /**
     * Prints the converted currency
     *
     * @param object $http Object with http data.
     * @param object $page Object with the return data.
     * @param array  $vars Array with extra stuff
     *
     * @return non
     */

    public function replyYahoo($http, $page, $vars)
    {
        extract($vars);
        
        $exrate = trim($page->data);
        if (!is_numeric($exrate))
            return;
			
		// Not found
		if($exrate == "0.00")
			return;

        $newamount = $exrate * $amount;
        echo "replyYahoo[$amount * $exrate = $newamount]".PHP_EOL;

        list($ca,$cn) = self::_cacheName($fromCurrency,$toCurrency,$exrate);
        self::$rateCache[$cn] = Array($ca,time());

        if ($origTo == "btc") {
            $vars["amount"] = $newamount;
            $vars["fromCurrency"] = "usd";
            $vars["toCurrency"] = "btc";
            $this->mtgoxCurrency($vars);
        } else {
            $this->sendReponse($parent, $replyto, $origAmount, $origFrom, $newamount, $origTo);
        }
    }

    /**
     * Gets the result from mtgox and processes it
     *
     * @param object $http Object with http data.
     * @param object $page Object with the return data.
     * @param array  $vars Array with extra stuff
     *
     * @return non
     */
    public function replyMtgox($http, $page, $vars)
    {
        extract($vars);
        
        if ($page->status != 200)
            echo $page->data;

        if (is_object($http)) {
            $this->cache['btc'] = Array('obj' => $page, 'time' => time());
        }

        $btc = trim($page->data); //substr($page->data, $pos1=strpos($page->data, '{'), strrpos($page->data, '}')-$pos1+1);
		$btc = trim(preg_replace('/\s\s+/', ' ', $btc));
		$btc2 = explode(" ", $btc);

        if(!isset($btc2[1]) || !isset($origFrom)) {
            return;
        }
        $btcRate = $btc2[1];
        
        if ($toCurrency == "btc") {
            $newamount = $amount / $btcRate;
            $this->sendReponse($parent, $replyto, $origAmount, $origFrom, $newamount, $origTo);
        } elseif ($fromCurrency == "btc") {
            $newamount = $amount * $btcRate;
            if ($toCurrency == "usd") {
                $this->sendReponse($parent, $replyto, $origAmount, $origFrom, $newamount, $origTo);
            } else {
                $vars["amount"] = $newamount;
                $vars["fromCurrency"] = "usd";
                $this->yahooCurrency($vars);
            }
        }
    }

    /**
     * Sends the response to the channel
     * 
     * @param object $parent Parent irc object
     * @param string $replyto Channel/Query to reply to
     * @param string $amount The original amount
     * @param string $from The original currency
     * @param string $newamount The calculated amount
     * @param string $to The target currency
     */
    public function sendReponse($parent, $replyto, $amount, $from, $newamount, $to)
    {
        $parent->privmsg(
            $replyto, 
            sprintf(
                "%s %s is %s %s",
                $amount,
                $from, 
                number_format($newamount, ($amount<0.1?8:2), '.', ' '), 
                $to
            )
        );
    }

    /**
     *
     */
    private static function _cacheName($f,$t,$a=null) {
        $F=strtoupper($f);
        $T=strtoupper($t);
        $rev = (strcmp($F,$T) > 0);
        $name = ($rev ? $T.$F : $F.$T);
        if ($a===null)
            return Array($rev,$name);
        return Array(
            ($rev ? 1/$a : $a),
            $name
        );


    }
}
