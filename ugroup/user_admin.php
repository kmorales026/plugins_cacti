<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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

chdir("../..");
include("./include/auth.php");

define("MAX_DISPLAY_PAGES", 21);

$user_actions = array(
	1 => "Delete",
	2 => "Copy",
	3 => "Enable",
	4 => "Disable",
	5 => "Batch Copy"
);

if (isset($_POST['update_policy'])) {
	update_policies();
}else{
	switch (get_request_var_request("action")) {
	case 'actions':
		form_actions();
		break;

	case 'save':
		form_save();
		break;

	case 'perm_remove':
		perm_remove();
		break;

	case 'user_edit':
		include_once("include/top_header.php");
		user_edit();
		include_once("include/bottom_footer.php");
		break;

	default:
		if (!api_plugin_hook_function('user_admin_action', get_request_var_request("action"))) {
			include_once("include/top_header.php");
			user();
			include_once("include/bottom_footer.php");
		}
		break;
	}
}

/* --------------------------
    Actions Function
   -------------------------- */

function update_policies() {
	$set = '';

	$set .= isset($_POST["policy_graphs"]) ? "policy_graphs=" . get_request_var_post("policy_graphs"):"";
	$set .= isset($_POST["policy_trees"]) ? (strlen($set) ? ",":"") . "policy_trees=" . get_request_var_post("policy_trees"):"";
	$set .= isset($_POST["policy_hosts"]) ? (strlen($set) ? ",":"") . "policy_hosts=" . get_request_var_post("policy_hosts"):"";
	$set .= isset($_POST["policy_graph_templates"]) ? (strlen($set) ? ",":"") . "policy_graph_templates=" . get_request_var_post("policy_graph_templates"):"";

	if (strlen($set)) {
		db_execute("UPDATE user_auth SET $set WHERE id = " . get_request_var_post("id"));
	}

	header("Location: user_admin.php?action=user_edit&tab=" .  get_request_var_post("tab") . "&id=" . get_request_var_post("id"));
	exit;
}

