<?php
function plugin_dashboard_version() { return array
(
    'name' => 'dashboard',
    'version' => '1.2',
    'longname' => 'Dashboard',
    'author' => 'Andre Luis',
    'homepage' => 'www.paxtecnologia.com.br',
    'email' => 'andre.luis@paxtecnologia.combr',
    'url' => 'www.paxtecnologia.com.br'
); }

function dashboard_version() { return (plugin_dashboard_version()); }

function dashboard_config_settings()
{
    global $tabs, $settings;
    $tabs["misc"] = "Misc";

    $temp = array (
        "dashboard_header" => array (
            "friendly_name" => "Dashboard",
            "method" => "spacer",
        ),
        "dashboard_pagestyle" => array (
            "friendly_name" => "Page style",
            "description" => "Where to display the Dashboard",
            "method" => "drop_array",
            "array" => array (
                0 => "Tab",
                1 => "Console Menu",
                2 => "Both Tab and Console Menu"
            )
        )
    );

    if (isset($settings["misc"])) {
        $settings["misc"] = array_merge($settings["misc"], $temp);
    } else {
        $settings["misc"] = $temp;
    }
}


function plugin_dashboard_install()
{
    api_plugin_register_hook('dashboard', 'top_header_tabs', 'dashboard_show_tab', "setup.php");
    api_plugin_register_hook('dashboard', 'top_graph_header_tabs', 'dashboard_show_tab', "setup.php");
    api_plugin_register_hook('dashboard', 'config_arrays', 'dashboard_config_arrays', "setup.php");
    api_plugin_register_hook('dashboard', 'config_settings', 'dashboard_config_settings', "setup.php");
    api_plugin_register_hook('dashboard', 'draw_navigation_text', 'dashboard_draw_navigation_text', "setup.php");
    api_plugin_register_hook('dashboard', 'graph_buttons', 'dashboard_graph_buttons', "setup.php");
    api_plugin_register_hook('dashboard', 'graph_buttonsgre', 'dashboard_graph_buttons', "setup.php");
    api_plugin_register_hook('dashboard', 'page_head', 'dashboard_page_head', "setup.php");

    return (true);
}

function dashboard_show_tab()
{
    global $config, $user_auth_realms, $user_auth_realm_filenames;

    $realm_id2 = 0;

        
    if (isset($user_auth_realm_filenames[basename('dashboard.php')]))
    {
        $realm_id2 = $user_auth_realm_filenames[basename('dashboard.php')];
    }

    $tabname = "tab_dashboard.gif";

    if (strstr($_SERVER['REQUEST_URI'], '/plugins\/dashboard\/dashboard.php/') != false)
    {
        $tabname = "tab_dashboard_red.gif";
    }

    if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='"
        . $_SESSION["sess_user_id"] . "' and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2)))
    {

        if(intval(read_config_option("dashboard_pagestyle")) != 1) {

        print '<a id="qt_link" href="' . $config['url_path']
            . 'plugins/dashboard/dashboard.php"><img class="qt_drophover" id="qt_tab" src="' . $config['url_path']
            . 'plugins/dashboard/images/';
        print $tabname;
        print '" alt="dashboard" align="absmiddle" border="0"></a>';
       }
    }

    dashboard_setup_table();
}

function dashboard_graph_buttons($data)
{
// $user = db_fetch_row("select * from user_auth where id=" .$_SESSION["sess_user_id"]. " and realm = 0");
// print "X";
// if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where user_id=" . $user["id"] . " and realm_id=212")) > 0)
// {	global $config;

    global $config, $user_auth_realms, $user_auth_realm_filenames;
    $realm_id2 = 0;

    if (isset($user_auth_realm_filenames[basename('dashboard.php')]))
    {
        $realm_id2 = $user_auth_realm_filenames[basename('dashboard_add_graph.php')];
    }

    if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='"
        . $_SESSION["sess_user_id"] . "' and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2)))
    {

        $local_graph_id = $data[1]['local_graph_id'];
        $rra_id = $data[1]['rra'];
	
        # print_r($data);
	
        print '<a title="Add this graph to Dashboard" href="' . $config['url_path']
	    . 'plugins/dashboard/dashboard_add_graph.php?rra_id=' . $rra_id . '&graph_id=' . $local_graph_id
#            . 'plugins/dashboard/dashboard.php?action=add&rra_id=' . $rra_id . '&graph_id=' . $local_graph_id . 'user_id=' . $user_id
            . '"><img src="' . $config['url_path']
            . 'plugins/dashboard/images/add.png" border="0" alt="Add this graph to Dashboard" style="padding: 3px;"></a><br>';
    }
}

