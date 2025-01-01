<?php

	include_once($config['library_path'] . '/database.php');
	
	function plugin_availreport_install(){
		api_plugin_register_hook('availreport', 'draw_navigation_text', 'availreport_draw_navigation_text', 'setup.php');
		api_plugin_register_hook('availreport', 'config_arrays', 'availreport_config_arrays', 'setup.php');
		api_plugin_register_hook('availreport', 'top_header_tabs', 'availreport_show_tab', 'setup.php');
		availreport_setup_table();
	}
	
	function table_exists($table) {
		return sizeof(db_fetch_assoc("SHOW TABLES LIKE '$table'"));
	}
	
	function availreport_setup_table(){
		global $config;
		
		//Cluster management tables
		if(!table_exists("hostgroup")){
			db_execute('CREATE TABLE hostgroup(`id` mediumint(8) unsigned NOT NULL auto_increment, `description` varchar(255) NOT NULL default "", `type` enum("S", "P") NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM;');
		}
		
		if(!table_exists("hostgroup_host")){
			db_execute('CREATE TABLE hostgroup_host(`hostgroup_id` mediumint(8) unsigned NOT NULL, `host_id` mediumint(8) unsigned NOT NULL) ENGINE=MyISAM;');
			
		}
	}
	
	function availreport_draw_navigation_text($nav){
		 $nav["availreport.php:"] = array(
			"title" => "Summary",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["availreport.php:expand"] = array(
			"title" => "Expand",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["availreport.php:expand_into_months"] = array(
			"title" => "Expand into months",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["availreport.php:expand_into_days"] = array(
			"title" => "Expand into days",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["availreport.php:expand_into_hours"] = array(
			"title" => "Expand into hours",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["availreport.php:max_zoom"] = array(
			"title" => "Maximum zoom",
			"mapping" => "availreport.php:",
			"url" => "availreport.php",
			"level" => "1"
		);
		$nav["cluster_setup.php:"] = array(
			"title" => "Cluster Management",
			"mapping" => "cluster_setup.php:",
			"url" => "cluster_setup.php",
			"level" => "1"
		);
		$nav["cluster_setup.php:add"] = array(
			"title" => "Add",
			"mapping" => "cluster_setup.php:",
			"url" => "cluster_setup.php",
			"level" => "1"
		);
		return $nav;
	}
	function availreport_show_tab(){
		global $config;
		print '<a href="' . $config['url_path'] . 'plugins/availreport/availreport.php"><img src="' . $config['url_path'] . 'plugins/availreport/images/tab.gif" align="absmiddle" border="0"></a>';
	}
	
	function availreport_config_arrays () {
		global $user_auth_realms, $user_auth_realm_filenames, $menu;
		$menu["Management"]['plugins/availreport/cluster_setup.php']  = "Cluster Management";
	}
	function plugin_availreport_uninstall(){
	}

	function plugin_availreport_check_config(){
		//if(read_config_option('availreport') != ''){
		//	return true;
		//}
		return true;
	}
	
	function plugin_availreport_version(){
		availreport_version();
	}
	
	function availreport_version () {
		return array("name" => "availreport",
					"version" => "0.1",
					"longname" => "AVAILABILITYREPORT",
					"author" => "Victor Antunes",
					"homepage"	=> "http://cacti.net",
					"email" => "imexy@live.com",
					"url" => "http://cacti.net/"
					);
	}
?>