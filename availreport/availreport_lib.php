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
function draw_init(){
	global $colors;
	html_start_box("Choose hosts", "100%", $colors["header"], "0", "center", "");
	$hosts = db_fetch_assoc("select id, hostname, description from host where disabled!='on'");

	echo "
	<form id='list' action='' method='GET' onSubmit='return verifySelected()'>	
	";
	
		echo"
		<table width='100%' cellspacing='0' style='padding:0px;margin:0px;' bgcolor='#".$colors['panel']."'>
			<tr>
				<td width='110'>
					&nbsp;<b>Single Host:</b>&nbsp;
				</td>
				<td>
					<input type='radio' name='hosts_radio' value='1' onclick='toggleSelect(this);'/>
						<select name='host_id' id='host_id'>";
						foreach($hosts as $host){
							echo "<option value=".$host['id'].">".$host['hostname']." (".$host['description'].")</option>";
						}
						echo "
						</select>
				</td>
			</tr>
		";
		
		$hostgroups = db_fetch_assoc("select id, description, type from hostgroup");
		echo "
			<tr>
				<td width='110'>
					&nbsp;<b>Cluster:</b>&nbsp;
				</td>
				<td>
					<input type='radio' name='hosts_radio' value='2' onclick='toggleSelect(this);'/>
						<select name='hostgroup_id' id='hostgroup_id'>";
						foreach($hostgroups as $hostgroup){
							switch($hostgroup["type"]){
								case "S":
									echo "<option value=".$hostgroup['id'].">".$hostgroup['description']." (Series)</option>";
									break;
								case "P":
									echo "<option value=".$hostgroup['id'].">".$hostgroup['description']." (Parallel)</option>";
									break;
							}
						}
						echo "
						</select>
				</td>
			</tr>
			<tr>
				<td width='110'>";
					if(!isset($_GET["host_id"])){
						echo"
							<input type='submit' value='Go'/>
							<input type='button' value='Back' onClick='history.go(-1);'>
						";
					}
					echo"
				</td>
				<td>
				</td>
			</tr>
		</table>
	</form>
	";
	html_end_box();
}

function draw_calendar($selected_group){
	global $colors;	
	if( isset($_GET["hosts_radio"]) ){
		$hosts_radio = $_GET["hosts_radio"];
	}
	
	html_start_box("Choose time period", "100%", $colors["header"], "0", "center", "");
	echo'
	<form action="" name="calendar" method="GET" onsubmit="return validateCalendar()">
		<input type="hidden" name="selected_group" value="'.$selected_group.'"/>
		<input type="hidden" name="hosts_radio" value="'.$hosts_radio.'"/>
		';
		switch($hosts_radio){
		case 1:
			if( isset($_GET["data_source_path"]) ){
				$data_source_path = $_GET["data_source_path"];
				echo'
				<input type="hidden" name="data_source_path" value="'.$data_source_path.'"/>
				';
			}
		break;
		case 2:
			if( isset($_GET["data_source_path"]) ){
				$data_source_paths = $_GET["data_source_path"];
				foreach($data_source_paths as $data_source_path){
					echo'
						<input type="hidden" name="data_source_path[]" value="'.$data_source_path.'"/>
					';
				}
			}
		break;
		}
		
		echo'
		<table width="100%" cellspacing="0" style="padding:0px;margin:0px;" bgcolor="#'.$colors["panel"].'">
			<tr>
				<td width="70">
					&nbsp;<b>Start date</b>&nbsp;
				</td>
				<td>
					<input type="text" name="datetime_start" id="datetime_start"/>
				</td>
			</tr>
			<tr>
				<td width="70">
					&nbsp;<b>End date</b>&nbsp;
				</td>
				<td>
					<input type="text" name="datetime_end" id="datetime_end"/>
				</td>
			</tr>
			<tr>
				<td width="50">
					<input type="submit" value="Go" />
				</td>
				<td>
					<input type="button" value="Back" onClick="history.go(-1);">
				</td>
			</tr>
		</table>
	</form>
	';
	html_end_box();
}