function form_actions() {
	global $colors, $user_actions, $auth_realms;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["associate_host"])) {
		while (list($var,$val) = each($_POST)) {
			if (ereg("^chk_([0-9]+)$", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				if (get_request_var_post("drp_action") == "1") {
					db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . $matches[1] . ",3)");
				}else{
					db_execute("DELETE FROM user_auth_perms WHERE user_id=" . get_request_var_post("id") . " AND item_id=" . $matches[1] . " AND type=3");
				}
			}
		}

		header("Location: user_admin.php?action=user_edit&tab=permsd&id=" . get_request_var_post("id"));
		exit;
	}elseif (isset($_POST["associate_graph"])) {
		while (list($var,$val) = each($_POST)) {
			if (ereg("^chk_([0-9]+)$", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				if (get_request_var_post("drp_action") == "1") {
					db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . $matches[1] . ",1)");
				}else{
					db_execute("DELETE FROM user_auth_perms WHERE user_id=" . get_request_var_post("id") . " AND item_id=" . $matches[1] . " AND type=1");
				}
			}
		}

		header("Location: user_admin.php?action=user_edit&tab=permsg&id=" . get_request_var_post("id"));
		exit;
	}elseif (isset($_POST["associate_template"])) {
		while (list($var,$val) = each($_POST)) {
			if (ereg("^chk_([0-9]+)$", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				if (get_request_var_post("drp_action") == "1") {
					db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . $matches[1] . ",4)");
				}else{
					db_execute("DELETE FROM user_auth_perms WHERE user_id=" . get_request_var_post("id") . " AND item_id=" . $matches[1] . " AND type=4");
				}
			}
		}

		header("Location: user_admin.php?action=user_edit&tab=permste&id=" . get_request_var_post("id"));
		exit;
	}elseif (isset($_POST["associate_tree"])) {
		while (list($var,$val) = each($_POST)) {
			if (ereg("^chk_([0-9]+)$", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				if (get_request_var_post("drp_action") == "1") {
					db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . $matches[1] . ",2)");
				}else{
					db_execute("DELETE FROM user_auth_perms WHERE user_id=" . get_request_var_post("id") . " AND item_id=" . $matches[1] . " AND type=2");
				}
			}
		}

		header("Location: user_admin.php?action=user_edit&tab=permstr&id=" . get_request_var_post("id"));
		exit;
	}elseif (isset($_POST["selected_items"])) {
		if (get_request_var_post("drp_action") != "2") {
			$selected_items = unserialize(stripslashes(get_request_var_post("selected_items")));
		}

		if (get_request_var_post("drp_action") == "1") { /* delete */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_remove($selected_items[$i]);

				api_plugin_hook_function('user_remove', $selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") == "2") { /* copy */
			/* ================= input validation ================= */
			input_validate_input_number(get_request_var_post("selected_items"));
			input_validate_input_number(get_request_var_post("new_realm"));
			/* ==================================================== */

			$new_username = get_request_var_post("new_username");
			$new_realm = get_request_var_post("new_realm", 0);
			$template_user = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . get_request_var_post("selected_items"));
			$overwrite = array( "full_name" => get_request_var_post("new_fullname") );

			if (strlen($new_username)) {
				if (sizeof(db_fetch_assoc("SELECT username FROM user_auth WHERE username = '" . $new_username . "' AND realm = " . $new_realm))) {
					raise_message(19);
				} else {
					if (user_copy($template_user["username"], $new_username, $template_user["realm"], $new_realm, false, $overwrite) === false) {
						raise_message(2);
					} else {
						raise_message(1);
					}
				}
			}
		}

		if (get_request_var_post("drp_action") == "3") { /* enable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_enable($selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") == "4") { /* disable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_disable($selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") == "5") { /* batch copy */
			/* ================= input validation ================= */
			input_validate_input_number(get_request_var_post("template_user"));
			/* ==================================================== */

			$copy_error = false;
			$template = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . get_request_var_post("template_user"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$user = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . $selected_items[$i]);
				if ((isset($user)) && (isset($template))) {
					if (user_copy($template["username"], $user["username"], $template["realm"], $user["realm"], true) === false) {
						$copy_error = true;
					}
				}
			}
			if ($copy_error) {
				raise_message(2);
			} else {
				raise_message(1);
			}
		}

		header("Location: user_admin.php");
		exit;
	}

	/* loop through each of the users and process them */
	$user_list = "";
	$user_array = array();
	$i = 0;
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			if (get_request_var_post("drp_action") != "2") {
				$user_list .= "<li>" . db_fetch_cell("SELECT username FROM user_auth WHERE id=" . $matches[1]) . "<br>";
			}
			$user_array[$i] = $matches[1];

			$i++;
		}
	}

	/* Check for deleting of Graph Export User */
	if ((get_request_var_post("drp_action") == "1") && isset($user_array) && sizeof($user_array)) { /* delete */
		$exportuser = read_config_option('export_user_id');
		if (in_array($exportuser, $user_array)) {
			raise_message(22);
			header("Location: user_admin.php");
			exit;
		}
	}

	include_once("./include/top_header.php");

	html_start_box("<strong>" . $user_actions[get_request_var_post("drp_action")] . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	print "<form action='user_admin.php' method='post'>\n";

	if (isset($user_array) && sizeof($user_array)) {
		if ((get_request_var_post("drp_action") == "1") && (sizeof($user_array))) { /* delete */
			print "
				<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						<p>When you click \"Continue\", the selected User(s) will be deleted.</p>
						<p><ul>$user_list</ul></p>
					</td>
				</tr>\n";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete User(s)'>";
		}
		$user_id = "";

		if ((get_request_var_post("drp_action") == "2") && (sizeof($user_array))) { /* copy */
			$user_id = $user_array[0];
			$user_realm = db_fetch_cell("SELECT realm FROM user_auth WHERE id = " . $user_id);

			print "
				<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						When you click \"Continue\" the selected User will be copied to the new User below<br><br>
					</td>
				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						Template Username: <i>" . db_fetch_cell("SELECT username FROM user_auth WHERE id=" . $user_id) . "</i>
					</td>
				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
					New Username: ";
			print form_text_box("new_username", "", "", 25);
			print "				</td>
				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						New Full Name: ";
			print form_text_box("new_fullname", "", "", 35);
			print "				</td>
				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						New Realm: \n";
			print form_dropdown("new_realm", $auth_realms, "", "", $user_realm, "", 0);
			print "				</td>

				</tr>\n";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Copy User'>";
		}

		if ((get_request_var_post("drp_action") == "3") && (sizeof($user_array))) { /* enable */
			print "
				<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						<p>When you click \"Continue\" the selected User(s) will be enabled.</p>
						<p><ul>$user_list</ul></p>
					</td>
				</tr>\n";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Enable User(s)'>";
		}

		if ((get_request_var_post("drp_action") == "4") && (sizeof($user_array))) { /* disable */
			print "
				<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						<p>When you click \"Continue\" the selected User(s) will be disabled.</p>
						<p><ul>$user_list</ul></p>
					</td>
				</tr>\n";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Disable User(s)'>";
		}

		if ((get_request_var_post("drp_action") == "5") && (sizeof($user_array))) { /* batch copy */
			$usernames = db_fetch_assoc("SELECT id,username FROM user_auth WHERE realm = 0 ORDER BY username");
			print "
				<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>When you click \"Continue\" you will overwrite selected the User(s) settings with the selected template User settings and permissions?  Original user Full Name, Password, Realm and Enable status will be retained, all other fields will be overwritten from Template User.<br><br></td>
				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						Template User: \n";
			print form_dropdown("template_user", $usernames, "username", "id", "", "", 0);
			print "		</td>

				</tr><tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"] . "'>
						<p>User(s) to update:
						<ul>$user_list</ul></p>
					</td>
				</tr>\n";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Reset User(s) Settings'>";
		}
	}else{
		print "<tr><td bgcolor='#" . $colors["form_alternate1"]. "'><span class='textError'>You must select at least one user.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print " <tr>
			<td align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='action' value='actions'>";
	if (get_request_var_post("drp_action") == "2") { /* copy */
		print "				<input type='hidden' name='selected_items' value='" . $user_id . "'>\n";
	}else{
		print "				<input type='hidden' name='selected_items' value='" . (isset($user_array) ? serialize($user_array) : '') . "'>\n";
	}
	print "				<input type='hidden' name='drp_action' value='" . get_request_var_post("drp_action") . "'>
				$save_html
			</td>
		</tr>
		";

	html_end_box();

	include_once("./include/bottom_footer.php");
}

/* --------------------------
    Save Function
   -------------------------- */

function form_save() {
	global $settings_graphs;

	/* graph permissions */
	if ((isset($_POST["save_component_graph_perms"])) && (!is_error_message())) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("perm_graphs"));
		input_validate_input_number(get_request_var_post("perm_trees"));
		input_validate_input_number(get_request_var_post("perm_hosts"));
		input_validate_input_number(get_request_var_post("perm_graph_templates"));
		input_validate_input_number(get_request_var_post("policy_graphs"));
		input_validate_input_number(get_request_var_post("policy_trees"));
		input_validate_input_number(get_request_var_post("policy_hosts"));
		input_validate_input_number(get_request_var_post("policy_graph_templates"));
		/* ==================================================== */

		$add_button_clicked = false;

		if (isset($_POST["add_graph_x"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_graphs") . ",1)");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_tree_x"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_trees") . ",2)");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_host_x"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_hosts") . ",3)");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_graph_template_x"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_graph_templates") . ",4)");
			$add_button_clicked = true;
		}

		if ($add_button_clicked == true) {
			header("Location: user_admin.php?action=user_edit&tab=graph_perms_edit&id=" . get_request_var_post("id"));
			exit;
		}
	}

	/* user management save */
	if (isset($_POST["save_component_user"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("realm"));
		/* ==================================================== */

		if ((get_request_var_post("password") == "") && (get_request_var_post("password_confirm") == "")) {
			$password = db_fetch_cell("SELECT password FROM user_auth WHERE id = " . get_request_var_post("id"));
		}else{
			$password = md5(get_request_var_post("password"));
		}

		/* check duplicate username */
		if (sizeof(db_fetch_row("select * from user_auth where realm = " . get_request_var_post("realm") . " and username = '" . get_request_var_post("username") . "' and id != " . get_request_var_post("id")))) {
			raise_message(12);
		}

		/* check for guest or template user */
		$username = db_fetch_cell("select username from user_auth where id = " . get_request_var_post("id"));
		if ($username != get_request_var_post("username")) {
			if ($username == read_config_option("user_template")) {
				raise_message(20);
			}
			if ($username == read_config_option("guest_user")) {
				raise_message(20);
			}
		}

		/* check to make sure the passwords match; if not error */
		if (get_request_var_post("password") != get_request_var_post("password_confirm")) {
			raise_message(4);
		}

		form_input_validate(get_request_var_post("password"), "password", "" . preg_quote(get_request_var_post("password_confirm")) . "", true, 4);
		form_input_validate(get_request_var_post("password_confirm"), "password_confirm", "" . preg_quote(get_request_var_post("password")) . "", true, 4);

		$save["id"] = get_request_var_post("id");
		$save["username"] = form_input_validate(get_request_var_post("username"), "username", "^[A-Za-z0-9\._\\\@\ -]+$", false, 3);
		$save["full_name"] = form_input_validate(get_request_var_post("full_name"), "full_name", "", true, 3);
		$save["password"] = $password;
		$save["must_change_password"] = form_input_validate(get_request_var_post("must_change_password", ""), "must_change_password", "", true, 3);
		$save["show_tree"] = form_input_validate(get_request_var_post("show_tree", ""), "show_tree", "", true, 3);
		$save["show_list"] = form_input_validate(get_request_var_post("show_list", ""), "show_list", "", true, 3);
		$save["show_preview"] = form_input_validate(get_request_var_post("show_preview", ""), "show_preview", "", true, 3);
		$save["graph_settings"] = form_input_validate(get_request_var_post("graph_settings", ""), "graph_settings", "", true, 3);
		$save["login_opts"] = form_input_validate(get_request_var_post("login_opts"), "login_opts", "", true, 3);
		$save["realm"] = get_request_var_post("realm", 0);
		$save["enabled"] = form_input_validate(get_request_var_post("enabled", ""), "enabled", "", true, 3);
		$save = api_plugin_hook_function('user_admin_setup_sql_save', $save);

		if (!is_error_message()) {
			$user_id = sql_save($save, "user_auth");

			if ($user_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}
	}elseif (isset($_POST["save_component_realm_perms"])) {
		db_execute("DELETE FROM user_auth_realm WHERE user_id = " . get_request_var_post("id"));

		while (list($var, $val) = each($_POST)) {
			if (preg_match("/^[section]/i", $var)) {
				if (substr($var, 0, 7) == "section") {
					db_execute("REPLACE INTO user_auth_realm (user_id,realm_id) VALUES (" . get_request_var_post("id") . "," . substr($var, 7) . ")");
				}
			}
		}

		raise_message(1);
	}elseif (isset($_POST["save_component_graph_settings"])) {
		while (list($tab_short_name, $tab_fields) = each($settings_graphs)) {
			while (list($field_name, $field_array) = each($tab_fields)) {
				if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
					while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
						db_execute("REPLACE INTO settings_graphs (user_id,name,value) VALUES (" . (!empty($user_id) ? $user_id : get_request_var_post("id")) . ",'$sub_field_name', '" . get_request_var_post($sub_field_name, "") . "')");
					}
				}else{
					db_execute("REPLACE INTO settings_graphs (user_id,name,value) VALUES (" . (!empty($user_id) ? $user_id : $_POST["id"]) . ",'$field_name', '" . get_request_var_post($field_name) . "')");
				}
			}
		}

		/* reset local settings cache so the user sees the new settings */
		kill_session_var("sess_graph_config_array");

		raise_message(1);
	}elseif (isset($_POST["save_component_graph_perms"])) {
		db_execute("UPDATE user_auth SET
			policy_graphs = " . get_request_var_post("policy_graphs") . ",
			policy_trees = " . get_request_var_post("policy_trees") . ",
			policy_hosts = " . get_request_var_post("policy_hosts") . ",
			policy_graph_templates = " . get_request_var_post("policy_graph_templates") . "
			WHERE id = " . get_request_var_post("id"));
	} else {
		api_plugin_hook('user_admin_user_save');
	}

	/* redirect to the appropriate page */
	header("Location: user_admin.php?action=user_edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id));
}

/* --------------------------
    Graph Permissions
   -------------------------- */

function perm_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	input_validate_input_number(get_request_var_request("user_id"));
	/* ==================================================== */

	if (get_request_var_request("type") == "graph") {
		db_execute("DELETE FROM user_auth_perms WHERE type = 1 AND user_id = " . get_request_var_request("user_id") . " AND item_id = " . get_request_var_request("id"));
	}elseif (get_request_var_request("type") == "tree") {
		db_execute("DELETE FROM user_auth_perms WHERE type = 2 AND user_id = " . get_request_var_request("user_id") . " AND item_id = " . get_request_var_request("id"));
	}elseif (get_request_var_request("type") == "host") {
		db_execute("DELETE FROM user_auth_perms WHERE type = 3 AND user_id = " . get_request_var_request("user_id") . " AND item_id = " . get_request_var_request("id"));
	}elseif (get_request_var_request("type") == "graph_template") {
		db_execute("DELETE FROM user_auth_perms WHERE type = 4 AND user_id=" . get_request_var_request("user_id") . " and item_id = " . get_request_var_request("id"));
	}

	header("Location: user_admin.php?action=user_edit&tab=graph_perms_edit&id=" . get_request_var_request("user_id"));
}

