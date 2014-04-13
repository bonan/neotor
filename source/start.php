<?php

/**
 * Handles startup of bot
 *
 *
 * PHP version 5
 *
 * @category  Misc
 * @package   Bot
 * @author    Björn Enochsson <bonan@g33k.se>
 * @copyright 2003-2012 Björn Enochsson <bonan@g33k.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://neotor.se/
 */


// Fork, if not debug-mode.

if (!defined("DEBUG") && function_exists('pcntl_fork')) {
    logWrite(L_DEBUG, sprintf('[DEBUG] Creating background-process'));
    $pid = pcntl_fork();
    if ($pid == -1) {
        logWrite(L_DEBUG, sprintf('[DEBUG] Failed, entering debug-mode'));
        define('DEBUG', true);
    } elseif ($pid) {
        logWrite(
            L_DEBUG,
            sprintf(
                '[DEBUG] Sucessfully created background process, pid %s',
                $pid
            )
        );

        // Writing pid-file.
        if (!file_exists($config['system']['pidfile'])) {
            touch($config['system']['pidfile']);
        }

        $pidfile = fopen($config['system']['pidfile'], 'w');
        fwrite($pidfile, $pid);

        exit();
    }
} else {
    logWrite(L_DEBUG, sprintf('[DEBUG] Entering debug-mode'));
    
    if (!defined("DEBUG")) {
        define("DEBUG", true);
    }
}


if (function_exists("pcntl_signal")) {
    pcntl_signal(SIGTERM, "sigHandler");
    pcntl_signal(SIGHUP,  "sigHandler");
}


$ircNetworks = $writeStreams = $openStreams = $openFiles = Array();

if (defined('DEBUG')) {
    logOpen('php://stdout', L_DEBUG|L_SYSTEM|L_ERROR|L_TELNET|L_PRIVMSG|L_IRCALL);
    if (PHP_OS !== 'WINNT' && $tmpfp = fopen('php://stdin', 'r')) {
        $openFiles['console'] = Array('socket' => $tmpfp, 'type' => 'console');
        $console = new stdin($tmpfp);
        unset($tmpfp);
    }
    logWrite(L_DEBUG, sprintf('[DEBUG] Started neotor %s.', $version));
}

//	logOpen('logs/g33k.se.log', L_CHANNEL, '#g33k.se');

if (function_exists("irc_open_sockets")) {
    irc_open_sockets();
}


?>
