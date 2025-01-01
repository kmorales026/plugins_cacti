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
	ob_start();
	require_once("../../include/global.php");
	require_once("availreport_lib.php");
	require_once("cluster_setup_lib.php");
	require_once("../../include/top_header.php");
	api_plugin_hook('console_before');
	if(!isset($_REQUEST["action"])){
		$_REQUEST["action"] = "";
		draw_cluster_init();
	}
	else if(isset($_REQUEST["action"])){
		$action = $_REQUEST["action"];
		switch($action){
			case 'add':
				add_cluster();
				break;
			case 'save':
				form_save();
				break;
			case 'host_remove':
				host_remove();
				break;
			case 'cluster_remove':
				cluster_remove();
				break;
		}
	}
?>