function dashboard_config_arrays()
{
    global $user_auth_realms, $user_auth_realm_filenames, $menu;

    $user_auth_realms[213] = 'Plugin -> Dashboard: Access';
    $user_auth_realm_filenames['dashboard.php'] = 213;
	
    if(read_config_option("dashboard_pagestyle")>0) {
        $menu["Management"]['plugins/dashboard/dashboard.php'] = "Dashboard";
    }
    $user_auth_realms[214] = 'Plugin -> Dashboard: Edit';
    $user_auth_realm_filenames['dashboard_add_graph.php'] = 214;
    $user_auth_realm_filenames['dashboard_edit.php'] = 214;

#    if(read_config_option("dashboard_pagestyle")>0) {
        $menu["Management"]['plugins/dashboard/dashboard_edit.php'] = "Dashboard Edit";
 #   }
}

function dashboard_page_head()
{
    global $config;

   if( strstr($_SERVER['REQUEST_URI'], "dashboard.php") !== false) {
        print '<script type="text/javascript" src="' . $config['url_path']
            . 'plugins/dashboard/jquery-latest.min.js"></script>';
        print '<script type="text/javascript">jQuery.noConflict();</script>';
        # print '<script type="text/javascript" src="' . $config['url_path'] . 'plugins/dashboard/interface.js"></script>';
        print '<script type="text/javascript" src="' . $config['url_path'] . 'plugins/dashboard/dashboard.js"></script>';
        print '<link rel="stylesheet" href="' . $config['url_path'] . 'plugins/dashboard/dashboard.css"></link>';
   }
}

function dashboard_draw_navigation_text($nav)
{
    $nav["dashboard.php:"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    $nav["dashboard.php:add_ajax"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    $nav["dashboard.php:add"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    $nav["dashboard.php:remove"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    $nav["dashboard.php:save"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    $nav["dashboard.php:clear"] = array
    (
        "title" => "Dashboard",
        "mapping" => "index.php:",
        "url" => "dashboard.php",
        "level" => "1"
    );

    return ($nav);
}

function dashboard_setup_table()
{
    global $config, $database_default;
    include_once($config["library_path"] . DIRECTORY_SEPARATOR . "database.php");

    $sql = "show tables from " . $database_default;
    $result = db_fetch_assoc($sql) or die(mysql_error());

    $tables = array ();
    $sql = array ();

    foreach ($result as $index => $arr)
    {
        foreach ($arr as $t)
        {
            $tables[] = $t;
        }
    }

    if (!in_array('dashboard_graphs', $tables))
    {
        $sql[]
            = "CREATE TABLE dashboard_graphs (
                        id int(11) NOT NULL auto_increment,
                        userid int(11) NOT NULL,
  			local_graph_id mediumint(8) unsigned NOT NULL default '0',
  			rra_id smallint(8) unsigned NOT NULL default '0',
  			title varchar(255) default NULL,
                        PRIMARY KEY  (id)
                ) TYPE=MyISAM;";
    }

 $pagestyle = read_config_option("dashboard_pagestyle");

        if ($pagestyle == '' or $pagestyle < 0 or $pagestyle > 2) {
            $sql[] = "replace into settings values('dashboard_pagestyle',0)";
        }


    if (!empty($sql))
    {
        for ($a = 0; $a < count($sql); $a++)
        {
            $result = db_execute($sql[$a]);
        }
    }
}




function plugin_dashboard_upgrade () {
        /* Here we will upgrade to the newest version */
        dashboard_check_upgrade();
        return FALSE;
}

function plugin_dashboard_uninstall () {
        /* Do any extra Uninstall stuff here */
}

function plugin_dashboard_check_config () {
        /* Here we will check to ensure everything is configured */
        dashboard_check_upgrade();
        return TRUE;
}

function dashboard_check_upgrade () {
        global $config;

        $files = array('index.php', 'plugins.php');
        if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
                return;
        }

        $current = plugin_dashboard_version();
        $current = $current['version'];
        $old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='dashboard'");
        if (sizeof($old) && $current != $old["version"]) {
                /* if the plugin is installed and/or active */
                if ($old["status"] == 1 || $old["status"] == 4) {
                        /* re-register the hooks */
                        plugin_dashboard_install();

                        /* perform a database upgrade */
                        dashboard_database_upgrade();
                }

                /* update the plugin information */
                $info = plugin_dashboard_version();
                $id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='dashboard'");
                db_execute("UPDATE plugin_config
                        SET name='" . $info["longname"] . "',
                        author='"   . $info["author"]   . "',
                        webpage='"  . $info["homepage"] . "',
                        version='"  . $info["version"]  . "'
                        WHERE id='$id'");
        }
}

function dashboard_database_upgrade() {
        global $plugins, $config;
        return TRUE;
}


// vim:ts=4:sw=4:
?>