function read_file($pathname, $opts){
	$array = rrd_fetch("$pathname", $opts, count($opts)); //read data from the rrd file using previously declared parameters
	$count_NaN=0; //will serve to count how many NaN's occurred and in turn give the overall availability percentage
	$i=0; // simple counter initialization. If you don't know what something like this does, you really shouldn't be here
	if(!empty($array)){
		$array["data"] = array_slice($array["data"], 0, (count($array["data"]))-1);
		$data_count = count($array["data"]);
		foreach($array["data"] as $set => $data){
			$timestamp = $array["start"] + ($array["step"] * $i);
			if(strcmp($data,"NAN")==0 || strcmp($data,"inf")==0){
				$count_NaN++;
				$array["values"][$timestamp] = 0;
			}else{
				$array["values"][$timestamp] = 1;
			}
			$i++;
		}
		if(!isset($times[0])){
			$times[0] = 0;
		}
		$list = array("start"=>$array["start"], "end"=>$array["end"]-$array["step"], "NaN"=>$count_NaN, "data"=>$array["values"], "step"=>$array["step"]);
	}else{	
		$list = array("start"=>$opts[2], "end"=>$opts[4], "NaN"=>"", "data"=>"", "step"=>$array["step"]);
	}
	return $list;
}

function fetch_data($start, $end){
	global $config;
	if(isset($_GET["data_source_path"])){
		$rrd_file = $_GET["data_source_path"];
	}
	if(sizeof($rrd_file)==0){ //FILE DOES NOT EXIST | CANNOT BE FOUND
		display_custom_error_message("Invalid .rrd file path");
		return $list = array("start"=>$start, "end"=>$end, "up_percentage"=>0, "down_percentage"=>100);
	}else{ //FILE OK
		$opts = array("AVERAGE", "--start", "$start", "--end", "$end");
		
		if(!is_array($rrd_file)){ //SINGLE HOST MODE
			$pathname = $config["rra_path"] . $rrd_file;
			$result = read_file($pathname, $opts); //read data from the rrd file using previously declared parameters
		
			$count_all = count($result["data"]);
			$up_percentage = 100 - ($result["NaN"] * 100 / $count_all); //AVAILABILITY IS GIVEN SIMPLY COUNTING WHAT DATA ISN'T A "NaN"
			$down_percentage = 100 - $up_percentage;
			return $list = array("start"=>$result["start"], "end"=>$result["end"], "up_percentage"=>$up_percentage, "down_percentage"=>$down_percentage);
		}
		
		else{// CLUSTER MODE
			$selected_group = $_GET["selected_group"];
			$type = query_cluster_type($selected_group);
			
			foreach($rrd_file as $file){
				$pathname = $config["rra_path"] . $file;

				$result = read_file($pathname, $opts); //read data from the rrd file using previously declared parameters
				if(!empty($result["step"])){
					$step = $result["step"];
				}
				$data[] = $result["data"];
			}
			if(isset($data)){
				$uptime = round(calculate_availability($data, $type, $start, $end, $step), 2);
			}else{
				$uptime = 0;
			}
			return $list = array("start"=>$result["start"], "end"=>$result["end"], "up_percentage"=>$uptime, "down_percentage"=>100-$uptime);
		}
	}
}

function get_years($start, $end){
	$year_start = date("Y", $start);
	$year_end = date("Y",$end);
	$number_of_years = ($year_end - $year_start) + 1;
	$year = $start;
	for($i=0; $i<$number_of_years; $i++){
		$years[] = $year;
		$year = strtotime("+1 year", $year);
	}
	return $years;
}

function get_months($start, $end){
	$month_start = (date("Y", $start) * 12) + date("m",$start);
	$month_end = (date("Y",$end) * 12) + date("m", $end);
	$number_of_months = ($month_end - $month_start) + 1;
	$month = $start;
	for($i=0; $i<$number_of_months; $i++){
		$months[] = $month;
		$month = strtotime("+1 month", $month);
	}
	return $months;
}

function get_days($start, $end){
	$day_start = date("d", $start);
	$day_end = date("d", $end);
	if(date("F Y", $start) != date("F Y", $end)){
		$number_of_days = date("t", $start);
	}else{
		$number_of_days = date("d", $end) - date("d", $start) +1;
	}
	$day = $start;
	for($i=0; $i<$number_of_days; $i++){
		$days[] = $day;
		$day = strtotime("+1 day", $day);
	}
	return $days;
}


