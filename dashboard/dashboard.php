<?php
$guest_account = true;
ob_start();
chdir('../../');
include_once("./include/auth.php");
include_once("./include/global.php");
include_once($config["library_path"] . "/tree.php");
include_once($config["library_path"] . "/api_tree.php");


$action = "";

if (isset($_REQUEST['action']))
{
    $action = $_REQUEST['action'];
}
$user = $_SESSION["sess_user_id"];

switch ($action)
{
    case 'add':
        $graph = 0;
		
        if (isset($_GET['graph_id']))
        {
            $graph = intval($_GET['graph_id']);
            $rra = intval($_GET['rra_id']);
	    $user_id = $_POST['selectedUser'];
            $title = mysql_escape_string(
                db_fetch_cell("select title_cache from graph_templates_graph where local_graph_id=$graph"));
            $SQL =
                "insert into dashboard_graphs (userid,local_graph_id,rra_id,title) values ($user_id,$graph,$rra,'$title');";
            $result = db_execute($SQL);
        }
        header("Location: dashboard_edit.php?action=edit_dash&user_id=$user_id");
	break;

    case 'clear':
	$user = intval($_GET['user_id']);
        $SQL = "delete from  dashboard_graphs where userid=$user;";
        $result = db_execute($SQL);
        header("Location: dashboard_edit.php?action=edit_dash&user_id=$user");
	break;
    case 'remove':
        $graph = 0;
	if (isset($_GET['id']))
        {
            $graph = intval($_GET['id']);
	    $user = intval($_GET['user_id']);
            $SQL = "delete from  dashboard_graphs where userid=$user and id=$graph;";
            $result = db_execute($SQL);
        }
        header("Location: dashboard_edit.php?action=edit_dash&user_id=$user");
	break;

    case 'add_ajax':
        header('Content-type: text/plain');

        print "{ status: 'OK' }";
        break;

    default:
        include_once($config["base_path"] . "/plugins/dashboard/top_dashboard.php");

        
        $SQL = "select g.id, g.name from graph_tree g order by g.name";
        $queryrows = db_fetch_assoc($SQL);
        if (sizeof($queryrows) > 0)
        {
            print "<div id='qt_treeselector'><h3>Add to which graph tree?</h3><form method='post' action='dashboard.php'><input name='action' type='hidden' value='save' /><select name='tree_id'>";
# <option value=1>1<option value=2>2
            foreach ($queryrows as $tr)
            {
                printf("<option value='%d'>%s</option>", $tr['id'], htmlspecialchars($tr['name']));
            }
            print "</select><input type='submit' value='Add to this tree' /></form></div>";
        }
        
        print "This is your dashboard. Theses graphs were selected by the admin according to your profile.<br>";
	print "You can't edit your dashboard, if you want to do so, please contact the admin or create your own dashboard using QuickTree.";
        print "<hr>";
        if (isset($_REQUEST["clear_x"]))        {
        $start=date("Y-m-d H:i", (time()-86400));
        unset($_POST['date1']);
	unset($_POST['date2']);
}

        /* include time span selector */
        if (read_graph_config_option("timespan_sel") == "on") {
                ?>
                <tr bgcolor="#<?php print $colors['panel'];?>" class="noprint">
                        <td class="noprint">
                        <form style='margin:0px;padding:0px;' name='form_timespan_selector' method='post' action='dashboard.php'>
                                <table cellpadding="0" cellspacing="0">
                                        <tr>
                                                </td>

                                                <td nowrap style='white-space: nowrap;'>
                                                        &nbsp;<strong>From:</strong>&nbsp;
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        <input type='text' name='date1' id='date1' title='Graph Begin Timestamp' size='15' value='<?php print (isset($_POST['date1']) ? $_POST['date1'] : date("Y-m-d H:i", (time()-86400)));?>'>
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        &nbsp;<input type='image' src='images/calendar.gif' align='middle' alt='Start date selector' title='Start date selector' onclick="return showCalendar('date1');">
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        &nbsp;<strong>To:</strong>&nbsp;
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        <input type='text' name='date2' id='date2' title='Graph End Timestamp' size='15' value='<?php print (isset($_POST['date2']) ? $_POST['date2'] : date("Y-m-d H:i", time()));?>'>
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        &nbsp;<input type='image' src='images/calendar.gif' align='middle' alt='End date selector' title='End date selector' onclick="return showCalendar('date2');">
                                                </td>
                                                <td nowrap style='white-space: nowrap;'>
                                                        &nbsp;<input type='submit' name='button_refresh_x' value='Refresh' title='Refresh selected time span'>
                                                        <input type='submit' name='clear_x' value='Clear' title='Return to the default time span'>
                                                </td>
                                        </tr>
                                </table>
                        </form>
                        </td>
                </tr>
                <?php
        }
	$start=$_POST['date1'];
	$end=$_POST['date2'];
        $data_start = explode("-", $start);
	$horario_start = explode(" ", $start);
	$ano_start = $data_start[0];
	$mes_start = $data_start[1];
	$dias_start = explode(" ", $data_start[2]);
	$dia_start = $dias_start[0];
	$horas_start = explode(":", $horario_start[1]);
	$hora_start = $horas_start[0];
	$min_start = $horas_start[1];
        $data_end = explode("-", $end);
        $horario_end = explode(" ", $end);
        $ano_end = $data_end[0];
        $mes_end = $data_end[1];
        $dias_end = explode(" ", $data_end[2]);
        $dia_end = $dias_end[0];
        $horas_end = explode(":", $horario_end[1]);
        $hora_end = $horas_end[0];
        $min_end = $horas_end[1];
 	$ep_start = mktime($hora_start,$min_start,0,$mes_start,$dia_start,$ano_start);
	$ep_end = mktime($hora_end,$min_end,0,$mes_end,$dia_end,$ano_end);
        if ($ep_start != "") {
	}	
	else{
		$ep_start = (time()-86400);
		$ep_end = time();
	}
	$num_col = 2;
        if ($num_col != ""){
	}
	else{
		$num_col = 1;
	}
	$SQL =
            "select qt.*, gtg.title_cache from dashboard_graphs qt,graph_templates_graph gtg where qt.local_graph_id = gtg.local_graph_id and userid="
            . $user;
        $queryrows = db_fetch_assoc($SQL);
        if (sizeof($queryrows) > 0)
        {	
	    $counter = 1;
            foreach ($queryrows as $gr)
            {
                # print $gr['local_graph_id']."/".$gr['rra_id'];
#                $graph_title = $gr['title_cache'];
#               print "<table><thead><tr><th>";
#     	        print htmlspecialchars($graph_title);
#               print "&nbsp;&nbsp;<a href='dashboard.php?action=remove&id=" . $gr['id']
#                    . "'><img border=0 src='images/delete.png' title='Remove This Graph From Dashboard'></a>";
#	                print "</th></tr></thead>\n";
#		if ($num_col != 1){
			if ($counter % $num_col != 0){
	        	        print "<table><tbody><tr><td>";
			}
#		}else{
#			print "<table><tbody><tr><td>";
#		}
		print "<th>";
?>
            <a href = "../../graph.php?action=view&rra_id=all&local_graph_id=<?php print $gr['local_graph_id']; ?>"><img class = 'graphimage'
                id = 'graph_<?php print $gr["local_graph_id"] ?>'
                src = "../../graph_image.php?action=view&local_graph_id=<?php print $gr['local_graph_id'];?>&rra_id=<?php print $gr['rra_id'];?>&graph_start=<?php print $ep_start;?>&graph_end=<?php print $ep_end;?>&graph_height=80&graph_width=400"
                border = '0' alt = '<?php print $graph_title;?>'></a>

<?php
		print "</th>";
		    if ($counter % $num_col == 0){
	         	   print "</td></tr></tbody></table>\n";
		    }
		$counter = $counter + 1;
            }

            print "<hr>";
        }
        else
        {
            print "<p><em>No graphs yet</em></p>";
        }

        include_once($config["base_path"] . "/include/bottom_footer.php");
        break;
}
?>
