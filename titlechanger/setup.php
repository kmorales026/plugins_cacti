<?php

function plugin_titlechanger_version()
{
        return array(   'name'          => 'titlechanger',
                        'version'       => '0.1',
                        'longname'      => 'System Title Changer',
                        'author'        => 'Howard Jones',
                        'homepage'      => 'http://wotsit.thingy.com/haj/cacti/titlechanger-plugin.html',
                        'email' 	=> 'howie@thingy.com',
                        'url'           => 'http://wotsit.thingy.com/haj/cacti/version.php'
                        );

}

function titlechanger_version()
{
	return(plugin_titlechanger_version());
}

function titlechanger_page_title($t)
{
        $newtitle = read_config_option("titlechanger_prefix");
	
	$t = preg_replace("/^Cacti/", $newtitle, $t);

	return($t);
}

function titlechanger_config_settings()
{

	global $tabs, $settings;
        $tabs["misc"] = "Misc";

        $temp = array(
                "titlechanger_header" => array(
                        "friendly_name" => "Cacti Page Title",
                        "method" => "spacer",
                ),
		"titlechanger_prefix" => array(
                        "friendly_name" => "Title Prefix",
                        "description" => "Change the page title prefix from 'Cacti' to this",
                        "method" => "textbox",
                        "default" => "Cacti",
                        "max_length" => "255",
                        "size" => "60"
                        ),
        );

      if (isset($settings["misc"]))
                $settings["misc"] = array_merge($settings["misc"], $temp);
        else
                $settings["misc"]=$temp;


}


function plugin_titlechanger_install()
{
        api_plugin_register_hook('titlechanger', 'page_title', 'titlechanger_page_title', "setup.php");
        api_plugin_register_hook('titlechanger', 'config_settings', 'titlechanger_config_settings', "setup.php");

	return(true);
}

?>
