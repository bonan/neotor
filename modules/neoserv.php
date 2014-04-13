<?php

class neoserv extends service {
    
    protected function _init() {
        $this->register(
            CONFIG_neoserv_nick,
            CONFIG_neoserv_ident,
            CONFIG_neoserv_host,
            CONFIG_neoserv_realname
        );
        $this->raw("MODE {$this->me()->nick} :+o");
        $this->parent->raw(":{$this->parent->me()->nick} SWHOIS {$this->me()->nick} :NeoServ Services");
        $this->raw('JOIN #opers');
        $this->attach('ctcp' . $this->id, Array($this, '_ctcp'), Array('PRIVMSG'), "/^\x01(?P<type>[^\s]+)(?:\s(?P<message>.*))?\x01$/i");
    }
    
    public function _ctcp($parent, $data, $extra) {
        extract($extra);
        
        $type = strtoupper($extra['regexp']['type']);
        $message = (empty($extra['regexp']['message']) ? '' : $extra['regexp']['message']);
        
        switch($type) {
            case 'VERSION':
                $this->raw("NOTICE {$nick} :\x01{$type} NeoServ v1.0\x01");
                break;
            case 'PING':
                $this->raw("NOTICE {$nick} :\x01{$type} {$message}\x01");
                break;
        }
        
        
    }
}
