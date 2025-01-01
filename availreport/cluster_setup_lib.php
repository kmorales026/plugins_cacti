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

function cluster_remove(){
	if(isset($_GET["id"])){
		$id = $_GET["id"];
		$return = db_execute("delete from hostgroup where id=$id");
		if($return == 1){
			raise_message(1);
		}else if($return == 2){
			raise_message(2);
		}
	}
	header("Location: cluster_setup.php");
}

function host_remove(){
	if(isset($_GET["id"]) && isset($_GET["host_id"])){
		$id = $_GET["id"];
		$host_id = $_GET["host_id"];
		$return = db_execute("delete from hostgroup_host where hostgroup_id=$id and host_id=$host_id");
		if($return == 1){
			raise_message(1);
		}else if($return == 2){
			raise_message(2);
		}
	}
	header("Location: cluster_setup.php?action=add&id=$id");
}

function form_save(){
	$array["id"] = $_POST["id"];
	$array["description"] = form_input_validate($_POST["description"], "description", "", false, 3);
	$array["type"] = form_input_validate($_POST["type"], "type", "", false, 3);
	
	if(!is_error_message()){
		$id = sql_save($array, "hostgroup");
		if ($id) {
			raise_message(1);
		}else{
			raise_message(2);
		}
	}
	header("Location: cluster_setup.php?action=add&id=$id");
}