function graph_perms_edit($tab, $header_label) {
	global $colors;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	/* ==================================================== */

	$policy_array = array(
		1 => "Allow",
		2 => "Deny");

	if (!empty($_REQUEST["id"])) {
		$policy = db_fetch_row("SELECT policy_graphs,policy_trees,policy_hosts,policy_graph_templates 
			FROM user_auth 
			WHERE id=" . get_request_var_request("id"));
	} else {
		$policy = array(
			'policy_graphs' => '1',
			'policy_trees'  => '1',
			'policy_hosts'  => '1',
			'policy_graph_templates' => '1'
		);
	}

	switch($tab) {
	case 'permsg':
		process_graph_request_vars();

		graph_filter($header_label);

		/* print checkbox form for validation */
		print "<form name='policy' method='post' action='user_admin.php'>\n";

		/* box: device permissions */
		html_start_box("<strong>Default Policy</strong>", "100%", $colors["header"], "3", "center", "");

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td><table cellspacing="0" cellpadding="2"><tr>
			<td style="white-space:nowrap;" width="120">The Default Allow/Deny Graph Policy for this User.</td>
			<td width="10"> 
				<?php form_dropdown("policy_graphs",$policy_array,"","",$policy["policy_graphs"],"",""); ?>
			</td>
			<td>
				<input type="submit" name="update_policy" value="Update">
				<input type="hidden" name="tab" value="<?php print $tab;?>">
				<input type="hidden" name="id" value="<?php print get_request_var_request("id");?>">
			</td>
			</tr></table></td>
		</tr>
		<?php

		html_end_box();

		print "</form>\n";

		/* if the number of rows is -1, set it to the default */
		if ($_REQUEST["rows"] == -1) {
			$rows = 15;
		}else{
			$rows = $_REQUEST["rows"];
		}

		/* form the 'where' clause for our main sql query */
		if (strlen(get_request_var_request("filter"))) {
			$sql_where = "WHERE (gtg.title_cache LIKE '%%" . get_request_var_request("filter") . "%%' AND gtg.local_graph_id>0)";
		} else {
			$sql_where = "WHERE (gtg.local_graph_id>0)";
		}

		if (get_request_var_request("graph_template_id") == "-1") {
			/* Show all items */
		}elseif (get_request_var_request("graph_template_id") == "0") {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " gtg.graph_template_id=0";
		}elseif (!empty($_REQUEST["graph_template_id"])) {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " gtg.graph_template_id=" . get_request_var_request("graph_template_id");
		}

		if (get_request_var_request("associated") == "false") {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " (user_auth_perms.type=1 AND user_auth_perms.user_id=" . get_request_var_request("id", 0) . ")";
		}

		/* print checkbox form for validation */
		print "<form name='chk' method='post' action='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permsg&id=" . get_request_var_request("id")) . "'>\n";

		html_start_box("", "100%", $colors["header"], "3", "center", "");

		$total_rows = db_fetch_cell("select
			COUNT(gtg.id)
			FROM graph_templates_graph AS gtg
			LEFT JOIN user_auth_perms 
			ON (gtg.local_graph_id=user_auth_perms.item_id AND user_auth_perms.type=1)
			$sql_where");

		$sql_query = "SELECT gtg.local_graph_id, gtg.title_cache, user_auth_perms.user_id
			FROM graph_templates_graph AS gtg
			LEFT JOIN user_auth_perms 
			ON (gtg.local_graph_id=user_auth_perms.item_id AND user_auth_perms.type=1)
			$sql_where 
			ORDER BY title_cache
			LIMIT " . ($rows*(get_request_var_request("page")-1)) . "," . $rows;

		$graphs = db_fetch_assoc($sql_query);

		/* generate page list */
		if ($total_rows > 0) {
			$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $rows, $total_rows, "user_admin.php?action=user_edit&tab=permsg&id=" . get_request_var_request('id'));
	
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
							<td align='left' class='textHeaderDark'>
									<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permsg&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
								</td>\n
								<td align='center' class='textHeaderDark'>
									Showing Rows " . (($rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $rows) || ($total_rows < ($rows*get_request_var_request("page")))) ? $total_rows : ($rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
								</td>\n
								<td align='right' class='textHeaderDark'>
											<strong>"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tabe=permsg&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}else{
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
								<td align='center' class='textHeaderDark'>
									No Rows Found
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}
	
		print $nav;

		$display_text = array("Graph Title", "ID", "Effective Policy");

		html_header_checkbox($display_text, false);

		$i = 0;
		if (sizeof($graphs)) {
			foreach ($graphs as $g) {
				form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $g["local_graph_id"]); $i++;
				form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($g["title_cache"])) : htmlspecialchars($g["title_cache"])), $g["local_graph_id"], 250);
				form_selectable_cell($g["local_graph_id"], $g["local_graph_id"]);
				if (empty($g['user_id']) || $g['user_id'] == NULL) {
					if ($policy['policy_graphs'] == 1) {
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $g['local_graph_id']);
					}else{
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $g['local_graph_id']);
					}
				} else {
					if ($policy['policy_graphs'] == 1) {
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $g['local_graph_id']);
					}else{
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $g['local_graph_id']);
					}
				}
				form_checkbox_cell($g["title_cache"], $g["local_graph_id"]);
				form_end_row();
			}
	
			/* put the nav bar on the bottom as well */
			print $nav;
		} else {
			print "<tr><td><em>No Associated Graphs Found</em></td></tr>";
		}
		html_end_box(false);

		form_hidden_box("action", "user_edit", "");
		form_hidden_box("tab",$tab,"");
		form_hidden_box("id", get_request_var_request("id"), "");
		form_hidden_box("associate_graph", "1", "");

		if ($policy['policy_graphs'] == 1) {
			$assoc_actions = array(
				1 => "Revoke Access",
				2 => "Grant Access"
			);
		}else{
			$assoc_actions = array(
				1 => "Grant Access",
				2 => "Revoke Access"
			);
		}

		/* draw the dropdown containing a list of available actions for this form */
		draw_actions_dropdown($assoc_actions);

		print "</form>";

		break;
	case 'permsd':
		process_device_request_vars();

		device_filter($header_label);

		/* print checkbox form for validation */
		print "<form name='policy' method='post' action='user_admin.php'>\n";

		/* box: device permissions */
		html_start_box("<strong>Default Policy</strong>", "100%", $colors["header"], "3", "center", "");

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td><table cellspacing="0" cellpadding="2"><tr>
			<td style="white-space:nowrap;" width="120">The Default Allow/Deny Device Policy for this User.</td>
			<td width="10"> 
				<?php form_dropdown("policy_hosts",$policy_array,"","",$policy["policy_hosts"],"",""); ?>
			</td>
			<td>
				<input type="submit" name="update_policy" value="Update">
				<input type="hidden" name="tab" value="<?php print $tab;?>">
				<input type="hidden" name="id" value="<?php print get_request_var_request("id");?>">
			</td>
			</tr></table></td>
		</tr>
		<?php

		html_end_box();

		print "</form>\n";

		/* if the number of rows is -1, set it to the default */
		if ($_REQUEST["rows"] == -1) {
			$rows = 15;
		}else{
			$rows = $_REQUEST["rows"];
		}

		/* form the 'where' clause for our main sql query */
		/* form the 'where' clause for our main sql query */
		if (strlen(get_request_var_request("filter"))) {
			$sql_where = "WHERE (host.hostname LIKE '%%" . get_request_var_request("filter") . "%%' OR host.description LIKE '%%" . get_request_var_request("filter") . "%%')";
		} else {
			$sql_where = "";
		}

		if (get_request_var_request("host_template_id") == "-1") {
			/* Show all items */
		}elseif (get_request_var_request("host_template_id") == "0") {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " host.host_template_id=0";
		}elseif (!empty($_REQUEST["host_template_id"])) {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " host.host_template_id=" . get_request_var_request("host_template_id");
		}

		if (get_request_var_request("associated") == "false") {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " user_auth_perms.user_id=" . get_request_var_request("id", 0);
		}

		/* print checkbox form for validation */
		print "<form name='chk' method='post' action='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permsd&id=" . get_request_var_request("id")) . "'>\n";

		html_start_box("", "100%", $colors["header"], "3", "center", "");

		$total_rows = db_fetch_cell("select
			COUNT(host.id)
			FROM host
			LEFT JOIN user_auth_perms 
			ON (host.id=user_auth_perms.item_id AND user_auth_perms.type = 3)
			$sql_where");

		$host_graphs       = array_rekey(db_fetch_assoc("SELECT host_id, count(*) as graphs FROM graph_local GROUP BY host_id"), "host_id", "graphs");
		$host_data_sources = array_rekey(db_fetch_assoc("SELECT host_id, count(*) as data_sources FROM data_local GROUP BY host_id"), "host_id", "data_sources");

		$sql_query = "SELECT host.*, user_auth_perms.user_id
			FROM host 
			LEFT JOIN user_auth_perms 
			ON (host.id=user_auth_perms.item_id AND user_auth_perms.type=3)
			$sql_where 
			ORDER BY description
			LIMIT " . ($rows*(get_request_var_request("page")-1)) . "," . $rows;

		$hosts = db_fetch_assoc($sql_query);

		/* generate page list */
		if ($total_rows > 0) {
			$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $rows, $total_rows, "user_admin.php?action=user_edit&tab=permsd&id=" . get_request_var_request('id'));
	
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
							<td align='left' class='textHeaderDark'>
									<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permsd&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
								</td>\n
								<td align='center' class='textHeaderDark'>
									Showing Rows " . (($rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $rows) || ($total_rows < ($rows*get_request_var_request("page")))) ? $total_rows : ($rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
								</td>\n
								<td align='right' class='textHeaderDark'>
											<strong>"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tabe=permsd&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}else{
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
								<td align='center' class='textHeaderDark'>
									No Rows Found
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}
	
		print $nav;

		$display_text = array("Description", "ID", "Effective Policy", "Graphs", "Data Sources", "Status", "Hostname");

		html_header_checkbox($display_text, false);

		$i = 0;
		if (sizeof($hosts)) {
			foreach ($hosts as $host) {
				form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $host["id"]); $i++;
				form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($host["description"])) : htmlspecialchars($host["description"])), $host["id"], 250);
				form_selectable_cell(round(($host["id"]), 2), $host["id"]);
				if (empty($host['user_id']) || $host['user_id'] == NULL) {
					if ($policy['policy_hosts'] == 1) {
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $host['id']);
					}else{
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $host['id']);
					}
				} else {
					if ($policy['policy_hosts'] == 1) {
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $host['id']);
					}else{
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $host['id']);
					}
				}
				form_selectable_cell((isset($host_graphs[$host["id"]]) ? $host_graphs[$host["id"]] : 0), $host["id"]);
				form_selectable_cell((isset($host_data_sources[$host["id"]]) ? $host_data_sources[$host["id"]] : 0), $host["id"]);
				form_selectable_cell(get_colored_device_status(($host["disabled"] == "on" ? true : false), $host["status"]), $host["id"]);
				form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($host["hostname"])) : htmlspecialchars($host["hostname"])), $host["id"]);
				form_checkbox_cell($host["description"], $host["id"]);
				form_end_row();
			}
	
			/* put the nav bar on the bottom as well */
			print $nav;
		} else {
			print "<tr><td><em>No Associated Hosts Found</em></td></tr>";
		}
		html_end_box(false);

		form_hidden_box("action", "user_edit", "");
		form_hidden_box("tab",$tab,"");
		form_hidden_box("id", get_request_var_request("id"), "");
		form_hidden_box("associate_host", "1", "");

		if ($policy['policy_hosts'] == 1) {
			$assoc_actions = array(
				1 => "Revoke Access",
				2 => "Grant Access"
			);
		}else{
			$assoc_actions = array(
				1 => "Grant Access",
				2 => "Revoke Access"
			);
		}

		/* draw the dropdown containing a list of available actions for this form */
		draw_actions_dropdown($assoc_actions);

		print "</form>";

		break;
	case 'permste':
		process_template_request_vars();

		template_filter($header_label);

		/* print checkbox form for validation */
		print "<form name='policy' method='post' action='user_admin.php'>\n";

		/* box: device permissions */
		html_start_box("<strong>Default Policy</strong>", "100%", $colors["header"], "3", "center", "");

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td><table cellspacing="0" cellpadding="2"><tr>
			<td style="white-space:nowrap;" width="120">The Default Allow/Deny Template Policy for this User.</td>
			<td width="10"> 
				<?php form_dropdown("policy_graph_templates",$policy_array,"","",$policy["policy_graph_templates"],"",""); ?>
			</td>
			<td>
				<input type="submit" name="update_policy" value="Update">
				<input type="hidden" name="tab" value="<?php print $tab;?>">
				<input type="hidden" name="id" value="<?php print get_request_var_request("id");?>">
			</td>
			</tr></table></td>
		</tr>
		<?php

		html_end_box();

		print "</form>\n";

		/* if the number of rows is -1, set it to the default */
		if ($_REQUEST["rows"] == -1) {
			$rows = 15;
		}else{
			$rows = $_REQUEST["rows"];
		}

		/* form the 'where' clause for our main sql query */
		if (strlen(get_request_var_request("filter"))) {
			$sql_where = "WHERE (gt.name LIKE '%%" . get_request_var_request("filter") . "%%')";
		} else {
			$sql_where = "";
		}

		if (get_request_var_request("associated") == "false") {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " (user_auth_perms.type=4 AND user_auth_perms.user_id=" . get_request_var_request("id", 0) . ")";
		}

		/* print checkbox form for validation */
		print "<form name='chk' method='post' action='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permste&id=" . get_request_var_request("id")) . "'>\n";

		html_start_box("", "100%", $colors["header"], "3", "center", "");

		$total_rows = db_fetch_cell("SELECT
			COUNT(gt.id)
			FROM graph_templates AS gt
			INNER JOIN graph_local AS gl
			ON gt.id=gl.graph_template_id
			LEFT JOIN user_auth_perms 
			ON (gt.id=user_auth_perms.item_id AND user_auth_perms.type=4)
			$sql_where
			GROUP BY gl.graph_template_id");

		$sql_query = "SELECT gt.id, gt.name, count(*) AS totals, user_auth_perms.user_id
			FROM graph_templates AS gt
			INNER JOIN graph_local AS gl
			ON gt.id=gl.graph_template_id
			LEFT JOIN user_auth_perms 
			ON (gt.id=user_auth_perms.item_id AND user_auth_perms.type=4)
			$sql_where 
			GROUP BY gl.graph_template_id
			ORDER BY name
			LIMIT " . ($rows*(get_request_var_request("page")-1)) . "," . $rows;

		$graphs = db_fetch_assoc($sql_query);

		/* generate page list */
		if ($total_rows > 0) {
			$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $rows, $total_rows, "user_admin.php?action=user_edit&tab=permste&id=" . get_request_var_request('id'));
	
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
							<td align='left' class='textHeaderDark'>
									<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permste&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
								</td>\n
								<td align='center' class='textHeaderDark'>
									Showing Rows " . (($rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $rows) || ($total_rows < ($rows*get_request_var_request("page")))) ? $total_rows : ($rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
								</td>\n
								<td align='right' class='textHeaderDark'>
											<strong>"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tabe=permste&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}else{
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
								<td align='center' class='textHeaderDark'>
									No Rows Found
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}
	
		print $nav;

		$display_text = array("Template Name", "ID", "Effective Policy", "Total Graphs");

		html_header_checkbox($display_text, false);

		$i = 0;
		if (sizeof($graphs)) {
			foreach ($graphs as $g) {
				form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $g["id"]); $i++;
				form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($g["name"])) : htmlspecialchars($g["name"])), $g["id"], 250);
				form_selectable_cell($g["id"], $g["id"]);
				if (empty($g['user_id']) || $g['user_id'] == NULL) {
					if ($policy['policy_graph_templates'] == 1) {
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $g['id']);
					}else{
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $g['id']);
					}
				} else {
					if ($policy['policy_graph_templates'] == 1) {
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $g['id']);
					}else{
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $g['id']);
					}
				}
				form_selectable_cell($g["totals"], $g["id"]);
				form_checkbox_cell($g["name"], $g["id"]);
				form_end_row();
			}
	
			/* put the nav bar on the bottom as well */
			print $nav;
		} else {
			print "<tr><td><em>No Associated Graph Templates Found</em></td></tr>";
		}
		html_end_box(false);

		form_hidden_box("action", "user_edit", "");
		form_hidden_box("tab",$tab,"");
		form_hidden_box("id", get_request_var_request("id"), "");
		form_hidden_box("associate_template", "1", "");

		if ($policy['policy_graph_templates'] == 1) {
			$assoc_actions = array(
				1 => "Revoke Access",
				2 => "Grant Access"
			);
		}else{
			$assoc_actions = array(
				1 => "Grant Access",
				2 => "Revoke Access"
			);
		}

		/* draw the dropdown containing a list of available actions for this form */
		draw_actions_dropdown($assoc_actions);

		print "</form>";

		break;
	case 'permstr':
		process_tree_request_vars();

		tree_filter($header_label);

		/* print checkbox form for validation */
		print "<form name='policy' method='post' action='user_admin.php'>\n";

		/* box: device permissions */
		html_start_box("<strong>Default Policy</strong>", "100%", $colors["header"], "3", "center", "");

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td><table cellspacing="0" cellpadding="2"><tr>
			<td style="white-space:nowrap;" width="120">The Default Allow/Deny Tree Policy for this User Group.</td>
			<td width="10"> 
				<?php form_dropdown("policy_trees",$policy_array,"","",$policy["policy_trees"],"",""); ?>
			</td>
			<td>
				<input type="submit" name="update_policy" value="Update">
				<input type="hidden" name="tab" value="<?php print $tab;?>">
				<input type="hidden" name="id" value="<?php print get_request_var_request("id");?>">
			</td>
			</tr></table></td>
		</tr>
		<?php

		html_end_box();

		print "</form>\n";

		/* if the number of rows is -1, set it to the default */
		if ($_REQUEST["rows"] == -1) {
			$rows = 15;
		}else{
			$rows = $_REQUEST["rows"];
		}

		/* form the 'where' clause for our main sql query */
		/* form the 'where' clause for our main sql query */
		if (strlen(get_request_var_request("filter"))) {
			$sql_where = "WHERE (gt.name LIKE '%%" . get_request_var_request("filter") . "%%')";
		} else {
			$sql_where = "";
		}

		if (get_request_var_request("associated") == "false") {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " (user_auth_perms.type=2 AND user_auth_perms.user_id=" . get_request_var_request("id", 0) . ")";
		}

		/* print checkbox form for validation */
		print "<form name='chk' method='post' action='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permstr&id=" . get_request_var_request("id")) . "'>\n";

		html_start_box("", "100%", $colors["header"], "3", "center", "");

		$total_rows = db_fetch_cell("SELECT
			COUNT(gt.id)
			FROM graph_tree AS gt
			LEFT JOIN user_auth_perms 
			ON (gt.id=user_auth_perms.item_id AND user_auth_perms.type=2)
			$sql_where");

		$sql_query = "SELECT gt.id, gt.name, user_auth_perms.user_id
			FROM graph_tree AS gt
			LEFT JOIN user_auth_perms 
			ON (gt.id=user_auth_perms.item_id AND user_auth_perms.type=2)
			$sql_where 
			ORDER BY name
			LIMIT " . ($rows*(get_request_var_request("page")-1)) . "," . $rows;

		$trees = db_fetch_assoc($sql_query);

		/* generate page list */
		if ($total_rows > 0) {
			$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $rows, $total_rows, "user_admin.php?action=user_edit&tab=permstr&id=" . get_request_var_request('id'));
	
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
							<td align='left' class='textHeaderDark'>
									<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tab=permstr&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
								</td>\n
								<td align='center' class='textHeaderDark'>
									Showing Rows " . (($rows*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < $rows) || ($total_rows < ($rows*get_request_var_request("page")))) ? $total_rows : ($rows*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
								</td>\n
								<td align='right' class='textHeaderDark'>
											<strong>"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?action=user_edit&tabe=permstr&id=" . get_request_var_request("id") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}else{
			$nav = "<tr bgcolor='#" . $colors["header"] . "'>
					<td colspan='11'>
						<table width='100%' cellspacing='0' cellpadding='0' border='0'>
							<tr>
								<td align='center' class='textHeaderDark'>
									No Rows Found
								</td>\n
							</tr>
						</table>
					</td>
				</tr>\n";
		}
	
		print $nav;

		$display_text = array("Tree Name", "ID", "Effective Policy");

		html_header_checkbox($display_text, false);

		$i = 0;
		if (sizeof($trees)) {
			foreach ($trees as $t) {
				form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $t["id"]); $i++;
				form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($t["name"])) : htmlspecialchars($t["name"])), $t["id"], 250);
				form_selectable_cell($t["id"], $t["id"]);
				if (empty($t['user_id']) || $t['user_id'] == NULL) {
					if ($policy['policy_graphs'] == 1) {
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $t['id']);
					}else{
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $t['id']);
					}
				} else {
					if ($policy['policy_graphs'] == 1) {
						form_selectable_cell('<span style="color:red;font-weight:bold;">Access Restricted</span>', $t['id']);
					}else{
						form_selectable_cell('<span style="color:green;font-weight:bold;">Access Granted</span>', $t['id']);
					}
				}
				form_checkbox_cell($t["name"], $t["id"]);
				form_end_row();
			}
	
			/* put the nav bar on the bottom as well */
			print $nav;
		} else {
			print "<tr><td><em>No Associated Graph Trees Found</em></td></tr>";
		}
		html_end_box(false);

		form_hidden_box("action", "user_edit", "");
		form_hidden_box("tab",$tab,"");
		form_hidden_box("id", get_request_var_request("id"), "");
		form_hidden_box("associate_tree", "1", "");

		if ($policy['policy_graph_templates'] == 1) {
			$assoc_actions = array(
				1 => "Revoke Access",
				2 => "Grant Access"
			);
		}else{
			$assoc_actions = array(
				1 => "Grant Access",
				2 => "Revoke Access"
			);
		}

		/* draw the dropdown containing a list of available actions for this form */
		draw_actions_dropdown($assoc_actions);

		print "</form>";

		break;
	}
}

