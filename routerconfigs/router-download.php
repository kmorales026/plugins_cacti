<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2008 The Cacti Group                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* let PHP run just as long as it has to */
ini_set("max_execution_time", "0");

error_reporting('E_ALL');
$dir = dirname(__FILE__);
chdir($dir);

ini_set("max_execution_time", 0);
ini_set("memory_limit", "256M");

if (strpos($dir, 'plugins') !== false) {
	chdir('../../');
}
if (file_exists("./include/global.php")) {
	include("./include/global.php");
} else {
	include("./include/config.php");
}

include_once($config['base_path'] . '/plugins/routerconfigs/functions.php');
db_execute("REPLACE INTO settings (name, value) VALUES ('plugin_routerconfigs_running', 1)");

$t = $stime = time();
$devices = db_fetch_assoc("SELECT * FROM plugin_routerconfigs_devices WHERE enabled = 'on' AND ($t - (schedule * 86400)) - 3600 > lastbackup");
$failed = array();
if (!empty($devices)) {
	foreach ($devices as $device) {
		echo "Processing " . $device['hostname'] . "\n";
		plugin_routerconfigs_download_config($device);

		// Check for failed Backup
		$t = time() - 120;
		$f = db_fetch_assoc("SELECT * FROM plugin_routerconfigs_backups WHERE btime > $t AND device = " . $device['id']);
		if (empty($f)) {
			$device = db_fetch_assoc("SELECT * FROM plugin_routerconfigs_devices WHERE id = " . $device['id']);
			$failed[] = array ('hostname' => $device[0]['hostname'], 'lasterror' => $device[0]['lasterror']);
		}
		sleep(10);
	}
} else {
	return;
}
$success = count($devices) - count($failed);
$cfailed = count($failed);

cacti_log("$success Devices Backed Up and $cfailed Devices Failed in " . time() - $stime . " seconds", true, "RouterConfigs");

$message = "$success devices backed up successfully.\n";
$message .= "$cfailed devices failed to backup.\n\n";
if (!empty($failed)) {
	$message .= "These devices failed to backup\n--------------------------------\n";
	foreach ($failed as $f) {
		$message .= $f['hostname'] . ' - ' . $f['lasterror'] . "\n";
	}
}
echo $message;

$email = read_config_option('routerconfigs_email');
$from = read_config_option('routerconfigs_from');
if (strlen($email) > 1) {
	if (strlen($from) < 2) {
		$from = 'ConfigBackups@reyrey.com';
	}
	send_mail($email, $from, 'Network Device Configuration Backups', $message, $filename = '', $headers = '', $fromname = 'Config Backups');
}
plugin_routerconfigs_retention ();
db_execute("REPLACE INTO settings (name, value) VALUES ('plugin_routerconfigs_running', 0)");