function add_cluster(){
	global $colors;
	if(!empty($_GET["id"])){
		$get_id=$_GET["id"];
		$cluster = db_fetch_row("select * from hostgroup where id= ".$_GET["id"]."");
		$header_label = "[edit: " . htmlspecialchars($cluster["description"]) . "]";
		$save_status = "saved";
		if(!empty($_POST["assign_host"])){
			$assign_host = $_POST["assign_host"];
			settype($assign_host, 'integer');
			if($assign_host>0){
				db_execute("insert into hostgroup_host(hostgroup_id, host_id) values($get_id, $assign_host)");
				header("Location: cluster_setup.php?action=add&id=".$cluster["id"]."");
			}
		}
	}else{
		$save_status = "new";
		$header_label = "[New]";
	}
	html_start_box("<strong>Cluster</strong> " . $header_label . "", "100%", $colors["header"], "3", "center", "");
		
		$cluster_types = array(
			"S" => "Series",
			"P" => "Parallel",
			"M" => "Mixed"
		);
		
		$fields = array(
			"id" => array(
				"method" => "hidden_zero",
				"value" => (isset($cluster["id"]) ? $cluster["id"] : "|arg1:id|")
			),
			"description" => array(
				"method" => "textbox",
				"friendly_name" => "Description",
				"description" => "Enter a description to be assigned to the Cluster",
				"value" => (isset($cluster["description"]) ? $cluster["description"] : "|arg1:description|"),
				"max_length" => "80"
			),
			"type" => array(
				"method" => "drop_array",
				"friendly_name" => "Type",
				"description" => "Select the Cluster Type",
				"value" => (isset($cluster["type"]) ? $cluster["type"] : "|arg1:type|"),
				"default" => "S",
				"array" => $cluster_types
			)
		);
		
		draw_edit_form(array(
			"config" => array(),
			"fields" => inject_form_variables($fields, (isset($list) ? $list : array()))
			)
		);
		

		switch($save_status){
			case 'new':
				form_save_button("cluster_setup.php", "return");
				html_end_box();
				break;
			case 'saved':
				html_start_box("Currently Assigned Hosts", "100%", $colors["header"], "3", "center", "");
				$display_text = array(
					"description" => array("Description", "ASC"),
					"host_id" => array("Host ID", "ASC"),
					"graphs" => array("Graphs", "ASC"),
					"data_sources" => array("Data Sources", "ASC"),
					"status" => array("Status", "ASC"),
					"hostname" => array("Hostname", "ASC"),
					"availability" => array("Availability", "ASC"),
					" " => array("", "")
				);
				html_header_sort($display_text, get_request_var_request(""), get_request_var_request(""), false);
				$current_hosts = db_fetch_assoc("select hh.host_id host_id,
					h.hostname hostname, h.description description, h.status status, h.disabled disabled, h.availability availability
					from host h
					inner join hostgroup_host hh
					on hh.host_id = h.id
					where hh.hostgroup_id = ".$cluster["id"].""
				);
				$i=0;
				if(sizeof($current_hosts)>0){
					foreach($current_hosts as $current_host){
						$current_host_graphs = db_fetch_row("select count(id) count from graph_local where host_id=".$current_host["host_id"]."");
						$current_host_data_sources = db_fetch_row("select count(id) count from data_local where host_id=".$current_host["host_id"]."");
					form_alternate_row_color($colors["light"], $colors["alternate"], $i); $i++;
						echo"
							<td style='margin:10px;'>
								<strong><a href='" . htmlspecialchars("/host.php?action=edit&id=" . $current_host["host_id"]."") . "'>" .$current_host["description"]."</a></strong>
							</td>
							<td>
								".$current_host["host_id"]."
							</td>
							<td>
								".$current_host_graphs["count"]."
							</td>
							<td>
								".$current_host_data_sources["count"]."
							</td>
							<td>
								".get_colored_device_status(($current_host["disabled"] == "on" ? true : false), $current_host["status"])."
							</td>
							<td>
								".$current_host["hostname"]."
							</td>
							<td>
								".round($current_host["availability"],2)."
							</td>
							<td align='right' nowrap>
								<a href='".htmlspecialchars("cluster_setup.php?action=host_remove&id=" . $cluster["id"] . "&host_id=" . $current_host["host_id"])."'><img src='/images/delete_icon_large.gif' title='Remove Host from Cluster' alt='Remove Host from Cluste' border='0' align='middle'></a>
							</td>
						";
					}
				}else{
					form_alternate_row_color($colors['alternate'],$colors['light'],0);
					print '<td colspan=12><center>No Assigned Hosts</center></td></tr>';
				}
				form_save_button("cluster_setup.php", "return");
				html_end_box();
				html_start_box("Assign New Host", "100%", $colors["header"], "3", "center", "");
					$assign_hosts = db_fetch_assoc("select id, CONCAT(description, ' (',hostname,')') name 
						from host
						where disabled !='on'");
						/*and id not in
						(select host_id from hostgroup_host where hostgroup_id=".$cluster["id"].")");*/
					echo "
					<tr bgcolor='#".$colors['form_alternate1']."'>
						<td colspan='4'>
							<table cellspacing='0' cellpadding='1' width='100%'>
							<form name='assign_' id='asign' method='POST'>
								<td>Assign Host to Cluster:&nbsp;
									";
									form_dropdown("assign_host", $assign_hosts, "name", "id", "", "None", "0");
								echo"
								</td>
								<td align='right'>
									&nbsp;<input type='submit' value='Add' title='Assign Host to Cluster'>
								</td>
							</table>
							</form>
						</td>
					</tr>
					";
				html_end_box(false);
			break;
			html_end_box();
		}
}

function draw_cluster_init(){
	global $colors;
	html_start_box("<strong>Clusters</strong>", "100%", $colors["header"], "3", "center", "cluster_setup.php?action=add");
		$display_text = array(
			"description" => array("Description", "ASC"),
			"type" => array("Type", "ASC"),
			" " => array("", "")
		);
			
		html_header_sort($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);
		
		$clusters = get_cluster_list();
		
		$i=0;
		if(sizeof($clusters)>0){
			foreach($clusters as $cluster){
				switch($cluster["type"]){
					case "S":
						$cluster["type"] = "Series";
						break;
					case "P":
						$cluster["type"] = "Parallel";
						break;
					case "M":
						$cluster["type"] = "Mixed";
						break;
				}
				
				form_alternate_row_color($colors["light"], $colors["alternate"], $i); $i++;
				form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("cluster_setup.php?action=add&id=" . $cluster["id"]) . "'>" .$cluster["description"]."</a>", $cluster["id"]);
				form_selectable_cell($cluster["type"], $cluster["id"]);
				form_selectable_cell("<a href='".htmlspecialchars("cluster_setup.php?action=cluster_remove&id=".$cluster["id"]."")."'><img src='/images/delete_icon_large.gif' title='Remove Host from Cluster' alt='Remove Host from Cluste' border='0' align='right'></a>", $cluster["id"]);
			}
		}
		else{
			print "<tr><td style='padding: 4px; margin: 4px;' colspan=11><center>There are no Clusters to display!</center></td></tr>";
		}
	html_end_box();
}

function get_cluster_list(){
	$clusters = db_fetch_assoc("select id, description, type from hostgroup");
	return $clusters;
}

?>