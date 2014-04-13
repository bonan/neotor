<?php

function tname($name)
{
    if (substr($name, 0, 6) == 'telnet') {
        return substr($name, 6);
    } else {
        return false;
    }
}

function timestamp($time = '')
{
    return "[" . date('H:i:s', (empty($time)?time():$time)) . "] ";
}

function utime()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function matchCallback($level, $callback)
{
    if (($level & $callback) > 0) {
        return true;
    } else {
        return false;
    }
}

function sigHandler($signo)
{
    switch($signo) {
    case SIGTERM:
        shutdown("Got SIGTERM");
        break;
    case SIGHUP:
        logWrite(L_SYSTEM, "[SYSTEM] Got SIGHUP, Forced rehash.");
        rehash();
        break;
    }
}

function shutdown($msg = '')
{
    global $ircNetworks, $module, $log;
    logWrite(
        L_SYSTEM, sprintf(
            '[SYSTEM] Shutting down neotor%s', 
            (!empty($msg)?': '.$msg:'')
        )
    );

    // Kill all irc-connections
    foreach ($ircNetworks as $key => $value) {
        $ircNetworks[$key]->quit($msg);
    }

    // Close all logs
    if (!is_array($log)) {
        $log = Array();
    }
    foreach ($log as $key => $value) {
        if ($key>0) {
            $log[$key]->close();
        }
    }

    // Unload all modules
    if (!is_array($module)) {
        $module = Array();
    }
    foreach ($module as $key => $value) {
        $value->forceUnload();
    }

    // Going down
    exit;
}

function rehash()
{
    /**
    * Code for bot-rehash here.
    */
}

function ischan($chan = '')
{
    if (substr($chan, 0, 1) == '#') {
        return true;
    } else {
        return false;
    }
} // function ischan

function detectUTF8($string)
{
    return preg_match(
        '%(?:
        [\xC2-\xDF][\x80-\xBF]              # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )+%xs', 
        $string
    );
}


?>
