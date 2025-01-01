<?php

	function plugin_capacityreport_install(){
		api_plugin_register_hook('capacityreport', 'draw_navigation_text', 'capacityreport_draw_navigation_text', 'setup.php');
		api_plugin_register_hook('capacityreport', 'top_header_tabs', 'capacityreport_show_tab', 'setup.php');
	}
	
	function capacityreport_show_tab(){
		global $config;
		print '<a href="' . $config['url_path'] . 'plugins/capacityreport/capacityreport.php"><img src="' . $config['url_path'] . 'plugins/capacityreport/images/tab.gif" align="absmiddle" border="0"></a>';
	}
	
	function plugin_capacityreport_uninstall(){
	}

	function plugin_capacityreport_check_config(){
		if(read_config_option('capacityreport') != ''){
			return true;
		}
		return true;
	}
	
	function capacityreport_draw_navigation_text($nav){
		$nav["capacityreport.php:"] = array(
			"title" => "Capacityreport",
			"mapping" => "capacityreport.php:",
			"url" => "capacityreport.php",
			"level" => "1"
		);
		return $nav;
	}
	
	function plugin_capacityreport_version(){
		return capacityreport_version();
	}
	
	function capacityreport_version () {
		return array('name' => 'capacityreport',
					'version' => '0.1',
					'longname' => 'CAPACITYREPORT',
					'author' => 'Victor Antunes',
					'homepage'	=> 'http://cacti.net',
					'email' => 'victor.antunes.ignacio@gmail.com',
					'url' => 'http://cacti.net/'
					);
	}
?>