function user_realms_edit($header_label) {
	global $colors, $user_auth_realms;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	/* ==================================================== */

	print "<form name='chk' action='user_admin.php' method='post'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$all_realms = $user_auth_realms;

	print "	<tr bgcolor='#" . $colors["header"] . "'>
			<td class='textHeaderDark'><strong>Realm Permissions</strong> $header_label</td>
			<td class='tableHeader' width='1%' align='center' bgcolor='#819bc0' style='" . get_checkbox_style() . "'><input type='checkbox' style='margin: 0px;' name='all' title='Select All' onClick='SelectAll(\"section\",this.checked)'></td>\n
		</tr>\n";

	/* do cacti realms first */
	print "<tr class='tableSubHeaderColumn' bgcolor='#" . $colors['header_panel'] . "'><td>Base Permissions<td></tr>\n";
	print "<tr><td colspan='4' width='100%'><table width='100%'><tr><td valign='top' style='white-space:nowrap;' width='25%'>\n";
	$i = 1;
	$j = 1;
	$base = array(7,8,15,1,2,3,4,5,6,9,10,11,12,13,14,16,17,101);
	foreach($base as $realm) {
		if (isset($user_auth_realms[$realm])) {
			if (sizeof(db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE user_id=" . get_request_var_request("id", 0) . " AND realm_id=" . $realm)) > 0) {
				$old_value = "on";
			}else{
				$old_value = "";
			}

			unset($all_realms[$realm]);

			if ($j == 6) {
				print "</td><td valign='top' width='25%' style='white-space:nowrap;'>\n";
				$j = 1;
			}

			form_checkbox("section" . $realm, $old_value, $user_auth_realms[$realm], "", "", "", (!empty($_REQUEST["id"]) ? 1 : 0)); print "<br>";

			$j++;
		}
	}
	print "</td></tr></table></td></tr>\n";

	/* do plugin realms */
	$realms = db_fetch_assoc("SELECT pc.name, pr.id AS realm_id, pr.display
		FROM plugin_config AS pc
		INNER JOIN plugin_realms AS pr
		ON pc.directory=pr.plugin
		ORDER BY pc.name, pr.display");

	print "<tr class='tableSubHeaderColumn' bgcolor='#" . $colors['header_panel'] . "'><td>Plugin Permissions<td></tr>\n";
	print "<tr><td colspan='4' width='100%'><table width='100%' cellpadding='0' cellspacing='0'><tr><td valign='top' width='25%' style='white-space:nowrap;'>\n";
	if (sizeof($realms)) {
		$last_plugin = "none";
		$i = 1;
		$j = 1;
		$level = floor(sizeof($all_realms) / 4); 
		$break = false;

		foreach($realms as $r) {
			if ($last_plugin != $r["name"]) {
				if ($break) {
					print "</td><td valign='top' width='25%' style='white-space:nowrap;'>\n";
					$break = false;
					$j = 1;
				}
				print "<strong>" . $r["name"] . "</strong><br>\n";
				$last_plugin = $r["name"];
			}elseif ($break) {
				print "</td><td valign='top' width='25%' style='white-space:nowrap;'>\n";
				$break = false;
				$j = 1;
				print "<strong>" . $r["name"] . " (cont)</strong><br>\n";
			}

			if ($j == 5) {
				$break = true;;
			}

			$realm = $r["realm_id"] + 100;

			if (sizeof(db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE user_id=" . get_request_var_request("id", 0) . " AND realm_id=" . $realm)) > 0) {
				$old_value = "on";
			}else{
				$old_value = "";
			}

			unset($all_realms[$realm]);

			$pos = (strpos($user_auth_realms[$realm], "->") !== false ? strpos($user_auth_realms[$realm], "->")+2:0);

			form_checkbox("section" . $realm, $old_value, substr($user_auth_realms[$realm], $pos), "", "", "", (!empty($_REQUEST["id"]) ? 1 : 0)); print "<br>";

			$j++;
		}
	}

	/* get the old PIA 1.x realms */
	if (sizeof($all_realms)) {
		if ($break) {
			print "</td><td valign='top' width='25%' style='white-space:nowrap;'>\n";
		}
		print "<strong>Legacy 1.x Plugins</strong><br>\n";
		foreach($all_realms as $realm => $name) {
			if (sizeof(db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE user_id=" . get_request_var_request("id", 0) . " AND realm_id=" . $realm)) > 0) {
				$old_value = "on";
			}else{
				$old_value = "";
			}

			$pos = (strpos($user_auth_realms[$realm], "->") !== false ? strpos($user_auth_realms[$realm], "->")+2:0);

			form_checkbox("section" . $realm, $old_value, substr($user_auth_realms[$realm], $pos), "", "", "", (!empty($_REQUEST["id"]) ? 1 : 0)); print "<br>";


		}
	}

	print "</td></tr></table></td></tr>\n";

	html_end_box();

	form_hidden_box("action", "user_edit", "");
	form_hidden_box("id", get_request_var_request("id"), "");
	form_hidden_box("tab", "realms", "");
	form_hidden_box("save_component_realm_perms", "1", "");

	form_save_button("user_admin.php", "return");
}

function graph_settings_edit($header_label) {
	global $settings_graphs, $tabs_graphs, $colors, $graph_views, $graph_tree_views;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	/* ==================================================== */

	print "<form name='chk' action='user_admin.php' method='post'>\n";

	html_start_box("<strong>Graph Settings</strong> $header_label", "100%", $colors["header"], "3", "center", "");

	while (list($tab_short_name, $tab_fields) = each($settings_graphs)) {
		?>
		<tr bgcolor='#<?php print $colors["header_panel"];?>'>
			<td colspan='2' class='tableSubHeaderColumn' style='padding: 3px;'>
				<?php print $tabs_graphs[$tab_short_name];?>
			</td>
		</tr>
		<?php

		$form_array = array();

		while (list($field_name, $field_array) = each($tab_fields)) {
			$form_array += array($field_name => $tab_fields[$field_name]);

			if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
				while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
					if (graph_config_value_exists($sub_field_name, $_REQUEST["id"])) {
						$form_array[$field_name]["items"][$sub_field_name]["form_id"] = 1;
					}

					$form_array[$field_name]["items"][$sub_field_name]["value"] =  db_fetch_cell("SELECT value FROM settings_graphs WHERE name = '" . $sub_field_name . "' AND user_id = " . get_request_var_request("id"));
				}
			}else{
				if (graph_config_value_exists($field_name, $_REQUEST["id"])) {
					$form_array[$field_name]["form_id"] = 1;
				}

				$form_array[$field_name]["value"] = db_fetch_cell("select value from settings_graphs where name='$field_name' and user_id=" . $_REQUEST["id"]);
			}
		}

		draw_edit_form(
			array(
				"config" => array(
					"no_form_tag" => true
					),
				"fields" => $form_array
				)
			);
	}

	html_end_box();

	form_hidden_box("action", "user_edit", "");
	form_hidden_box("id", get_request_var_request("id"), "");
	form_hidden_box("tab", "settings", "");
	form_hidden_box("save_component_graph_settings","1","");

	form_save_button("user_admin.php", "return");
}

/* --------------------------
    User Administration
   -------------------------- */

function user_edit() {
	global $config, $colors, $fields_user_user_edit_host;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	/* ==================================================== */

	/* present a tabbed interface */
	$tabs = array(
		"general" => "General",
		"realms" => "Realm Perms",
		"permsg" => "Graph Perms",
		"permsd" => "Device Perms",
		"permste" => "Template Perms",
		"permstr" => "Tree Perms",
		"settings" => "Graph Settings"
	);

	if (!empty($_REQUEST['id']) && !ugroup_user_console_authorized($_REQUEST['id'])) {
		unset($fields_user_user_edit_host["login_opts"]["items"][1]);
	}

    /* set the default tab */
    load_current_session_value("tab", "sess_user_admin_tab", "general");
    $current_tab = $_REQUEST["tab"];

	if (!empty($_REQUEST["id"])) {
		$user = db_fetch_row("SELECT * FROM user_auth WHERE id = " . get_request_var_request("id"));
		$header_label = "[edit: " . $user["username"] . "]";
	}else{
		$header_label = "[new]";
	}

	if (sizeof($tabs) && isset($_REQUEST['id'])) {
		/* draw the tabs */
		print "<table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

		foreach (array_keys($tabs) as $tab_short_name) {
			print "<td style='padding:3px 10px 2px 5px;background-color:" . (($tab_short_name == $current_tab) ? "silver;" : "#DFDFDF;") .
				"white-space:nowrap;'" .
				" width='1%' " .
				" align='center' class='tab'>
				<span class='textHeader'><a href='" . htmlspecialchars($config['url_path'] .
				"plugins/ugroup/user_admin.php?action=user_edit&id=" . get_request_var_request('id') .
				"&tab=" . $tab_short_name) .
				"'>$tabs[$tab_short_name]</a></span>
				</td>\n
				<td width='1'></td>\n";
		}

		api_plugin_hook('user_admin_tab');

		print "<td></td>\n</tr></table>\n";
	}

	switch($current_tab) {
	case 'general':
		api_plugin_hook_function('user_admin_edit', (isset($user) ? get_request_var_request("id") : 0));

		html_start_box("<strong>User Management</strong> $header_label", "100%", $colors["header"], "3", "center", "");

		draw_edit_form(array(
			"config" => array("form_name" => "chk"),
			"fields" => inject_form_variables($fields_user_user_edit_host, (isset($user) ? $user : array()))
			));

		html_end_box();

		form_save_button("user_admin.php", "return");

		break;
	case 'settings':
		graph_settings_edit($header_label);

		break;
	case 'realms':
		user_realms_edit($header_label);

		break;
	case 'permsg':
	case 'permsd':
	case 'permste':
	case 'permstr':
		graph_perms_edit($current_tab, $header_label);
		break;
	default:
		if (api_plugin_hook_function('user_admin_run_action', get_request_var_request("tab"))) {
			user_realms_edit();
		}
		break;
	}
}

function user() {
	global $config, $colors, $auth_realms, $user_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
	}

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var_request("sort_column"));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var_request("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_user_admin_current_page");
		kill_session_var("sess_user_admin_rows");
		kill_session_var("sess_user_admin_filter");
		kill_session_var("sess_user_admin_sort_column");
		kill_session_var("sess_user_admin_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
	}else{
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_user_admin_current_page", "1");
	load_current_session_value("rows", "sess_user_admin_rows", "-1");
	load_current_session_value("filter", "sess_user_admin_filter", "");
	load_current_session_value("sort_column", "sess_user_admin_sort_column", "username");
	load_current_session_value("sort_direction", "sess_user_admin_sort_direction", "ASC");

	?>
	<script type="text/javascript">
	function applyFilterChange(objForm) {
		strURL = '?rows=' + objForm.rows.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	</script>
	<?php

	html_start_box("<strong>User Management</strong>", "100%", $colors["header"], "3", "center", "user_admin.php?action=user_edit");

	if ($_REQUEST["rows"] == '-1') {
		$rows = read_config_option("num_rows_device");
	}else{
		$rows = $_REQUEST["rows"];
	}

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="form_user_admin" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="50">
						Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.form_user_admin)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;'>
						&nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
					</td>
					<td nowrap style='white-space: nowrap;'>
						&nbsp;<input type="submit" name="clear_x" value="Clear" title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var_request("filter"))) {
		$sql_where = "WHERE (user_auth.username LIKE '%" . get_request_var_request("filter") . "%' OR user_auth.full_name LIKE '%" . get_request_var_request("filter") . "%')";
	}else{
		$sql_where = "";
	}

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='user_admin.php'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(user_auth.id)
		FROM user_auth
		$sql_where");

	$user_list = db_fetch_assoc("SELECT
		id,
		user_auth.username,
		full_name,
		realm,
		enabled,
		policy_graphs,
		time,
		max(time) as dtime
		FROM user_auth
		LEFT JOIN user_log ON (user_auth.id = user_log.user_id)
		$sql_where
		GROUP BY id
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") .
		" LIMIT " . ($rows * (get_request_var_request("page") - 1)) . "," . $rows);

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, $rows, $total_rows, "user_admin.php?filter=" . get_request_var_request("filter"));

	$nav = "<tr bgcolor='#" . $colors["header"] . "'>
		<td colspan='7'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='left' class='textHeaderDark'>
						<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page") - 1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
					</td>\n
					<td align='center' class='textHeaderDark'>
						Showing Rows " . (($rows * (get_request_var_request("page") - 1)) + 1) . " to " . ((($total_rows < $rows) || ($total_rows < ($rows * get_request_var_request("page")))) ? $total_rows : ($rows * get_request_var_request("page"))) . " of $total_rows [$url_page_select]
					</td>\n
					<td align='right' class='textHeaderDark'>
						<strong>"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("user_admin.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page") + 1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * $rows) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
					</td>\n
				</tr>
			</table>
		</td>
		</tr>\n";

	print $nav;

	$display_text = array(
		"username" => array("User Name", "ASC"),
		"full_name" => array("Full Name", "ASC"),
		"enabled" => array("Enabled", "ASC"),
		"realm" => array("Realm", "ASC"),
		"policy_graphs" => array("Default Graph Policy", "ASC"),
		"dtime" => array("Last Login", "DESC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

	$i = 0;
	if (sizeof($user_list) > 0) {
		foreach ($user_list as $user) {
			if (empty($user["dtime"]) || ($user["dtime"] == "12/31/1969")) {
				$last_login = "N/A";
			}else{
				$last_login = strftime("%A, %B %d, %Y %H:%M:%S ", strtotime($user["dtime"]));;
			}
			if ($user["enabled"] == "on") {
				$enabled = "Yes";
			}else{
				$enabled = "No";
			}

			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $user["id"]); $i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars($config["url_path"] . "plugins/ugroup/user_admin.php?action=user_edit&tab=general&id=" . $user["id"]) . "'>" .
			(strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>",  htmlspecialchars($user["username"])) : htmlspecialchars($user["username"]))
			, $user["id"]);
			form_selectable_cell((strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($user["full_name"])) : htmlspecialchars($user["full_name"])), $user["id"]);
			form_selectable_cell($enabled, $user["id"]);
			form_selectable_cell($auth_realms[$user["realm"]], $user["id"]);
			if ($user["policy_graphs"] == "1") {
				form_selectable_cell("ALLOW", $user["id"]);
			}else{
				form_selectable_cell("DENY", $user["id"]);
			}
			form_selectable_cell($last_login, $user["id"]);
			form_checkbox_cell($user["username"], $user["id"]);
			form_end_row();
		}

		print $nav;
	}else{
		print "<tr><td><em>No Users</em></td></tr>";
	}
	html_end_box(false);

	draw_actions_dropdown($user_actions);

}