/*THIS IS THE LOGIC BEHIND THE "EXPAND" BUTTON. IT DETECTS INPUT TIME RANGES AND CLASSIFIES INTO TIME SETS (YEARS, MONTHS, DAYS, ETC)
AND DISPLAYS IN THE MOST APPROPRIATE SET VIEW, FROM WIDER TO SMALLER SET VIEW, MEANING "YEARS > MONTHS > DAYS > HOURS" WHEN APPLICABLE.

YEAH, I KNOW. THIS CODE IS A MESS. I PLAN ON MAKING IT BETTER.
*/
function expand($start, $end, $id, $hosts_radio){
	$action = $_GET["action"];
	$time_range = $end - $start;
	$start_time = mktime(0,0,0,1,1,date("Y",$start));
	$end_time = mktime(23,59,59,date("m",$end),date("t",date("m", $end)),date("Y",$end));
	
	$years = get_years($start, $end);
	$months = get_months($start, $end);
	if(count($years)>1){
		if(strcmp($action, "expand")==0){
			foreach($years as $year){
				$get_start_end = get_start_end(0, $year);
				$fetch = fetch_data($get_start_end["first"], $get_start_end["last"]);
				if(date("Y", $year) == date("Y")){
					$first[] = $get_start_end["first"];
				}else{
					$first[] = $start;
				}
				$last[] = $get_start_end["last"];
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}
			print_summary($first, $last, $uptime, $downtime, "yearly", $id, $hosts_radio);
		}
		else if(strcmp($action, "expand_into_months")==0){
			foreach($periodyears as $year){
				if(date("Y", $year) == date("Y")){
					$limit_month = date("m");
				}else{
					$limit_month = 12;
				}
				for($i=1; $i<=$limit_month; $i++){
					$get_start_end = get_start_end($i, date("Y",$year));
					$fetch = fetch_data($get_start_end["first"], $get_start_end["last"]);
					$first[] = $get_start_end["first"];
					$last[] = $get_start_end["last"];
					$uptime[] = $fetch["up_percentage"];
					$downtime[] = $fetch["down_percentage"];
				}
			}
			print_summary($first, $last, $uptime, $downtime, "yearly", $id, $hosts_radio);
		}
	}
	else if(count($years)==1 && count($months)>1){
		$months = get_months($start, $end);
		foreach($months as $month){
			$current_month = date("m", $month);
			if(date("F Y", $month) == date("F Y", $start)){
				$fetch = fetch_data(mktime(date("H", $start),date("i", $start),date("s", $start), $current_month, date("d", $start) ,date("Y", $start)), mktime(date("H", $end),date("i", $end),date("s", $end), $current_month, date("t", $month),date("Y", $start)));
				$first[] = mktime(date("H", $start),date("i", $start),date("s", $start), $current_month, date("d", $start),date("Y", $start));
				$last[] = mktime(23,59,59, $current_month, date("t", $start),date("Y", $start));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}else if(date("F Y", $month) == date("F Y", $end)){
				$fetch = fetch_data(mktime(0,0,0, $current_month, 1,date("Y", $end)), mktime(date("H", $end),date("i", $end),date("s", $end), $current_month, date("d", $end),date("Y", $start)));
				$first[] = mktime(0,0,0, $current_month, 1,date("Y", $end));
				$last[] = mktime(date("H", $end),date("i", $end),date("s", $end), $current_month, date("d", $end),date("Y", $start));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
				$fetch = fetch_data(mktime(date("H", $start),date("i", $start),date("s", $start), $current_month, date("d", $start) ,date("Y", $start)), mktime(23,59,59, $current_month, date("t", $start),date("Y", $start)));
			}else{
				$fetch = fetch_data(mktime(0,0,0,$current_month, 1, date("Y", $month)), mktime(23,59,59, $current_month, date("t", $month), date("Y", $month)));
				$first[] = mktime(0,0,0,$current_month, 1, date("Y", $month));
				$last[] = mktime(23,59,59, $current_month, date("t", $month), date("Y", $month));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}
		}
		print_summary($first, $last, $uptime, $downtime, "monthly", $id, $hosts_radio);
	}else if(strcmp($action, "expand_into_days")==0){
		$days = get_days($start, $end);
		if(count($days)==1){
			$fetch = fetch_data(mktime(date("H", $start),date("i", $start),date("s", $start),date("m",$start),$date("d", $start), date("Y", $start)), mktime(date("H", $end),date("H", $end),date("H", $end),date("m",$end),$date("d", $end), date("Y", $end)));
			$first[] = mktime(date("H", $start),date("i", $start),date("s", $start),date("m",$start),$date("d", $start), date("Y", $start));
			$last[] = mktime(date("H", $end),date("H", $end),date("H", $end),date("m",$end),$date("d", $end), date("Y", $end));
			$uptime[] = $fetch["up_percentage"];
			$downtime[] = $fetch["down_percentage"];
		}
		foreach($days as $day){
			$current_day = date("d", $day);
			if(date("d F Y", $start) == date("d F Y", $day)){
				$fetch = fetch_data(mktime(0,0,0,date("m",$start),$current_day, date("Y", $start)), mktime(23,59,59,date("m",$start),$current_day, date("Y", $start)));
				$first[] = mktime(date("H", $start),date("i", $start),date("s", $start),date("m",$start),$current_day, date("Y", $start));
				$last[] = mktime(23,59,59,date("m",$start),$current_day, date("Y", $start));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}else if(date("d F Y", $end) == date("d F Y", $day)){
				$fetch = fetch_data(mktime(0,0,0,date("m",$end),$current_day, date("Y", $end)), mktime(date("H", $end),date("H", $end),date("H", $end),date("m",$end),$current_day, date("Y", $end)));
				$first[] = mktime(0,0,0,date("m",$end),$current_day, date("Y", $end));
				$last[] = mktime(date("H", $end),date("H", $end),date("H", $end),date("m",$end),$current_day, date("Y", $end));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}else{
				$fetch = fetch_data(mktime(0,0,0,date("m",$day),$current_day, date("Y", $day)), mktime(23,59,59,date("m",$day),$current_day, date("Y", $day)));
				$first[] = mktime(0,0,0,date("m",$day),$current_day, date("Y", $day));
				$last[] = mktime(23,59,59,date("m",$day),$current_day, date("Y", $day));
				$uptime[] = $fetch["up_percentage"];
				$downtime[] = $fetch["down_percentage"];
			}
		}
		print_summary($first, $last, $uptime, $downtime, "daily", $id, $hosts_radio);
	}else if(strcmp($action, "expand_into_hours")==0){
		if(date("d F Y", $start) == date("d F Y", $end)){
			$start_hour = date("G", $start);
			$limit_hour = date("G", $end);
		}else{
			$start_hour = 0;
			$limit_hour = date("G", $end);
		}
		$current_day = date("d",$start);
		for($i=$start_hour; $i<=$limit_hour; $i++){
			$fetch = fetch_data(mktime($i,0,0,date("m",$start),$current_day, date("Y", $start)), mktime($i,59,59,date("m",$start),$current_day, date("Y", $start)));
			$first[] = mktime($i,0,0,date("m",$start),$current_day, date("Y", $start));
			$last[] = mktime($i,59,59,date("m",$start),$current_day, date("Y", $start));
			$uptime[] = $fetch["up_percentage"];
			$downtime[] = $fetch["down_percentage"];
		}
		print_summary($first, $last, $uptime, $downtime, "hourly", $id, $hosts_radio);
	}else if(strcmp($action, "expand")==0 and count($months)==1){
		$days = get_days($start, $end);
		if(count($days) == 1){
			$fetch = fetch_data(mktime(date("H", $start), date("i", $start), date("s", $start), date("m", $start), date("d", $start), date("Y", $start)), mktime(date("H", $end), date("i", $end), date("s", $end), date("m", $end), date("d", $end), date("Y", $end)));
			$first[] = mktime(date("H", $start), date("i", $start), date("s", $start), date("m", $start), date("d", $start), date("Y", $start));
			$last[] = mktime(date("H", $end), date("i", $end), date("s", $end), date("m", $end), date("d", $end), date("Y", $end));
			$uptime[] = $fetch["up_percentage"];
			$downtime[] = $fetch["down_percentage"];
		}else{
			foreach($days as $day){
				$current_day = date("d", $day);
				if(date("d F Y", $start) == date("d F Y", $day)){
					$fetch = fetch_data(mktime(date("H", $start), date("i", $start), date("s", $start), date("m", $start), date("d", $start), date("Y", $start)), mktime(23,59,59, date("m", $start), date("d", $start), date("Y", $start)));
					$first[] = mktime(date("H", $start), date("i", $start), date("s", $start),date("m", $start), $current_day, date("Y", $start));
					$last[] = mktime(23,59,59,date("m", $start), $current_day, date("Y", $start));
					$uptime[] = $fetch["up_percentage"];
					$downtime[] = $fetch["down_percentage"];
				}else if(date("d F Y", $end) == date("d F Y", $day)){
					$fetch = fetch_data(mktime(0,0,0, date("m", $end), date("d", $end), date("Y", $end)), mktime(date("H", $end), date("i", $end), date("s", $end), date("m", $end), date("d", $end), date("Y", $end)));
					$first[] = mktime(0,0,0,date("m", $end), $current_day, date("Y", $end));
					$last[] = mktime(date("H", $end), date("i", $end), date("s", $end), date("m", $end), date("d", $end), date("Y", $end));
					$uptime[] = $fetch["up_percentage"];
					$downtime[] = $fetch["down_percentage"];
				}else{
					$fetch = fetch_data(mktime(0,0,0, date("m", $day),$current_day,date("Y", $day)), mktime(23,59,59, date("m", $day),$current_day,date("Y", $day)));
					$first[] = mktime(0,0,0, date("m", $day),$current_day,date("Y", $day));
					$last[] = mktime(23,59,59, date("m", $day),$current_day,date("Y", $day));
					$uptime[] = $fetch["up_percentage"];
					$downtime[] = $fetch["down_percentage"];
				}
			}
		}
		print_summary($first, $last, $uptime, $downtime, "daily", $id, $hosts_radio);
	}
}

