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
	require("../../include/global.php");
	include("availreport_lib.php");
	include("../../include/top_header.php");
	api_plugin_hook('console_before');
	//access denied for non-authorized users
	if ((read_config_option("auth_method") != 0) && (!isset($_SESSION["sess_user_id"]))) {	
		echo "ACCESS DENIED";
		return false;
	}
	// Host / Cluster selection screen
	if( empty($_GET) ){
		draw_init();
	}
	// IF: Data source selection screen | ELSE: Calendar screen
	else if( ( isset($_GET["host_id"]) || isset($_GET["hostgroup_id"]) ) && ( !isset($_GET["datetime_start"]) && !isset($_GET["datetime_end"]) ) ){
		$hosts_radio = $_GET["hosts_radio"];
		switch($hosts_radio){
			case 1:
				display_custom_message("Single Host Mode", "warning");
				$host_id = $_GET["host_id"];
				if( !isset($_GET["data_source_path"]) ){
					print_data_source_select($host_id);
				}else if( isset($_GET["data_source_path"]) ){
					draw_calendar($host_id);
				}
				break;
			case 2:
				display_custom_message("Cluster Mode", "warning");
				if( isset($_GET["hostgroup_id"]) ){
					$hostgroup_id = $_GET["hostgroup_id"];
					$hosts = query_hosts_from_cluster($hostgroup_id);
				}
				if( !isset($_GET["data_source_path"]) ){
					print_data_source_select($hosts);
				}else if( isset($_GET["data_source_path"]) ){
					draw_calendar($hostgroup_id);
				}
		}
	}
	if(isset($_GET["datetime_start"]) && isset($_GET["datetime_end"])){
		$datetime_start = $_GET["datetime_start"]; //convert data text into unix timestamp
		$datetime_end = $_GET["datetime_end"]; 
		$selected_group = $_GET["selected_group"]; //currently selected ID of host or cluster
		$hosts_radio = $_GET["hosts_radio"]; //1 == single host / 2 == cluster
		$time_range = $datetime_end - $datetime_start;
		if($time_range < 900){ //at least 15min, otherwise rrdtool bugs the output
			display_custom_error_message(
				"Not enough data to produce a report between '" . date("d F Y H:i:s", $datetime_start) . "' and '" . date("d F Y H:i:s", $datetime_end) . "'
				</br>Please select a wider time slot"
			);
			echo'</br><input type="button" value="Back" onClick="history.go(-1);">';
		}else{
			if(isset($_GET["action"])){
				$action = $_GET["action"];
				$fetch = fetch_data($datetime_start, $datetime_end);
				expand($datetime_start, $datetime_end, $selected_group, $hosts_radio);
			}else{
				switch($hosts_radio){
					case 1:
						$fetch = fetch_data($datetime_start, $datetime_end);
						print_summary($datetime_start, $datetime_end, $fetch["up_percentage"], $fetch["down_percentage"], "summary", $selected_group, $hosts_radio);
					break;
					case 2:
						$fetch = fetch_data($datetime_start, $datetime_end);
						$type = query_cluster_type($selected_group);
						print_summary($datetime_start, $datetime_end, $fetch["up_percentage"], $fetch["down_percentage"], "summary", $selected_group, $hosts_radio);
					break;
				}
			}
		}
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Availability Report</title>

	
	
	<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.22.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

	<link type="text/css" rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css"></link>
	<style type="text/css">
	</style>
	<script type="text/javascript" language="javascript">
		function validateCalendar(){
			var start = document.calendar.datetime_start;
			var end = document.calendar.datetime_end;
			
			if(start.value=="" || end.value==""){
				alert("All date fields must be filled")
				return false;
			}else{
				return true;
			}

		}
		
		function verifySelected(){
			if($('input[type=radio]:checked').size() == 0){
				alert("Please select one option");
				return false;
			}else{
				return true;
			}
		}
	</script>
	<script type="text/javascript" language="javascript">
		$(document).ready(function() {
			var startDateTextBox = $('#datetime_start');
			var endDateTextBox = $('#datetime_end');

			startDateTextBox.datetimepicker({ 
				onClose: function(dateText, inst) {
					if (endDateTextBox.val() != '') {
						var testStartDate = startDateTextBox.datetimepicker('getDate');
						var testEndDate = endDateTextBox.datetimepicker('getDate');
						if (testStartDate > testEndDate)
							endDateTextBox.datetimepicker('setDate', testStartDate);
					}
					else {
						endDateTextBox.val(dateText);
					}
				},
				onSelect: function (selectedDateTime){
					endDateTextBox.datetimepicker('option', 'minDate', startDateTextBox.datetimepicker('getDate') );
				}
			});
			endDateTextBox.datetimepicker({ 
				onClose: function(dateText, inst) {
					if (startDateTextBox.val() != '') {
						var testStartDate = startDateTextBox.datetimepicker('getDate');
						var testEndDate = endDateTextBox.datetimepicker('getDate');
						if (testStartDate > testEndDate)
							startDateTextBox.datetimepicker('setDate', testEndDate);
					}
					else {
						startDateTextBox.val(dateText);
					}
				},
				onSelect: function (selectedDateTime){
					$(this).datetimepicker({maxDate: '0'});
					startDateTextBox.datetimepicker('option', 'maxDate', endDateTextBox.datetimepicker('getDate') );
				}
			});
			var date = new Date();
			startDateTextBox.datepicker('setDate', new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0));
			endDateTextBox.datepicker('setDate', new Date());
		});
		$(document).submit(function() {
			var start = $("#datetime_start").val();
			var date = start.match(/\d+/g); // extract date parts
			var value = +new Date(date[2], date[0] - 1, date[1], date[3], date[4], "00")/1000; //
			$("#datetime_start").val(value);
			
			var end = $("#datetime_end").val();
			var date = end.match(/\d+/g); // extract date parts
			var value = +new Date(date[2], date[0] - 1, date[1], date[3], date[4], "00")/1000; //
			$("#datetime_end").val(value);
		});
	</script>
	<script type="text/javascript" language="javascript">
	function toggleSelect(input){
		if (input.value == 1){
			document.getElementById("host_id").disabled = false;
			document.getElementById("hostgroup_id").disabled = true;
		}
		else if (input.value == 2){
			document.getElementById("hostgroup_id").disabled = false;
			document.getElementById("host_id").disabled = true;
		}
	}
	</script>
</head>
<body>
</body>
</html>