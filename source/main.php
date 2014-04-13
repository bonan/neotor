<?php
/**
 * Handles the main loop
 * 
 * PHP version 5
 * 
 * @category  Core
 * @package   Core
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

while (true) {
    
    $write  = streamContainer()->getWriteStreams();
    $read   = streamContainer()->getReadStreams();
    $except = null;
    
    if (empty($loopTime) || time() > $loopTime) {
        Timer::checkTimers();
        $loopTime = time();
    }
    
    if (count($write) + count($read) == 0) {
        //No active sockets, sleeping
        usleep(500000);
        continue;
    }

    if (stream_select($read, $write, $except, 0, 50000) > 0) {
        if (is_array($write) && count($write) > 0) {
            foreach ($write as $socket) {
                streamContainer()->getHandler($socket)->writeData();
            }
        }
        
        if (is_array($read) && count($read) > 0) {
            foreach ($read as $socket) {
                streamContainer()->getHandler($socket)->hasData();
            }
        }
        
    }
    
} // while