/*
GIVES FIRST AND LAST TIMESTAMPS FOR GIVEN PERIOD, DOWN TO THE 'SECONDS' LEVEL
*/
function get_start_end($month, $year){
	$date_year = date("Y", $year);
	if($month>=1 && $month<=12){
		$first = mktime(0,0,0,$month,1, $date_year);
		$num_of_days = cal_days_in_month(CAL_GREGORIAN, $month, $date_year);
		$last = mktime(23,59,59,$month, $num_of_days,$date_year);
	}else if($month==0){
			$first = mktime(0,0,0,1,1,$date_year);
			$last = mktime(23,59,59,12,31,$date_year);
	}else if($month>12){
			$date_month = date("m", $month);
			$first = mktime(0,0,0,$date_month,1, $date_year);
			$num_of_days = date("t", $month);
			$last = mktime(23,59,59,$date_month, $num_of_days,$date_year);
	}
	
	$list = array("first"=>$first, "last"=>$last);
	return $list;
}

function print_summary($start, $end, $uptime, $downtime, $category, $selected_group, $hosts_radio){
	global $colors;
	if($hosts_radio==1){
		$data_source_path = $_GET["data_source_path"];
		$info = query_host_info($selected_group);
		display_custom_message("Single Host Mode", "warning");
	}else if($hosts_radio==2){
		$data_source_path = $_GET["data_source_path"];
		$info = query_cluster_description($selected_group);
		display_custom_message("Cluster Mode", "warning");
	}
	if(strcmp($category, "summary")==0){
		$date_format = "d F Y H:i";
		html_start_box("<strong>Summary for ".$info."</strong>", "100%", $colors["header"], "3", "center", "");
	}else{
		html_start_box('<strong>Expanded Report for '.$info.'</strong>', "100%", $colors["header"], "3", "center", "");
		if(strcmp($category, "yearly")==0){
			$date_format = "Y";
			$action = "expand_into_months";
		}else if(strcmp($category, "monthly")==0){
			$date_format = "M Y";
			$action = "expand_into_days";
		}else if(strcmp($category, "daily")==0){
			$date_format = "d M Y";
			$action = "expand_into_hours";
		}else if(strcmp($category, "hourly")==0){
			$date_format = "d M Y G:i";
		}
	}
	
	$display_text = array(
	"datetime_start" => array("Period starting at", "ASC"),
	"datetime_end" => array("Period ending at", "ASC"),
	"uptime" => array("Uptime Percentage", "ASC"),
	"downtime" => array("Downtime + Unreachability Percentage", "ASC"),
	"expand" => array("", "ASC"));
	
	html_header_sort($display_text, get_request_var_request(""), get_request_var_request(""), false);
	for($i=0; $i<count($start); $i++){
	echo'
		<form id="summary" action="" method="GET">';
		if(isset($action)){
			echo'
			<input type="hidden" id="action" name="action" value="'.$action.'" />
			';
		}else{
			echo'
			<input type="hidden" id="action" name="action" value="expand" />
			';
		}
			if( is_array($data_source_path) ){
				foreach($data_source_path as $data){
					echo'
					<input type="hidden" name="data_source_path[]" value="'.$data.'"/>
					';
				}
			}else{
				echo'
					<input type="hidden" name="data_source_path" value="'.$data_source_path.'"/>
				';
			}
			echo'
			<input type="hidden" name="selected_group" value="'.$selected_group.'"/>
			<input type="hidden" name="hosts_radio" value="'.$hosts_radio.'"/>
	';
	$j=0;
	form_alternate_row_color($colors["light"], $colors["alternate"], $j); $j++;
		$uptime_value = is_array($uptime) ? $uptime[$i] : $uptime;
		$downtime_value = is_array($downtime) ? $downtime[$i] : $downtime;
		$start_value = is_array($start) ? $start[$i] : $start;
		$end_value = is_array($end) ? $end[$i] : $end;
		print'
			<input type="hidden" name="datetime_start" value="'.$start_value.'"/>
			<input type="hidden" name="datetime_end" value="'.$end_value.'"/>
			<td style="margin: 10px;">
				'.date($date_format, $start_value).'
			</td>
			<td>
				'.date($date_format,$end_value).'
			</td>
			';
			echo'
			<td>
				'.round($uptime_value,2).'%
			</td>
			<td>
				'.round($downtime_value,2).'%
			</td>
			';
			if(($end_value - $start_value > (3600))){
				echo'<td>
						<input type="submit" value="Expand"/>
					</td>
				';
			}
			echo'
		</form>
		<div id="return"></div>
	';
	}
	html_end_box();
}

