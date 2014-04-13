<?php
/**
 * Parses configs for neotor.
 *
 * PHP Version 5
 *
 * @category Config
 * @package  Config
 * @author   Bonan Bonan <bonan@bonan.se>
 * @license  http://neotor.se/license BSD license
 * @link     http://www.neotor.se/
 *
 */
if (empty($args['config'])) {
    $args['config'] = 'neotor.conf';
}


if ($init == 1) {
    printf("Loading config-file: %s\n", $args['config']);
}

if (!$config = @parse_ini_file($args['config'], true)) {

    printf("Config file '%s' does not exist\n", $args['config']);
    die();
}

foreach ($config as $key => $value) {
    foreach ($value as $key2 => $value2) {
        define(sprintf('CONFIG_%s_%s', $key, $key2), $value2, true);
    }
}

printf("-- Checking config-file: --\n");

if (!empty($args['pidfile'])) {
    $config['system']['pidfile'] = $args['pidfile'];
}

printf(
    "* Pidfile: %s\n", 
    (
        empty(
            $config['system']['pidfile']
        )?'Failed':$config['system']['pidfile']
    )
);