function ugroup_check_changed($request, $session) {
    if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
        if ($_REQUEST[$request] != $_SESSION[$session]) {
            return 1;
        }
    }
}

function process_graph_request_vars() {
    /* ================= input validation ================= */
    input_validate_input_number(get_request_var_request("graph_template_id"));
    input_validate_input_number(get_request_var_request("rows"));
    input_validate_input_number(get_request_var_request("page"));
    /* ==================================================== */

    /* clean up search string */
    if (isset($_REQUEST["filter"])) {
        $_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
    }

	/* clean up sort solumn */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up associated */
	if (isset($_REQUEST['associated'])) {
		$_REQUEST['associated'] = sanitize_search_string(get_request_var_request('associated'));
	}

    /* if the user pushed the 'clear' button */
    if (isset($_REQUEST["clearf"])) {
        kill_session_var("sess_uag_page");
        kill_session_var("sess_uag_rows");
        kill_session_var("sess_uag_filter");
        kill_session_var("sess_uag_graph_template_id");
        kill_session_var("sess_uag_associated");
        kill_session_var("sess_uag_sort_column");
        kill_session_var("sess_uag_sort_direction");

        unset($_REQUEST["page"]);
        unset($_REQUEST["rows"]);
        unset($_REQUEST["filter"]);
        unset($_REQUEST["graph_template_id"]);
        unset($_REQUEST["associated"]);
        unset($_REQUEST["sort_column"]);
        unset($_REQUEST["sort_direction"]);
    }else{
        $changed = 0;
        $changed += ugroup_check_changed('rows', 'sess_uag_rows');
        $changed += ugroup_check_changed('filter', 'sess_uag_filter');
        $changed += ugroup_check_changed('graph_template_id', 'sess_uag_graph_template_id');
        $changed += ugroup_check_changed('associated', 'sess_uag_associated');
        $changed += ugroup_check_changed('sort_column', 'sess_uag_sort_column');
        $changed += ugroup_check_changed('sort_direction', 'sess_uag_sort_direction');
        if ($changed) {
            $_REQUEST['page'] = '1';
        }
	}

    /* remember these search fields in session vars so we don't have to keep passing them around */
    load_current_session_value("page", "sess_uag_page", "1");
    load_current_session_value("filter", "sess_uag_filter", "");
    load_current_session_value("associated", "sess_uag_associated", "false");
    load_current_session_value("graph_template_id", "sess_uad_graph_template_id", "-1");
    load_current_session_value("rows", "sess_uad_rows", "-1");
}

