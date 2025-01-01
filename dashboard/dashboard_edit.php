<?php
$guest_account = true;
ob_start();
chdir('../../');

include_once("./include/auth.php");
include_once("./include/global.php");
include_once($config["library_path"] . "/tree.php");
include_once($config["library_path"] . "/api_tree.php");


        include_once($config["base_path"] . "/include/top_header.php");
        print "This is your dashboard admin page. You can edit the dashboards here.<br>";
        print "<hr>";
        $SQL = "select g.id, g.username from user_auth g order by g.username";
        $queryrows = db_fetch_assoc($SQL);
        if (sizeof($queryrows) > 0)
        {
            print "Dashoboard:<div id='qt_treeselector'><form method='post' action='dashboard_edit.php?action=edit_dash'><select name='selectedDash'>";
	    printf("<option value=''</option>");
            foreach ($queryrows as $tr)
            {
                printf("<option value='%d'>%s</option>", $tr['id'], htmlspecialchars($tr['username']));
            }
            print "</select><input type='submit' value='Edit' /></form></div>";
        }
#        print "<hr>";



if (isset($_REQUEST['action']))
{
    $action = $_REQUEST['action'];
}
switch ($action)
{
	case 'add':
        $graph_id = ($_POST['selectedGraph']);
	if (isset($graph_id)){
		$user_id = intval($_GET['user_id']);
		$rra = 0;
		$title = mysql_escape_string(
	                db_fetch_cell("select title_cache from graph_templates_graph where local_graph_id=$graph_id"));
        	    	$SQL =
	                	"insert into dashboard_graphs (userid,local_graph_id,rra_id,title) values ($user_id,$graph_id,$rra,'$title');";
        	    	$result = db_execute($SQL);
			$msg = 1;
	}
	else $msg = 0;
	header("Location: dashboard_edit.php?action=edit_dash&user_id=$user_id&status=$msg");
	break;


	case 'edit_dash':
	if (isset($_GET['user_id']))
	{	
		$user_id = intval($_GET['user_id']);
		if ($_GET['msg']= 1){
			$status="grafico inserido com sucesso!";
		}
	}
	else
	{
		$user_id = ($_POST['selectedDash']);
	}
        $SQL = "select g.local_graph_id, g.title from graph_templates_graph g order by g.title";
        $queryrows = db_fetch_assoc($SQL);
        if (sizeof($queryrows) > 0)
        {
            print "Add graph:<div id='qt_graphselector'><form method='post' action='dashboard_edit.php?action=add&user_id=$user_id'><select name='selectedGraph'>";
            printf("<option value=''</option>");
            foreach ($queryrows as $tr)
            {
                printf("<option value='%d'>%s</option>", $tr['local_graph_id'], htmlspecialchars($tr['title']));
            }
            print "</select><input type='submit' value='Add' /></form></div>";
        }
        print "<h3>$status</h3>";
        print "<hr>";

        $sql_user_name = 
		"select g.id, g.username from user_auth g where id=". $user_id;
        $username = db_fetch_assoc($sql_user_name);
	foreach ($username as $xr)
	{
		printf("<h3>Dashboard %s </h3> ", $xr['username']);
	}
        $SQL =
            "select qt.*, gtg.title_cache from dashboard_graphs qt,graph_templates_graph gtg where qt.local_graph_id = gtg.local_graph_id and userid="
            . $user_id;
	print "<a href='dashboard.php?action=clear&user_id=" . $user_id 
		."' >clear all the graphs from this Dashboard</a>.";
        $queryrows = db_fetch_assoc($SQL);

        if (sizeof($queryrows) > 0)
        {
            foreach ($queryrows as $gr)
            {
                $graph_title = $gr['title_cache'];
                print "<table><thead><tr><th>";
                print htmlspecialchars($graph_title);
                print "&nbsp;&nbsp;<a href='dashboard.php?action=remove&id=" . $gr['id'] . "&user_id=" . $user_id
                    . "'><img border=0 src='images/delete.png' title='Remove This Graph From Dashboard'></a>";
                print "</th></tr></thead>\n";
                print "<tbody><tr><td>";
?>

            <a href = "../../graph.php?action=view&rra_id=all&local_graph_id=<?php print $gr['local_graph_id']; ?>"><img class = 'graphimage'
                id = 'graph_<?php print $gr["local_graph_id"] ?>'
                src = '../../graph_image.php?action=view&local_graph_id=<?php print $gr["local_graph_id"];?>&rra_id=<?php print $gr["rra_id"];?>'
                border = '0' alt = '<?php print $graph_title;?>'></a>

<?php
            print "</td></tr></tbody></table>\n";
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
