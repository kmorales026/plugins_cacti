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

plugin_routerconfigs_redownload_failed();

db_execute("REPLACE INTO settings (name, value) VALUES ('plugin_routerconfigs_running', 0)");