function process_device_request_vars() {
    /* ================= input validation ================= */
    input_validate_input_number(get_request_var_request("host_template_id"));
    input_validate_input_number(get_request_var_request("rows"));
    input_validate_input_number(get_request_var_request("page"));
    /* ==================================================== */

    /* clean up search string */
    if (isset($_REQUEST["filter"])) {
        $_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
    }

	/* clean up sort solumn */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up associated */
	if (isset($_REQUEST['associated'])) {
		$_REQUEST['associated'] = sanitize_search_string(get_request_var_request('associated'));
	}

    /* if the user pushed the 'clear' button */
    if (isset($_REQUEST["clearf"])) {
        kill_session_var("sess_uad_page");
        kill_session_var("sess_uad_rows");
        kill_session_var("sess_uad_filter");
        kill_session_var("sess_uad_host_template_id");
        kill_session_var("sess_uad_associated");
        kill_session_var("sess_uad_sort_column");
        kill_session_var("sess_uad_sort_direction");

        unset($_REQUEST["page"]);
        unset($_REQUEST["rows"]);
        unset($_REQUEST["filter"]);
        unset($_REQUEST["host_template_id"]);
        unset($_REQUEST["associated"]);
        unset($_REQUEST["sort_column"]);
        unset($_REQUEST["sort_direction"]);
    }else{
        $changed = 0;
        $changed += ugroup_check_changed('rows', 'sess_uad_rows');
        $changed += ugroup_check_changed('filter', 'sess_uad_filter');
        $changed += ugroup_check_changed('host_template_id', 'sess_uad_host_template_id');
        $changed += ugroup_check_changed('associated', 'sess_uad_associated');
        $changed += ugroup_check_changed('sort_column', 'sess_uad_sort_column');
        $changed += ugroup_check_changed('sort_direction', 'sess_uad_sort_direction');
        if ($changed) {
            $_REQUEST['page'] = '1';
        }
	}

    /* remember these search fields in session vars so we don't have to keep passing them around */
    load_current_session_value("page", "sess_uad_page", "1");
    load_current_session_value("filter", "sess_uad_filter", "");
    load_current_session_value("associated", "sess_uad_associated", "false");
    load_current_session_value("host_template_id", "sess_uad_host_template_id", "-1");
    load_current_session_value("rows", "sess_uad_rows", "-1");
}

