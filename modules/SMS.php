<?php

include_once('sms/SmsService.php');
/**
 * Class SMS
 */
class SMS implements Module {

    /**
     * @var array
     */
    public static $channels = Array(
        "Quakenet" => Array("#g33k.se", "#spelcraft"),
        "ZEnet" => Array("#sverige", "#opers", "#neotor")
    );

    /**
     * @var array
     */
    public static $unknownTarget = Array('Quakenet', 'bonan');

    /**
     * @var string
     */
    public static $number = "/^(07[0236])[0-9]{7}$/";

    /**
     * @var Irc
     */
    protected $parent;

    /**
     * @var SmsService
     */
    protected $service;

    /**
     * @param Irc $parent
     * @param $params
     */
    public function __construct($parent, $params)
    {
        $this->id = uniqid();
        $this->parent = $parent;
        $parent->attach('sms_'.$this->id, Array($this, 'privmsg'), Array("PRIVMSG"), '/^!sms/i');

        $this->service = SmsService::getInstance();
        $this->service->addNetwork($parent->getNetworkName(), $this);

    }

    /**
     * @param $fromNick
     * @param $toChannel
     * @param $text
     * @return boolean
     */
    public function newSms($fromNick, $toChannel, $text) {
        if ($text == 'Failed' || $text == 'Pending') {
            // Do nothing
            return false;
        }

        if ($text == 'Delivered') {
            $this->parent->privmsg($toChannel, "\x0304[SMS]\x03 Leveransrapport: Meddelandet har levererats till $fromNick");
            return false;
        }

        $this->parent->privmsg($toChannel, "\x0304[SMS]\x03 <$fromNick> \x0312".trim($text)."\x03 (svara med !sms $fromNick text)");
        return true;
    }

    /**
     * @param $number
     * @param $message
     */
    public function unknownMessage($number, $message) {
        $this->parent->privmsg(self::$unknownTarget[1], "Nytt SMS från {$number}: " .
            str_replace(Array("\r","\n"), Array("","<LF>"), $message));
    }

    /**
     * @param Irc $parent
     * @param $data
     * @param $extra
     * @return bool
     */
    public function privmsg($parent, $data, $extra) {
        extract($extra);
        $network = $parent->getNetworkName();
        $cmd = '';
        $data = '';
        if (strpos($msg, " ") !== false) {
            list (,$cmd) = explode(' ', trim($msg), 2);
            $cmd = trim($cmd);
            if (strpos($cmd, " ") !== false ) {
                list($cmd,$data) = explode(' ', $cmd, 2);
                $data = trim($data);
            }
        }

        if (substr_count($msg, " ") > 2)
            list(, $cmd, $data) = explode(' ', trim($msg), 3);


        if ($private) {
            if (empty($cmd)) {
                $parent->privmsg($replyto, "Registrera: !sms reg #kanal dittnummer");
                $parent->privmsg($replyto, "Verifiera nummer: !sms verify koden");
                return true;
            }
            $cmd = strtolower($cmd);
            if ($cmd == 'reg' || $cmd == 'register') {
                if (strpos($data, ' ') !== false) {
                    list ($chan, $num) = explode(' ', $data, 2);
                    $chan = trim($chan);
                    $num = trim($num);
                }

                if (!in_array(strtolower($chan), self::$channels[$network])) {
                    $parent->privmsg($replyto, "Ogiltig kanal, har kanalen sms-funktionen aktiverad?");
                    return true;
                }

                if (preg_match(self::$number, $num) == 0) {
                    $parent->privmsg($replyto, "Ogiltigt nummer, ange endast siffror. t.ex: !sms reg $chan 0701234567");
                    return true;
                }

                $confirm = '';
                $t = "abcdefghijklmnopqrstuvwxyz0123456789";
                for ($i=0; $i<6; $i++) $confirm .= substr($t, rand(0,strlen($t)-1), 1);
                if (!$this->service->sendSms($num, "Din verifieringskod är: $confirm - Om du inte vet vad det här är kan du ignorera detta sms")) {
                    $parent->privmsg($replyto, "Kunde inte skicka SMS, prova igen om en stund");
                    return true;
                }
                $this->service->regUser($nick, $chan, $network, $num, $confirm);

                $parent->privmsg($replyto, "Ditt nummer har sparats/uppdaterats för $chan. ".
                    "Ett SMS har skickats för att verifiera ditt nummer, vänligen skriv /msg {$parent->me()->nick} !sms verify koden");
            }
            if ($cmd == 'verify') {
                $code = trim($data);
                if ($this->service->verifyUser($nick, $code)) {
                    $parent->privmsg($replyto, "Ditt nummer har verifierats, du kan nu skicka sms till och ta emot sms från kanalen.");
                } else {
                    $parent->privmsg($replyto, "Koden är ogiltig");
                }
            }

        } else {
            if (!in_array($target, self::$channels[$network]))
                return false;
            
            if (!empty($cmd) && strtolower($cmd) == "list" && empty($data)) {
                $users = $this->service->listUsers($network, $target);
                
                $this->parent->notice($nick, "SMS-Användare i $target: " . implode(', ', $users));

                return true;
            }

            if (empty($cmd) || empty($data)) {
                $parent->privmsg($replyto, "$nick: Syntax: !sms nick meddelande, t.ex: !sms $nick Hej! - !sms list, Lista med användare - För registrering: /msg {$parent->me()->nick} !sms reg {$target} 07xxxxxxxx");
                return true;
            }

            $tonick = $cmd;
            $number = $this->service->getNumber($tonick, $target, $network);
            if ($number === false) {
                $parent->privmsg($replyto, "$nick: $tonick är inte registrerad. /msg {$parent->me()->nick} !sms reg $target nummer");
                return true;
            }

            $smsprefix = "<$nick@$target> ";
            $smstext = $smsprefix.$data;

            if (strlen($smstext) > 160) {
                $parent->privmsg($replyto, "$nick: Ditt sms får max vara ".(160-strlen($smsprefix))." tecken, nuvarande: ".strlen($data)." tecken");
                return true;
            }

            if ($this->service->sendSms($number, $smstext, 1)) {
                $this->service->insertHistory($nick, $tonick, $target, $network, $number, 1);
                $parent->privmsg($replyto, "\x0304[SMS]\x03 Skickar sms till $tonick: \x0312$smstext\x03");
                return true;
            } else {
                $parent->privmsg($replyto, "$nick: Meddelandet kunde inte skickas, prova igen om en stund");
                return true;
            }

        }
    }

}



