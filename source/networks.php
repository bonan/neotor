<?php

/**
 * Handles network configurations.
 *
 * PHP version 5
 *
 * @category  HTTP
 * @package   HTTP
 * @author    Björn Enochsson <bonan@neotor.se>
 * @copyright 2007-2012 Björn Enochsson <bonan@neotor.se>
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://www.example.com/lol
 */

// Make sure this file can be include():ed 
// every time we need to reload network information
$global['networks'] = Array();
printf("* Loading networks: ");

if (CONFIG_NETWORK_FROM == 'mysql') {
    if ($q = db::query("SELECT * FROM {$db_prefix}IrcNetworks", $myConn)) {
        printf("* Loading networks: ");
        
        while ($r = @mysql_fetch_array($q)) {
            $networkList[$r['network']] = $r;
        }
    } else {
        printf("Failed, %s\n", mysql_error());
    }

} else {

    if (defined('CONFIG_FLATFILE_NETWORKS')) {
        if (file_exists(CONFIG_FLATFILE_NETWORKS)) {
            if ($networkList = parse_ini_file(CONFIG_FLATFILE_NETWORKS, true)) {
                foreach ($networkList as $k=>$r) {
                    $networkList[$k]['network'] = $k;
                }
            } else {
                printf("Failed, parse error in %s\n", CONFIG_FLATFILE_NETWORKS);
            }
        } else {
            printf("Failed, %s does not exist\n", CONFIG_FLATFILE_NETWORKS);
        }
    } else {
        printf("Failed, %s not defined\n", "CONFIG_FLATFILE_NETWORKS");
    }
}

foreach ($networkList as $k=>$r) {
    if ($r['active'] == 1) {
        $global['networks'][] = $r['network'];
    }
    printf("%s(%s) ", $r['network'], $r['active']==1?'Active':'Inactive');
}
printf("\n");

?>
