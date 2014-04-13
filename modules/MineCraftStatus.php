<?php

class MineCraftStatus implements Module
{

    protected $id;
    protected $parent;

    public function __construct($parent, $params) {
        $this->id = uniqid();
        $this->parent = $parent;
        $parent->attach('MCStatus_'.$this->id, Array($this,'checkStatus'), Array('PRIVMSG'), '/^!mc (?P<server>[---a-z_\.0-9]+)(?:[: ](?P<port>\d{4,5}))?\s*$/i');
    }


    public function checkStatus($parent, $data, $extra)
    {
        extract($extra);
        extract($regexp);
        
        if (empty($port)) {
            $ret = dns_get_record("_minecraft._tcp.".$server, DNS_SRV);
            if (count($ret)>0) {
                $server = $ret[0]['target'];
                $port = $ret[0]['port'];
            } else {
                $port = 25565;
            }
        }


        $extra['server'] = $server;
        $extra['port'] = $port;
        $c = new TCPClient($server, $port);
        $c->setCallback('gotStatus', $this);
        $c->extra = $extra;
        $c->posted = false;
        $c->put("\xFE");
    }
    
    public function gotStatus($c, $data, $error)
    {
        $extra = $c->extra;
        extract($extra);

        if ($error > 0) {

            if ($c->posted) return;

            $this->parent->privmsg($replyto, "[$server:$port] Offline/Error");
            return false;
        }
        
        
        $serverInfo = mb_convert_encoding(substr($data,3), 'auto', 'UCS-2');
        
        $string = $serverInfo;

        $hex = '';
        for ($i=0; $i < strlen($string); $i++)
        {
            $o = ord($string[$i]);
            if ($o > 32 && $o < 127)
                $hex .= " {$string[$i]} ";
            else 
                $hex .= sprintf(' %02x ', $o);
        }

        if (strpos($serverInfo, "\x00") !== false) {
            $info = explode("\x00", $serverInfo);
            $maxPlayers = array_pop($info);
            $players = array_pop($info);
            $motd = array_pop($info);
            $version = array_pop($info);
        } else {
            $info = explode("\xA7", $serverInfo);
            $maxPlayers = array_pop($info);
            $players = array_pop($info);
            $motd = implode("\xA7", $info);
            $version = '';
        }

        if (!$private && $this->parent->chan($replyto)->hasMode('c')) {
            $motd = preg_replace("/\xA7([0-9a-f])/i", "", $motd);
        } else {
            $motd = preg_replace_callback("/\xA7([0-9a-z])/i", function($matches) {
                $s=Array('0'=>'01','1'=>'02','2'=>'03','3'=>'10','4'=>'05','5'=>'06','6'=>'07','7'=>'15','8'=>'14','9'=>'02','a'=>'09','b'=>'11','c'=>'04','d'=>'13','e'=>'08','f'=>'00');
                if (!isset($s[$matches[1]])) return "";
                return sprintf("\x03%02d", $s[$matches[1]]);
            }, $motd);
            $motd .= "\x03";
        }

        $c->posted = true;
        $this->parent->privmsg($replyto, "[$server:$port] (Players: $players/$maxPlayers) $motd".(!empty($version)?" (v:$version)":''));
        
    }
}
