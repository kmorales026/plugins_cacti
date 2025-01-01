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
	include_once("capacityreport_lib.php");
	include_once("../../include/global.php");
	include_once("../../include/top_header.php");
	include_once("../../lib/rrd.php");
	api_plugin_hook("console_before");
	//access denied for non-authorized users
	if ((read_config_option("auth_method") != 0) && (!isset($_SESSION["sess_user_id"]))) {	
		echo "ACCESS DENIED";
		return false;
	}
	
	//first screen
	if(!isset($_POST["start"]) and !isset($_POST["end"]) and !isset($_POST["host_id"]) and !isset($_POST["graphs"])){
		draw_init();
	}else if (isset($_POST["start"]) and isset($_POST["end"]) and isset($_POST["host_id"])){ //second screen
		$start = $_POST["start"];
		$end = $_POST["end"];
		$host_id = $_POST["host_id"];
		
		if(!isset($_POST["graphs"])){
			show_graph_list($host_id, $start, $end);
		}else{ //third screen
			$graphs = $_POST["graphs"];
			foreach($graphs as $graph => $property){
				list($graphs_id[], $graphs_title[]) = explode("&", $property);
			}
			
			//taking care of graphs that don't display the hostname
			$hostname = explode(" - ", $graphs_title[0]);
			if(count($hostname) > 1 ){
				array_pop($hostname);
				$hostname = $hostname[0];
			}else{
				$hostname = "";
			}
			echo'
			<div align="center"/>
			<div style="clear:both;"/>
			';

			print_graph_info($graphs_id, $graphs_title, $start, $end, $hostname);
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Capacity Report</title>

	<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.22.custom.min.js"></script>

	<link type="text/css" rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css"></link>
	<style>
		.ui-datepicker-calendar{
			display:none;
		}
	</style>
</head>
<body>
</body>
<script type="text/javascript" language="javascript">
		function selectAll(field) {
			checkboxes = document.getElementsByName('graphs[]');
			if( (checkboxes.length == 0) ){
				checkbox = document.getElementById('graphs_select_all');
				alert("No graphs available");
				checkbox.checked = false;
			}else{
				for(var i in checkboxes){
					checkboxes[i].checked = field.checked;
				}
			}
		}
	</script>
	<script type="text/javascript" language="javascript">
		function calculate(index, graphs, original, option){
		
			var array = new Array();
			
			for(var k=0;k<graphs;k++){
				array[k] = new Array();
				for(var i=0;i<original.length;i++){
					array[k][i]= original[i];
				}
			}
			
			if(option.value==3){ //Divide by 1000
				var operand = 0.001;
				var suffix = "k";
			}else if(option.value==4){ //Divide by 1000000
				var operand = 0.000001;
				var suffix = "M";
			}else if(option.value==5){ //Divide by 1000000000
				var operand = 0.000000001;
				var suffix = "G";
			}else if(option.value==6){ //Multiply by 1000
				var operand = 1000;
				var suffix = "m";
			}else if(option.value==7){ //Multiply by 1000000
				var operand = 1000000;
				var suffix = "u";
			}else if(option.value==8){ //Multiply by 1000000000
				var operand = 1000000000;
				var suffix = "n";
			}else if(option.value==9){ //Multiply by 1000000000
				var operand = 8;
			}else if(option.value==10){ //Multiply by 1000000000
				var operand = 0.125;
			}
			
			for(var i = 0; i < original.length; i++){
				var sanitize = document.getElementById('graph_'+index+'&values_'+i).innerHTML.split(" "); //sanitize
				document.getElementById('graph_'+index+'&values_'+i).innerHTML = sanitize[0]; //sanitize
				
				var current_value = document.getElementById('graph_'+index+'&values_'+i);
				
				if(option.value == 0){//Back to default values
					current_value.innerHTML = array[index][i];
				}else if(option.value == 1){//Transform automatically based on value
					var operand = magic_scale(current_value.innerHTML);
					var suffix = magic_suffix(current_value.innerHTML);
					if(current_value<1){
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML/operand).toFixed(2)) + " " + suffix;
					}else{
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML/operand).toFixed(2)) + " " + suffix;
					}
				}else if(option.value == 2){ //IEC TO SI
					var operand = iec_transform(current_value.innerHTML);
					if(current_value<1){
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2));
						if("suffix" in window)
							current_value.innerHTML += suffix;
					}else{
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2));
						if("suffix" in window)
							current_value.innerHTML += suffix;
					}
				}else if( (option.value>=3) && (option.value<=8) ){ //Divide|Multiply
					var suffix = magic_suffix(current_value.innerHTML);
					if(current_value < 1){
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2)) + " " + suffix;
					}else{
						current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2)) + " " + suffix;
					}
				}else if( (option.value==9) || (option.value==10) ){ //Bytes|Bits
					current_value.innerHTML = current_value.innerHTML * operand;
				}
			}
		}
		
		function remove_zero_decimals(input){
			var split = input.toString().split(".");
			if(split[1] == 00){
				var result = "";
			}else{
				var result = "." + split[1];
			}
			return split[0] + result;
		}
	
		function count_zeros(input, direction){ //direction is 1 for left-to-right progression; -1 for right-to-left progression
			var count = 0;
			for(var i=0;i<(input.toString().length);i++){
				var zero_occurrence = input.substr(i,( 1 * (direction) ));
				if(zero_occurrence==0){
					count++;
				}else{
					break;
				}
			}
			return count;
		}
		
		function magic(input){
			if( !(input == parseInt(input)) ){ //If number has a floating point
				var split = input.toString().split(".");
				if(input==0){
					var result = 0;
				}else if(input>=1){ //If number is greater than 1.0000
					var result = split[0].toString().length;
				}else{ //If number between 0 and 1 (ex: 0.123123)
					var result = count_zeros(split[1], 1) + 1;
				}
			}else{ //If number is an integer
				var result = input.toString().length;
			}
			
			return result;
		}
		
		function iec_transform(input){
			var result = magic(input);
			
			if( (result % 3) != 0 ){
				var output = parseInt(result/3);
			}else if( (result % 3) == 0){
				var output = (result / 3) - 1;
			}
			if(input>=1){
				var final_result = Math.pow(1000, parseInt(output)) / Math.pow(1024, parseInt(output));
			}else{
				var final_result = ( 1 / ( (Math.pow(1000, (output+1))) / (Math.pow(1024, parseInt(output+1))) ) );
			}
			
			return final_result;
		}
		
		function magic_scale(input){	

			var absolute = Math.abs(input);
			var result = magic(absolute);		
			
			if( (result % 3) != 0 ){
				var output = parseInt(result/3);
			}else if( (result % 3) == 0){
				var output = (result / 3) - 1;
			}
			if(absolute>=1){
				var final_result = Math.pow(1000, output);
			}else{
				var final_result = ( 1 / (Math.pow(1000, (output+1))) );
			}
			return final_result;
		}
		
		function magic_suffix(input){
			
			absolute = Math.abs(input);
			if( (result==0) ){ //less than 999; bigger than 0
				var suffix = "";
			}
			else if( (absolute>=1) ){
				var result = magic(absolute);
				if((result>=1) && (result<=3)){
					var suffix = "";
				}
				else if( (result>=4) && (result<=6) ){
					var suffix = "k";
				}else if( (result>=7) && (result<=9) ){
					var suffix = "M";
				}else if( (result>=10) && (result<=12) ){
					var suffix = "G";
				}else if( (result>12) ){
					var suffix = "T";
				}
			}else if( (absolute>=0) && (absolute<1) ){
				if( !(absolute == parseInt(absolute)) ){ //If number has a floating point
					var split = absolute.toString().split(".");
					var result = count_zeros(split[1], 1);
					if( (result>=0) && (result<=2) ){
						var suffix = "m";
					}else if( (result>=3) && (result<=5) ){ 
						var suffix = "u";
					}else if( (result>=6) && (result<=8) ){ 
						var suffix = "n";
					}else if( (result>=9) && (result<=11) ){
						var suffix = "p";
					}
				}else{
					suffix = "";
				}
			}

			return suffix;
		}
	</script>
	<script type="text/javascript" language="javascript">
		function verifyCheckboxSelected(item){
			var item = document.forms[item];
			var valid = false;
			
			for(var i=0; i<item.length; i++){
				if(item.elements[i].checked){
					valid = true;
				}
			}
			
			if(!valid){
				alert("Please select at least one graph to display");
				return false;
			}
		}
	</script>
	<script type="text/javascript" language="javascript">
		$(document).ready(function() {
			$( "#start" ).datepicker({
				prevText: "Previous month",
				nextText: "Next month",
				dateFormat: 'm/yy',
				changeMonth: true,
				changeYear: true,
				showButtonPanel: true,
				onChangeMonthYear: function(year, month) {
					var date = new Date(year, month-1, 1);
					//$("#end").datepicker("option", "minDate", date);
					$("#start").val(month + "/" + year);
				}
			});
			
			$( "#end" ).datepicker({
				prevText: "Previous month",
				nextText: "Next month",
				dateFormat: 'm/yy',
				changeMonth: true,
				changeYear: true,
				showButtonPanel: true,
				onChangeMonthYear: function(year, month) {
					var date = new Date(year, month-1, 1);
					$('#end').val(month + "/" + year);
				}

			});
			$( "#start" ).datepicker('setDate', new Date());
			$( "#end" ).datepicker('setDate', new Date());
		});
		$(document).submit(function() {
			var start = $( "#start" ).val();
			start = "01/" + start;
			start = new Date(start.split("/").reverse().join("/")).getTime() / 1000;
			$( "#start" ).val(start);
			
			var date = new Date();
			var current_month = date.getMonth();
			var current_year = date.getFullYear();
			
			var end = $( "#end" ).val();
			var date = end.split("/");
			
			/*if end datetime is within current month, then it must be equal to current datetime
			else, it'll equal to last datetime of month
			*/
			if( (date[1] == current_year) && ( date[0] == (current_month+1)) ){
				date = new Date();
				var hour = date.getHours();
				var current_day = date.getDate();
				var minute = date.getMinutes();
				end = new Date(current_year, current_month, current_day, hour, minute, "00").getTime() / 1000;
				$( "#end" ).val(end);
			}else{
				var days = new Date(date[1], date[0], 0).getDate();
				var split = end.split("/").reverse();
				end = new Date(split[0], split[1]-1, days, 23, 59, 59).getTime() / 1000;
				$( "#end" ).val(end);
			}
		});
		
	</script>
</html>