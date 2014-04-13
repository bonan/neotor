<?php
/**
 * Parses args for neotor
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

if ($_SERVER['argc'] > 2) {
    foreach ($_SERVER['argv'] as $nr => $arg) {
        if ($arg == '--help' || $arg == '-h') {
            die(
                sprintf(
                    file_get_contents('docs/help'), 
                    $version, 
                    $_SERVER['argv'][1]
                )
            );
        }

        if ($arg == '--debug' || $arg == '-d') {
            if (!defined("DEBUG"))
                define("DEBUG", true);
        }

        if ($arg == '--version' || $arg == '-v') {
            die(
                sprintf(
                    "neotor %s - Released 20120805. Written by: Björn Enochsson.\n", 
                    $version
                )
            );
        }

        if ((substr($arg, 0, 8) == '--config') || ($arg == '-c')) {
            $args['config'] = (
                strlen(
                    $arg
                )
                >8?
                substr(
                    $arg, 9
                ):$_SERVER['argv'][$nr+1]);
        }

        if ((substr($arg, 0, 9) == '--pidfile') || ($arg == '-p')) {
            $args['pidfile'] = (
                strlen(
                    $arg
                )>
                9?substr(
                    $arg, 
                    10
                ):$_SERVER['argv'][$nr+1]);
        }

        if ((substr($arg, 0, 7) == '--service') || ($arg == '-s')) {
            define("SERVICE", true);
        }
    }
}

if (!defined('SERVICE')) {
    define('SERVICE', false);
}