function process_template_request_vars() {
    /* ================= input validation ================= */
    input_validate_input_number(get_request_var_request("rows"));
    input_validate_input_number(get_request_var_request("page"));
    /* ==================================================== */

    /* clean up search string */
    if (isset($_REQUEST["filter"])) {
        $_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
    }

	/* clean up sort solumn */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up associated */
	if (isset($_REQUEST['associated'])) {
		$_REQUEST['associated'] = sanitize_search_string(get_request_var_request('associated'));
	}

    /* if the user pushed the 'clear' button */
    if (isset($_REQUEST["clearf"])) {
        kill_session_var("sess_uate_page");
        kill_session_var("sess_uate_rows");
        kill_session_var("sess_uate_filter");
        kill_session_var("sess_uate_associated");
        kill_session_var("sess_uate_sort_column");
        kill_session_var("sess_uate_sort_direction");

        unset($_REQUEST["page"]);
        unset($_REQUEST["rows"]);
        unset($_REQUEST["filter"]);
        unset($_REQUEST["associated"]);
        unset($_REQUEST["sort_column"]);
        unset($_REQUEST["sort_direction"]);
    }else{
        $changed = 0;
        $changed += ugroup_check_changed('rows', 'sess_uate_rows');
        $changed += ugroup_check_changed('filter', 'sess_uate_filter');
        $changed += ugroup_check_changed('associated', 'sess_uate_associated');
        $changed += ugroup_check_changed('sort_column', 'sess_uate_sort_column');
        $changed += ugroup_check_changed('sort_direction', 'sess_uate_sort_direction');
        if ($changed) {
            $_REQUEST['page'] = '1';
        }
	}

    /* remember these search fields in session vars so we don't have to keep passing them around */
    load_current_session_value("page", "sess_uate_page", "1");
    load_current_session_value("filter", "sess_uate_filter", "");
    load_current_session_value("associated", "sess_uate_associated", "false");
    load_current_session_value("rows", "sess_uate_rows", "-1");
}

