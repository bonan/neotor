<?PHP
date_default_timezone_set('Europe/Stockholm');
/*
 *	The neotor IRCBot project, author: Björn Enochsson aka. bonan
 *	Copyright Björn Enochsson <bonan@g33k.se> 2003-2011 (c)
 *
 *	Redistribution and use in source and binary forms, with or without modification, are
 *	permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright notice, this list of
 *	  conditions and the following disclaimer.
 *	2. Redistributions in binary form must reproduce the above copyright notice, this list
 *	   of conditions and the following disclaimer in the documentation and/or other materials
 *	   provided with the distribution.
 *
 *	THIS SOFTWARE IS PROVIDED BY BJÖRN ENOCHSSON ''AS IS'' AND ANY EXPRESS OR IMPLIED
 *	WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 *	FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL BJÖRN ENOCHSSON OR
 *	CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *	CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *	SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 *	ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *	The views and conclusions contained in the software and documentation are those of the
 *	authors and should not be interpreted as representing official policies, either expressed
 *	or implied, of Björn Enochsson.
 */

error_reporting(E_ALL);

$version = 'v1.1';						// Version number.
$init = 1;							// Tell scripts to run init-code.
$netObj = Array();

declare(ticks = 1);
define('SYS_INIT_TIME', time());	
define('SPATH', 'source/');				// Path to source-files.
define('LPATH', 'libs/');				// Path to libraries.

if (!file_exists(SPATH))
	exit("[error] ".SPATH." was not found, plase make sure you're running neotor from the correct path.\n");

ini_set('output_buffering', 0);			// Disables output buffering.
ini_set('max_execution_time', 0);		// Disables max execution time.
set_time_limit(0);						// Disables time-limit

/*
*	For more info about each part
*	Read the source of those files.
*/

include_once(SPATH.'functions.php');	// Create misc functions.
include_once(SPATH.'args.php');		// Check command-line arguments.
include_once(LPATH.'rb.php');

// Show motd
if (file_exists('docs/logo'))
	printf(file_get_contents('docs/logo'), $version);

include_once(SPATH.'config.php');		// Load config-file.

if (defined('CONFIG_MYSQL_ENABLED') && CONFIG_MYSQL_ENABLED)
	include_once(SPATH.'db.php');		// Create mysql-class

include_once(SPATH.'networks.php');		// Load networks
include_once(SPATH.'StreamContainer.php');
include_once(SPATH.'SocketHandler.php');
include_once(SPATH.'TCPClient.php');
include_once(SPATH.'TCPServer.php');
include_once(SPATH.'user.php');
include_once(SPATH.'logging.php');		// Create log-class.
include_once(SPATH.'partyline.php');	// Create partyline-class
include_once(SPATH.'irc.php');			// Create irc-class
include_once(SPATH.'timer.php');		// Create timer-class
include_once(SPATH.'http.php');			// Create http-class
if (SERVICE)
   include_once(SPATH.'service.php');	// Create service base class

/*
 * Load modules
 */

include_once 'modules/module.php';

if (isset($config['modules'])) { 
	foreach($config['modules'] as $mod => $opt) {
		if (empty($opt) || !file_exists('modules/' . $mod . '.php')) {
			continue;
		} elseif ($opt==1) {
			include_once('modules/' . $mod . '.php');
			$netObj[$mod] = $mod;
		} else {
			include_once('modules/' . $mod . '.php');
			if (!isset($netObj[$opt]) || !is_array($netObj[$opt])) {
				$netObj[$opt] = Array();
			}
			$netObj[$opt][$mod] = $mod;
		}
	}
}


/*
 * Start bot
 */
include_once(SPATH.'start.php');		// Initiate startup-script.
$init = 0;								// Disable all init-codes.
include_once(SPATH.'main.php');			// Initiate while-loop.

/*
*	If the script got this far, an error ocurred.
*/

if (function_exists('logWrite'))
	logWrite(L_ERROR|L_DEBUG, "[error] Script ended without reason.\n");
else
	exit(timestamp()." [error] Script ended without reason.\n");

?>