/*	calculate_availability - calculates the availability of the cluster
	@arg $array - array consisting of data to be evaluated
	@arg $type - cluster type (Series or Parallel)
	@returns - percentage availability
*/
function calculate_availability($array, $type, $start, $end, $step){
	if( empty($array) ){
		display_custom_message("No available data","error");
	}else if(count($array) > 1){
		$cluster_size = sizeof($array);
		$cluster_down=0;
		if(strcmp($type["type"], "S") == 0){
			for($i=$start; $i<=($end); $i+=$step){
				for($j=0; $j<count($array); $j++){
					if(!isset($array[$j][$i])){ //value check didn't occur
						$cluster_down++;
						break;
					}else{
						if($array[$j][$i] == 0){ //value check occurred, but value was zero
							$cluster_down++;
							break;
						}
					}
				}
			}
			$result = 100 - ($cluster_down *100 / ( ($i - $start)/$step ));
			return $result;
		}
		else if($type["type"] == "P"){
			for($i=$start; $i<$end; $i+=$step){
				$node_down = 0;
				for($j=0; $j<count($array); $j++){
					if(!isset($array[$j][$i])){ //value check didn't occur
						$node_down++;
					}else{
						if($array[$j][$i] == 0){ //value check occurred, but value was zero
							$node_down++;
						}
					}
				}
				if($node_down == $cluster_size){
					$cluster_down++;
				}
			}
			$result = 100 - (($cluster_down * 100) / ( ($i - $start)/$step ));
			return $result;
		}
	}
}

