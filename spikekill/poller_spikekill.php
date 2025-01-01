#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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

chdir(dirname(__FILE__));
chdir("../..");
include("./include/global.php");

ini_set("memory_limit", "512M");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

global $debug, $start, $seed, $forcerun;

$debug          = FALSE;
$forcerun       = FALSE;
$templates      = FALSE;
$kills          = 0;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "--templates":
		$templates = $value;
		break;
	case "-f":
	case "--force":
		$forcerun = TRUE;
		break;
	case "-v":
	case "--help":
	case "-V":
	case "--version":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

echo "NOTE: SpikeKill Running\n";

if (!$templates) {
	$templates = array_rekey(db_fetch_assoc("SELECT graph_template_id FROM plugin_spikekill_templates"), "graph_template_id", "graph_template_id");
}else{
	$templates = explode(",",$templates);
}

if (timeToRun()) {
	debug("Starting Spikekill Process");

	list($micro,$seconds) = explode(" ", microtime());
	$start   = $seconds + $micro;

	$graphs = kill_spikes($templates, $kills);

	list($micro,$seconds) = explode(" ", microtime());
	$end  = $seconds + $micro;

    $cacti_stats = sprintf(
        "Time:%01.4f " .
        "Graphs:%s " . 
		"Kills:%s",
        round($end-$start,2),
        $graphs,
		$kills);

    /* log to the database */
    db_execute("REPLACE INTO settings (name,value) VALUES ('stats_spikekill', '" . $cacti_stats . "')");

    /* log to the logfile */
    cacti_log("SPIKEKILL STATS: " . $cacti_stats , TRUE, "SYSTEM");
}

echo "NOTE: SpikeKill Finished\n";

function timeToRun() {
	global $forcerun;

	$lastrun   = read_config_option("spikekill_lastrun");
	$frequency = read_config_option("spikekill_batch") * 3600;
	$basetime  = strtotime(read_config_option("spikekill_basetime"));
	$baseupper = 300;
	$baselower = $frequency - 300;
	$now       = time();

	debug("LastRun:'$lastrun', Frequency:'$frequency', BaseTime:'$basetime', BaseUpper:'$baseupper', BaseLower:'$baselower', Now:'$now'");
	if ($frequency > 0 && ($now - $lastrun > $frequency)) {
		debug("Frequency is '$frequency' Seconds");

		$nowfreq = $now % $frequency;
		debug("Now Frequency is '$nowfreq'");

		if ((empty($lastrun)) && ($nowfreq > $baseupper) && ($nowfreq < $baselower)) {
			debug("Time to Run");
			db_execute("REPLACE INTO settings (name,value) VALUES ('spikekill_lastrun', '" . time() . "')");
			return true;
		} elseif (($now - $lastrun > 3600) && ($nowfreq > $baseupper) && ($nowfreq < $baselower)) {
			debug("Time to Run");
			db_execute("REPLACE INTO settings (name,value) VALUES ('spikekill_lastrun', '" . time() . "')");
			return true;
		} else {
			debug("Not Time to Run");
			return false;
		}
	} elseif ($forcerun) {
		debug("Force to Run");
		db_execute("REPLACE INTO settings (name,value) VALUES ('spikekill_lastrun', '" . time() . "')");
		return true;
	} else {
		debug("Not time to Run");
		return false;
	}
}

function debug($message) {
	global $debug;

	if ($debug) {
		echo "DEBUG: " . trim($message) . "\n";
	}
}

function kill_spikes($templates, &$found) {
	global $debug, $config;

	$rrdfiles = array_rekey(db_fetch_assoc("SELECT DISTINCT rrd_path 
		FROM graph_templates AS gt
		INNER JOIN graph_templates_item AS gti
		ON gt.id=gti.graph_template_id
		INNER JOIN data_template_rrd AS dtr
		ON gti.task_item_id=dtr.id
		INNER JOIN poller_item AS pi ON pi.local_data_id=dtr.local_data_id
		WHERE gt.id IN (" . implode(",", $templates) . ")"), "rrd_path", "rrd_path");

	if (sizeof($rrdfiles)) {
	foreach($rrdfiles as $f) {
		debug("Removing Spikes from '$f'");
		$response = exec(read_config_option("path_php_binary") . " -q " . $config['base_path'] . "/plugins/spikekill/removespikes.php --rrdfile=$f" . ($debug ? " --debug":""));
		if (substr_count($response, "Spikes Found and Remediated")) {
			$found++;
		}

		debug(str_replace("NOTE: ", "", $response));
	}
	}

	return sizeof($rrdfiles);
}

function display_help() {
	echo "SpikeKiller Batch Poller 1.0, Copyright 2004-2011 - The Cacti Group\n\n";
	echo "Cacti batch Graph spike killer poller process.\n\n";
	echo "usage: poller_spikekill.php [--force] [--debug] [--tempaltes=N,N,N]\n";
}
