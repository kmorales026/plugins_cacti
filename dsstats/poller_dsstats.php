#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2009 The Cacti Group                                 |
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

/* tick use required as of PHP 4.3.0 to accomodate signal handling */
declare(ticks = 1);

/* display_help - generic help screen for utilities
   @returns - null */
function display_help () {
	$dsstats_info = plugin_dsstats_version();
	print "Data Sources Statistics Poller Version " . $dsstats_info["version"] . ", Copyright 2005-2009 - The Cacti Group\n\n";
	print "usage: poller_dsstats.php [-f | --force] [-d | --debug] [-h | -H | --help] [-v | -V | --version]\n\n";
	print "-f | --force     - Force the execution of a update process\n";
	print "-d | --debug     - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h -H --help     - display this help message\n";
}

/* sig_handler - provides a generic means to catch exceptions to the Cacti log.
   @arg $signo - (int) the signal that was thrown by the interface.
   @returns - null */
function sig_handler($signo) {
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
			cacti_log("WARNING: DSStats Poller terminated by user", FALSE, "dsstats");

			/* tell the main poller that we are done */
			db_execute("REPLACE INTO settings (name, value) VALUES ('dsstats_poller_status', 'terminated - end time:" . date("Y-m-d G:i:s") ."')");

			exit;
			break;
		default:
			/* ignore all other signals */
	}
}

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = TRUE;

$dir = dirname(__FILE__);
chdir($dir);

if (strpos($dir, 'dsstats') !== FALSE) {
	chdir('../../');
}

/* include important functions */
if (file_exists("./include/global.php")) {
	include_once("./include/global.php");
} else {
	include_once("./include/config.php");
}
include_once($config["base_path"] . "/lib/poller.php");
include_once($config["base_path"] . "/lib/rrd.php");
include_once($config["base_path"] . "/plugins/dsstats/functions.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$debug          = FALSE;
$forcerun       = FALSE;
$forcerun_maint = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-f":
	case "--force":
		$forcerun = TRUE;
		break;
	case "-v":
	case "--version":
	case "-V":
	case "--help":
	case "-h":
	case "-H":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

/* install signal handlers for UNIX only */
if (function_exists("pcntl_signal")) {
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGINT, "sig_handler");
}

/* take time and log performance data */
list($micro,$seconds) = split(" ", microtime());
$start = $seconds + $micro;

/* let's give this script lot of time to run for ever */
ini_set("max_execution_time", "0");
dsstats_memory_limit();

/* send a gentle message to the log and stdout */
dsstats_debug("Polling Starting");

/* only run if enabled, or forced */
if ((read_config_option("dsstats_enable") == "on") || $forcerun) {
	/* read some important settings relative to timing from the database */
	$major_time     = date("H:i:s", strtotime(read_config_option("dsstats_major_update_time")));
	$daily_interval = read_config_option("dsstats_daily_interval");
	$hourly_window  = date("Y-m-d H:i:s", time() - (read_config_option("dsstats_hourly_duration") * 60));

	/* check to see when the daily averages were updated last */
	$last_run_daily           = read_config_option("dsstats_last_daily_run_time");
	$last_run_major           = read_config_option("dsstats_last_major_run_time");

	/* remove old records from the cache first */
	if (db_fetch_cell("SELECT count(*) FROM data_source_stats_hourly_cache WHERE time < '$hourly_window'")) {
		db_execute("DELETE FROM data_source_stats_hourly_cache WHERE time < '$hourly_window'");
	}

	/* store the current averages into the hourly table */
	db_execute("INSERT INTO data_source_stats_hourly
		(local_data_id, rrd_name, average, peak)
		(SELECT local_data_id, rrd_name, AVG(`value`), MAX(`value`)
		FROM (SELECT local_data_id, rrd_name, `value` FROM data_source_stats_hourly_cache WHERE `value`!= '-90909090909') AS sally
		GROUP BY local_data_id, rrd_name)
		ON DUPLICATE KEY UPDATE average=VALUES(average), peak=VALUES(peak)");
	log_dsstats_statistics("HOURLY");

	/* see if boost is active or not */
	$boost_active = sizeof(db_fetch_cell("SELECT directory FROM plugin_config WHERE directory='boost' AND status='1'"));

	/* next let's see if it's time to update the daily interval */
	$current_time = time();
	if ($boost_active) {
		/* boost will spawn the collector */
	} else {
		if ($daily_interval == "boost") {
			cacti_log("WARNING: Daily update interval set to 'boost' and Boost Plugin Not Enabled, reseting to default of 1 hour", false, "DSSTATS");
			db_execute("REPLACE INTO settings (name, value) VALUES('dsstats_daily_interval', '60')");
		}

		/* determine if it's time to determine hourly averages */
		if ($last_run_daily == "") {
			/* since the poller has never run before, let's fake it out */
			db_execute("REPLACE INTO settings (name, value) VALUES ('dsstats_last_daily_run_time', '" . date("Y-m-d G:i:s", $current_time) . "')");
		}

		/* if it's time to update daily statistics, do so now */
		if ((($last_run_daily != "") && ((strtotime($last_run_daily) + ($daily_interval * 60)) < $current_time)) || ($forcerun)) {
			/* run the daily stats */
			dsstats_get_and_store_ds_avgpeak_values("daily");
			log_dsstats_statistics("DAILY");
			db_execute("REPLACE INTO settings (name, value) VALUES ('dsstats_last_daily_run_time', '" . date("Y-m-d G:i:s", $current_time) . "')");
		}
	}

	/* lastly, let's see if it's time to run the major stats */
	if ($last_run_major == "") {
		/* since the poller has never run before, let's fake it out */
		db_execute("REPLACE INTO settings (name, value) VALUES ('dsstats_last_major_run_time', '" . date("Y-m-d G:i:s", $current_time) . "')");
	} else {
		$last_major_day = date("Y-m-d", strtotime($last_run_major));

		$next_major_day = strtotime($last_major_day . " " . $major_time) + 86400;
	}

	/* if its time to run major statistics, do so now */
	if ((($last_run_major != "") && ($next_major_day < $current_time)) || ($forcerun)) {
		/* run the major stats, log first to keep other processes from running */
		db_execute("REPLACE INTO settings (name, value) VALUES ('dsstats_last_major_run_time', '" . date("Y-m-d G:i:s", $current_time) . "')");
		dsstats_get_and_store_ds_avgpeak_values("weekly");
		dsstats_get_and_store_ds_avgpeak_values("monthly");
		dsstats_get_and_store_ds_avgpeak_values("yearly");
		log_dsstats_statistics("MAJOR");
	}
}

dsstats_debug("Polling Ending");

?>