/*	query_hosts_from_cluster - takes a list of hosts that belong to a cluster
	@arg $id - Cluster ID
	@returns - the list of hosts associated to the cluster
*/
function query_hosts_from_cluster($id){
	$hosts = db_fetch_assoc("
	SELECT host_id AS id
	FROM hostgroup_host 
	WHERE hostgroup_id=$id");
	foreach($hosts as $host){
		$list[] = $host["id"];
	}
	return $list;
}

function query_number_of_hosts_from_cluster($id){
	$hosts = db_fetch_row("select count(host_id) from hostgroup_host where hostgroup_id=$id");
	return $hosts;
}
/*	query_cluster_type - checks the type of the cluster (S for Series, P for Parallel and M for Mixed)
	@arg $id - Cluster ID
	@returns - the type of the cluster
*/
function query_cluster_type($id){
	$type = db_fetch_row("
	SELECT type
	FROM hostgroup 
	WHERE id=$id");
	return $type;
}	

function query_data_sources($host_id, $filter){
	$data_sources = db_fetch_assoc("
	SELECT DISTINCT graph_templates_graph.title_cache, data_template_data.name_cache, data_template_data.data_source_path FROM (graph_templates_graph, data_local) INNER JOIN graph_templates_item ON graph_templates_item.local_graph_id = graph_templates_graph.local_graph_id INNER JOIN data_template_rrd ON data_template_rrd.id = graph_templates_item.task_item_id INNER JOIN data_template_data ON data_template_rrd.local_data_id = data_template_data.local_data_id INNER JOIN data_input_data ON data_input_data.data_template_data_id = data_template_data.id WHERE data_local.id = data_template_data.local_data_id AND data_local.host_id = $host_id  $filter ORDER BY name_cache");
	
	return $data_sources;
}

function number_of_sources($host_id){
	$number_of_sources = db_fetch_assoc("SELECT count(id) AS data_sources FROM data_local WHERE host_id = $host_id");
	return $number_of_sources["data_sources"];
}

function print_data_source_select($id){
	global $colors, $config;
	$uptime_filter = " AND (data_input_data.value LIKE '.1.3.6.1.2.1.25.1.1%' OR data_input_data.value LIKE '.1.3.6.1.2.1.1.3%') ";
	if( isset($_GET["hosts_radio"]) ){
		$hosts_radio = $_GET["hosts_radio"];
		if($hosts_radio == 1){
			$hasUptimeDataSources = true;
			$hasDataSources = false;
			$host_info = query_host_info($id);
			$selected_group = $_GET["host_id"];
			$data_sources = query_data_sources($id, $uptime_filter);
			if(count($data_sources) == 0){
				$hasUptimeDataSources = false;
				unset($data_sources);
				$data_sources = query_data_sources($id, "");
				if(count($data_sources) == 0){
					$hasDataSources = false;
				}else{
					$hasDataSources = true;
				}
			}else{
				$hasDataSources = true;
			}
			
			if( $hasDataSources == false ){
					display_custom_error_message("Host '".$host_info."' has no associated data sources. Cannot proceed.");
				echo "<input type='button' value='Back' onClick='history.go(-1);'>";
				break;
			}else{
				if( $hasDataSources == true && $hasUptimeDataSources == false){
					display_custom_error_message("Host '".$host_info."' has no associated Uptime data sources. It is not recommended to proceed.");
				}
			}
			
			html_start_box("Choose Data Source", "100%", $colors["header"], "0", "center", "");
			$display_text = array(
				"host" => array("Host", "ASC"),
				"data_source" => array("Data Source", "ASC"));
			echo"
			<form action='' name='data' method='GET'>
				<input type='hidden' name='hosts_radio' value='".$hosts_radio."'/>
				<input type='hidden' name='host_id' value='".$selected_group."'/>
			<tr>
				<td>
					<b>".$host_info."</b>
				</td>
				<td>
					<select name='data_source_path'>
					";
						foreach($data_sources as $data_source){
							$data_source_path = explode("<path_rra>", $data_source["data_source_path"]);
							
							echo"
								<option value='".$data_source_path[1] ."'>".$data_source["name_cache"]." (used in graph: ".$data_source["title_cache"].")</option>
							";
						}
					echo"
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<input type='submit' value='Go'/>
				</td>
			</tr>
		</form>
		";
		}else if($hosts_radio == 2){
			$selected_group = $_GET["hostgroup_id"];
			$count_no_uptime=0;
			for($i=0; $i<count($id); $i++){
				$data_sources[] = query_data_sources($id[$i], $uptime_filter);
			}
			
			$count_no_uptime=0;
			for($i=0; $i<count($data_sources); $i++){
				if(count($data_sources[$i]) == 0){
					$hasUptimeDataSources[] = false;
					$count_no_uptime++;
				}else{
					$hasUptimeDataSources[] = true;
				}
			}
			
			if($count_no_uptime > 0){
				for($i=0; $i<count($id); $i++){
					if($hasUptimeDataSources[$i] == false){
						unset($data_sources[$i]);
						$data_sources[$i] = query_data_sources($id[$i], "");
						$host_info = query_host_info($id[$i]);
					}
				}
			}
			ksort($data_sources);
			
			$count_no_data=0;
			for($i=0; $i<count($data_sources); $i++){
				if(count($data_sources[$i]) == 0){
					$hasDataSources[] = false;
					$count_no_data++;
				}else{
					$hasDataSources[] = true;
				}
			}
			
			if($count_no_data > 0){
				for($i=0; $i<count($id); $i++){
					if($hasDataSources[$i] == false){
						$host_info = query_host_info($id[$i]);
						display_custom_error_message("Host '".$host_info."' has no associated data sources. Cannot proceed.");
						echo "<input type='button' value='Back' onClick='history.go(-1);'>";
					}
				}
				break;
			}else{
				if($count_no_uptime > 0){
					for($i=0; $i<count($id); $i++){
						if($hasUptimeDataSources[$i] == false){
							$host_info = query_host_info($id[$i]);
							display_custom_error_message("Host '".$host_info."' has no associated Uptime data sources. It is not recommended proceed.");
						}
					}
				}
			}
			
			$cluster_info = query_cluster_description($selected_group);
			$display_text = array(
			"host" => array("Cluster", "ASC"),
			"data_source" => array("Data Source", "ASC"));
			html_start_box("<b>".$cluster_info."</b>", "100%", $colors["header"], "0", "center", "");
			html_header_sort($display_text, get_request_var_request(""), get_request_var_request(""), false);
			echo"
			<form action='' name='data' method='GET'>
				<input type='hidden' name='hosts_radio' value='".$hosts_radio."'/>
				<input type='hidden' name='hostgroup_id' value='".$selected_group."'/>
			";
			$cluster_info = query_cluster_description($selected_group);
			for($i=0; $i<count($id); $i++){
				$host_info = query_host_info($id[$i]);
				echo"
					<tr>
						<td>
							<b>".$host_info."</b>
						</td>
						<td>
							<select name='data_source_path[]'>"
							;
							for($j=0; $j<count($data_sources[$i]);$j++){
								$data_source_path = explode("<path_rra>", $data_sources[$i][$j]["data_source_path"]);
								echo"
									<option value='".$data_source_path[1] ."'>".$data_sources[$i][$j]["name_cache"]." (used in graph: ".$data_sources[$i][$j]["title_cache"].")</option>
								";
							}								
						echo"
							</select>
						</td>
					</tr>
				";
			}
			echo"
					<tr>
					<td>
						<input type='submit' value='Go'/>
						<input type='button' value='Back' onClick='history.go(-1);'>
					</td>
				</tr>
			</form>
			";
		}
	}
		

		$warning = false;
		if($hosts_radio == 1){
		}else if($hosts_radio == 2){
			
		}

}

function query_host_info($id){
	$host = db_fetch_row("
	SELECT CONCAT(hostname, ' (',description,')')
	AS name
	FROM host
	WHERE id=$id");
	return $host['name'];
}

/*	query_cluster_description - queries a cluster for its description
	@arg $id - Cluster ID
	@returns - a string composed of the description (index is "name")
*/
function query_cluster_description($id){
	$host = db_fetch_row("
	SELECT description AS name
	FROM hostgroup
	WHERE id=$id");
	return $host['name'];
}

function display_custom_message($message, $message_type){
	if($message_type == "error"){
		$color = "#FF0000";
	}else if($message_type == "warning"){
		$color = "#000000";
	}
	print "<div id='message' class='textInfo' style='margin-bottom:5px;padding:5px;color:$color;background-color:#FFFFFF;border:1px solid #BBBBBB;max-width:100%;position:relative;'>";
	print "$message";
	print "</div>";
}

function query_data_source_name_by_file($data_source_path){
	$result = db_fetch_assoc("SELECT name_cache FROM data_template_data WHERE name_cache = '<path_rra>/$data_source_path'");
	return $result["name_cache"];
}
function pre($input){
	echo "<pre>";
		print_r($input);
	echo "</pre>";
}
?>