function process_tree_request_vars() {
    /* ================= input validation ================= */
    input_validate_input_number(get_request_var_request("rows"));
    input_validate_input_number(get_request_var_request("page"));
    /* ==================================================== */

    /* clean up search string */
    if (isset($_REQUEST["filter"])) {
        $_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
    }

	/* clean up sort solumn */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort direction */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* clean up associated */
	if (isset($_REQUEST['associated'])) {
		$_REQUEST['associated'] = sanitize_search_string(get_request_var_request('associated'));
	}

    /* if the user pushed the 'clear' button */
    if (isset($_REQUEST["clearf"])) {
        kill_session_var("sess_uatr_page");
        kill_session_var("sess_uatr_rows");
        kill_session_var("sess_uatr_filter");
        kill_session_var("sess_uatr_associated");
        kill_session_var("sess_uatr_sort_column");
        kill_session_var("sess_uatr_sort_direction");

        unset($_REQUEST["page"]);
        unset($_REQUEST["rows"]);
        unset($_REQUEST["filter"]);
        unset($_REQUEST["associated"]);
        unset($_REQUEST["sort_column"]);
        unset($_REQUEST["sort_direction"]);
    }else{
        $changed = 0;
        $changed += ugroup_check_changed('rows', 'sess_uatr_rows');
        $changed += ugroup_check_changed('filter', 'sess_uatr_filter');
        $changed += ugroup_check_changed('associated', 'sess_uatr_associated');
        $changed += ugroup_check_changed('sort_column', 'sess_uatr_sort_column');
        $changed += ugroup_check_changed('sort_direction', 'sess_uatr_sort_direction');
        if ($changed) {
            $_REQUEST['page'] = '1';
        }

        $reset_multi = false;
	}

    /* remember these search fields in session vars so we don't have to keep passing them around */
    load_current_session_value("page", "sess_uatr_page", "1");
    load_current_session_value("filter", "sess_uatr_filter", "");
    load_current_session_value("associated", "sess_uatr_associated", "false");
    load_current_session_value("rows", "sess_uatr_rows", "-1");
}

function graph_filter($header_label) {
	global $config, $colors, $item_rows;

	?>
	<script type="text/javascript">
	<!--

	function applyFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permsg&id=<?php print get_request_var_request('id');?>'
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&graph_template_id=' + objForm.graph_template_id.value;
		strURL = strURL + '&associated=' + objForm.associated.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	function clearFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permsg&id=<?php print get_request_var_request('id');?>&clearf=true'
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Graph Permissions</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="forms" method="post" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Type:&nbsp;
					</td>
					<td width="1">
						<select name="graph_template_id" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("graph_template_id") == "-1") {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request("graph_template_id") == "0") {?> selected<?php }?>>None</option>
							<?php
							$graph_templates = db_fetch_assoc("SELECT DISTINCT gt.id, gt.name 
								FROM graph_templates AS gt
								INNER JOIN graph_local AS gl
								ON gl.graph_template_id=gt.id
								ORDER BY name");

							if (sizeof($graph_templates)) {
								foreach ($graph_templates as $gt) {
									print "<option value='" . $gt["id"] . "'"; if (get_request_var_request("graph_template_id") == $gt["id"]) { print " selected"; } print ">" . htmlspecialchars($gt["name"]) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>" onChange="applyFilterChange(document.forms)">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onChange='applyFilterChange(document.forms)' <?php print ($_REQUEST['associated'] == 'true' || $_REQUEST['associated'] == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'>Permissions Set</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" value="Go" onClick='applyFilterChange(document.forms)' title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="button" name="clearf" value="Clear" onClick='clearFilterChange(document.forms)' title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<input type='hidden' name='action' value='user_edit'>
			<input type='hidden' name='tab' value='permsg'>
			<input type='hidden' name='id' value='<?php print get_request_var_request('id');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();
}

function device_filter($header_label) {
	global $config, $colors, $item_rows;

	?>
	<script type="text/javascript">
	<!--

	function applyFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permsd&id=<?php print get_request_var_request('id');?>'
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&host_template_id=' + objForm.host_template_id.value;
		strURL = strURL + '&associated=' + objForm.associated.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	function clearFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permsd&id=<?php print get_request_var_request('id');?>&clearf=true'
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Devices Permission</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="forms" method="post" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Type:&nbsp;
					</td>
					<td width="1">
						<select name="host_template_id" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("host_template_id") == "-1") {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request("host_template_id") == "0") {?> selected<?php }?>>None</option>
							<?php
							$host_templates = db_fetch_assoc("select id,name from host_template order by name");

							if (sizeof($host_templates) > 0) {
								foreach ($host_templates as $host_template) {
									print "<option value='" . $host_template["id"] . "'"; if (get_request_var_request("host_template_id") == $host_template["id"]) { print " selected"; } print ">" . htmlspecialchars($host_template["name"]) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>" onChange="applyFilterChange(document.forms)">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onChange='applyFilterChange(document.forms)' <?php print ($_REQUEST['associated'] == 'true' || $_REQUEST['associated'] == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'>Permissions Set</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" value="Go" onClick='applyFilterChange(document.forms)' title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="button" name="clearf" value="Clear" onClick='clearFilterChange(document.forms)' title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<input type='hidden' name='action' value='user_edit'>
			<input type='hidden' name='tab' value='permsd'>
			<input type='hidden' name='id' value='<?php print get_request_var_request('id');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();
}

function template_filter($header_label) {
	global $config, $colors, $item_rows;

	?>
	<script type="text/javascript">
	<!--

	function applyFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permste&id=<?php print get_request_var_request('id');?>'
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&associated=' + objForm.associated.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	function clearFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permste&id=<?php print get_request_var_request('id');?>&clearf=true'
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Template Permission</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="forms" method="post" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>" onChange="applyFilterChange(document.forms)">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onChange='applyFilterChange(document.forms)' <?php print ($_REQUEST['associated'] == 'true' || $_REQUEST['associated'] == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'>Permissions Set</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" value="Go" onClick='applyFilterChange(document.forms)' title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="button" name="clearf" value="Clear" onClick='clearFilterChange(document.forms)' title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<input type='hidden' name='action' value='user_edit'>
			<input type='hidden' name='tab' value='permste'>
			<input type='hidden' name='id' value='<?php print get_request_var_request('id');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();
}

function tree_filter($header_label) {
	global $config, $colors, $item_rows;

	?>
	<script type="text/javascript">
	<!--

	function applyFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permstr&id=<?php print get_request_var_request('id');?>'
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&associated=' + objForm.associated.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	function clearFilterChange(objForm) {
		strURL = '?action=user_edit&tab=permstr&id=<?php print get_request_var_request('id');?>&clearf=true'
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Tree Permission</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="forms" method="post" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>" onChange="applyFilterChange(document.forms)">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onChange='applyFilterChange(document.forms)' <?php print ($_REQUEST['associated'] == 'true' || $_REQUEST['associated'] == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'>Permissions Set</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" value="Go" onClick='applyFilterChange(document.forms)' title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="button" name="clearf" value="Clear" onClick='clearFilterChange(document.forms)' title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<input type='hidden' name='action' value='user_edit'>
			<input type='hidden' name='tab' value='permstr'>
			<input type='hidden' name='id' value='<?php print get_request_var_request('id');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();
}

function member_filter($header_label) {
	global $config, $colors, $item_rows;

	?>
	<script type="text/javascript">
	<!--

	function applyFilterChange(objForm) {
		strURL = '?action=user_edit&tab=members&id=<?php print get_request_var_request('id');?>'
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&associated=' + objForm.associated.checked;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	function clearFilterChange(objForm) {
		strURL = '?action=user_edit&tab=members&id=<?php print get_request_var_request('id');?>&clearf=true'
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Tree Permission</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="forms" method="post" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="20">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>" onChange="applyFilterChange(document.forms)">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;Rows:&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.forms)">
							<option value="-1"<?php if (get_request_var_request("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onChange='applyFilterChange(document.forms)' <?php print ($_REQUEST['associated'] == 'true' || $_REQUEST['associated'] == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'>Permissions Set</label>
					</td>
					<td nowrap>
						&nbsp;<input type="button" value="Go" onClick='applyFilterChange(document.forms)' title="Set/Refresh Filters">
					</td>
					<td nowrap>
						<input type="button" name="clearf" value="Clear" onClick='clearFilterChange(document.forms)' title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<input type='hidden' name='action' value='user_edit'>
			<input type='hidden' name='tab' value='members'>
			<input type='hidden' name='id' value='<?php print get_request_var_request('id');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();
}

?>
