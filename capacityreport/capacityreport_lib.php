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
	global $config;

	function print_graph_intel($i, $rrd, $div_name){
		$index=0;
		foreach($rrd as $display_section => $section_values){
			echo"
				<td width=100px>
			";
				foreach($section_values as $value){
					echo"
						<table>
							<td>";
							if($display_section != "keys"){ //actual values here
								echo "<div id='".$div_name."_".$i."&values_".$index."' name='values_".$index."'>".$value."</div>";
								$values[$i][] = $value;
								$index++;
							}else{ //data source names
								echo "<b>$value</b>";
							}
							echo"
							</td>
						</table>
					";
				}
			echo"
				</td>
			";
		}
		return $values[$i];
	}
	
	//the code may be ugly, but it prints out nicely. I don't even care lalala.
	function print_graph_info($graphs_id, $graphs_title, $start, $end, $hostname){
		global $config, $colors;
		html_start_box("Graph collection for <b>".$hostname."</b>", "100%", $colors["header"], "3", "center", "");
			$display_text = array(
				"start" => array("Starting Period", "ASC"),
				"end" => array("Ending Period", "ASC")
			);
			html_header_sort($display_text, get_request_var_request(""), get_request_var_request(""), false);
			echo"
				<td>
					&nbsp;".date('d F Y, H:i:s',$start)."&nbsp;
				</td>
				<td>
					&nbsp;".date('d F Y, H:i:s',$end)."&nbsp;
				</td>
			";
			
		html_end_box();
		for($i=0; $i<count($graphs_title); $i++){
			html_start_box("<b>$graphs_title[$i]</b>", "100%", $colors["header"], "1", "center", "");

			$rrd = read_rrd($graphs_id[$i], $start, $end);
			$display_text = array(
				"datasource" => array("Data Source", "ASC"),
				"first" => array("First", "ASC"),
				"min" => array("Minimum", "ASC"),
				"max" => array("Maximum", "ASC"),
				"avg" => array("Average", "ASC"),
				"last" => array("Last", "ASC"),
				"growth" => array("Growth", "ASC")
			);
			html_header_sort($display_text, get_request_var_request(""), get_request_var_request(""), false);
			
			$graph_values = print_graph_intel($i, $rrd, "graph");
			
			html_start_box("<b>Transform values</b>
				<select name='calculate' onchange='calculate(".json_encode($i).", ".json_encode(count($graphs_title)).",".json_encode($graph_values).", this)'>
					<option value='' disabled selected style='display:none;'>Select</option>
					<optgroup label='Default'>
						<option value='0'>Back to default values</option>
					</optgroup>
					<optgroup label='Automatic'>
						<option value='1'>Transform automatically based on value</option>
					</optgroup>
					<optgroup label='IEC to SI'>
						<option value='2'>IEC Binary to SI Decimal</option>
					</optgroup>
					<optgroup label='Divide'>
						<option value='3'>Kilo (Divide by 1.000)</option>
						<option value='4'>Mega (Divide by 1.000.000)</option>
						<option value='5'>Giga (Divide by 1.000.000.000)</option>
					</optgroup>
					<optgroup label='Multiply'>
						<option value='6'>Mili (Multiply by 1.000)</option>
						<option value='7'>Micro (Multiply by 1.000.000)</option>
						<option value='8'>Nano (Multiply by 1.000.000.000)</option>
					</optgroup>
					<optgroup label='Bytes vs Bits'>
						<option value='9'>Bytes to Bits</option>
						<option value='10'>Bits to Bytes</option>
					</optgroup>
				</select>", "100%", $colors["header_panel"], "0", "center", "");

			html_start_box("", "100%", $colors["panel"], "", "center", "");
			echo"
				<div align='center' style='margin-bottom:10px; margin-top:5px;'>
					<!--<a href='../../graph.php?action=view&local_graph_id=".$graphs_id[$i]."&rra_id=all'>-->
						<img border='0' alt='".$graphs_title[$i]."' src='../../graph_image.php?action=zoom&amp;local_graph_id=".$graphs_id[$i]."&amp;rra_id=0&amp;view_type=tree&amp;graph_start=".$start."&amp;graph_end=".$end."&amp;graph_height=120&amp;graph_width=500&amp;title_font_size=10' id='zoomGraphImage'>
					<!--</a>-->
				</div>

			";
		}
	}
	
	//this here thing basically deals with scientific notation. "+" is greater than zero, "-" is less than zero. Duh.
	//:P
	function get_multiplier($operand, $operator){
		switch($operator){
			case "+":
				$multiplier = pow(1, -$operand);

				return $multiplier;
				break;
			case "-":
				$multiplier = pow(1, $operand);

				return $multiplier;
				break;
		}
	}
	
	function read_rrd($graph_id, $start, $end){
		global $config;
		
		$xport_meta = array();
		$graph_data_array["graph_start"] = $start;
		$graph_data_array["graph_end"] = $end;
		$graph_data_array["graph_width"] = 500;
		$graph_data_array["graph_height"] = 120;
		$export_data = @rrdtool_function_xport($graph_id, 0, $graph_data_array, $xport_meta);
		
		//for arrays that have no data
		if(empty($export_data["data"]) || empty($export_data)){
			return array("keys" => array("NaN"), "first" => array(0), "min" => array(0), "max" => array(0), "avg" => array(0), "last" => array(0), "growth" => array(0));
		}

		//overriding default column names
		$export_keys = array_values($export_data["meta"]["legend"]);
		foreach($export_keys as $key){
			if( (preg_match("/col\d-cdef\w/", $key)) || (preg_match("/col\d/", $key))){
				$keys[] = "NaN";
			}else{
				$keys[] = $key;
			}
		}
		
		//organizing data into proper arrays
		foreach($export_data["data"] as $data_row){
			for($i=0; $i<$export_data["meta"]["columns"]; $i++){
				if( (strcmp($data_row["col".($i + 1).""], "NaN") == 0) || (strcmp($data_row["col".($i + 1).""], "inf") == 0) ){
					$data_fields[$i][] = "";
				}else{
					$operand =  substr($data_row["col".($i + 1).""], -2, 2); //POSITIVE OR NEGATIVE
					$operator = substr($data_row["col".($i + 1).""], -3, 1); //NUMBER
					$multiplier = get_multiplier($operand, $operator);
					if($data_row["col".($i + 1).""] < 1){
						$data_fields[$i][] = round(($data_row["col".($i + 1).""] * $multiplier), 6);
					}else{
						$data_fields[$i][] = round(($data_row["col".($i + 1).""] * $multiplier), 2);
					}
				}
			}
		}
		
		//for those arrays that contain only "NaN" and/or "inf"
		for($i=0; $i<count($data_fields); $i++){
			$filtered_array = array_filter($data_fields[$i], 'strlen');
			$nozero_array = array_filter($data_fields[$i]);
			//make sure the array has values in it
			if(count($filtered_array) > 0){
				/*for Minimum, there has to be at least two values in the array
				for First, the array can't be empty, and that's what happens sometimes
				*/
				if(count($nozero_array) > 1){
					$first[] = array_shift(array_values($nozero_array));
					$min[] = min($nozero_array);
				}else{
					$first[] = 0;
					$min[] = 0;
				}
				$max[] = max($filtered_array);
				$avg[] = round(avg($filtered_array),2);
				$last[] = end($filtered_array);
				$growth[] = ($last[$i] - $first[$i]);
			}else{ //array is empty
				$first[] = 0;
				$min[] = 0;
				$max[] = 0;
				$avg[] = 0;
				$last[] = 0;
				$growth[] = 0;
			}
		}
		
		$array = array("keys" => $keys, "first" => $first, "min" => $min, "max" => $max, "avg" => $avg, "last" => $last, "growth" => $growth);

		return $array;
	}

	function avg($input){
		$result = array_sum($input) / count($input);
		return $result;
	}
	
	function draw_init(){
		global $colors;
		html_start_box("Choose time period", "100%", $colors["header"], "0", "center", "");
		$hosts = db_fetch_assoc("select id, hostname, description from host where disabled!='on'");
		echo'
		<form action="" name="draw" method="POST" onsubmit="return validateCalendar()">
			<table width="100%" cellspacing="0" style="padding:0px;margin:0px;" bgcolor="#'.$colors["panel"].'">
				<tr>
					<td width="100">
						&nbsp;Start month&nbsp;
					</td>
					<td>
						<input type="text" id="start" name="start"/>
						
					</td>
				</tr>
				<tr>
					<td>
						&nbsp;End month&nbsp;
					</td>
					<td>
						<input type="text" name="end" id="end"/>
					</td>
				</tr>
		';
		html_start_box("Choose host", "100%", $colors["header"], "0", "center", "");
		echo'
				<tr>
					<td width="100">
						&nbsp;Host&nbsp;
					</td>
					<td>
						<select name="host_id" id="host_id">';
							foreach($hosts as $host){
								echo "<option value=".$host['id'].">".$host['hostname']." (".$host['description'].")</option>";
							}
							echo'
						</select>
					</td>
				</tr>
				<tr>
					<td width="50">
						<input type="submit" value="Go" />
					</td>
				</tr>
			</table>
		</form>
		';
	}
	
	function query_graphs($host_id){
		$graphs = db_fetch_assoc("select gtg.title_cache, gl.id from graph_local gl inner join host h on gl.host_id = h.id inner join graph_templates_graph gtg on gl.id = gtg.local_graph_id where h.id=$host_id order by title_cache");
		return $graphs;
	}
	
	function query_hosts($host_id){
		$host = db_fetch_assoc("select hostname, description from host where id=$host_id");
		return $host;
	}

	function show_graph_list($host_id, $start, $end){
		global $colors;
		$host = query_hosts($host_id);
		foreach($host as $host_desc){
			html_start_box("Available graphs for <b>".$host_desc['hostname']." (".$host_desc['description'].")</b>", "100%", $colors["header"], "0", "center", "");
			html_end_box();
		}
		$graphs = query_graphs($host_id);
		echo"
		<form name='graphs_form' action='' method='POST' onSubmit='return verifyCheckboxSelected(this.name)'>
			<input type='hidden' name='start' value='".$start."'/>
			<input type='hidden' name='end' value='".$end."'/>
			<input type='hidden' name='host_id' value='".$host_id."'/>
			<table width='100%' cellspacing='0' style='padding:0px;margin:0px;' bgcolor='#".$colors['panel']."'>
		";
		echo"
			<tr>
				<td>
					<input type='checkbox' id='graphs_select_all' onClick='selectAll(this)' />
				</td>
				<td>
					&nbsp;Select All&nbsp;
				</td>
			</tr>
		";
		foreach($graphs as $graph){
			echo"
				<tr>
					<td>
						<input type='checkbox' name='graphs[]' id='graphs[]' value='".$graph['id']."&".$graph['title_cache']."'/>
					</td>
					<td>
						&nbsp;".$graph['title_cache']." (".$graph['id'].")&nbsp;
					</td>
				</tr>
			";
		}
		echo"
			</table>
			<input type='submit' value='Go'/>
		</form>
		";
	}

	function pre($input){
		echo "<pre>";
			print_r($input);
		echo "</pre>";
	}
?>