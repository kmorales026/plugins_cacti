<?php
$guest_account = true;

chdir('../../');

include_once("./include/auth.php");
include_once("./include/global.php");
include_once($config["library_path"] . "/tree.php");
include_once($config["library_path"] . "/api_tree.php");


$action = "";
include_once($config["base_path"] . "/plugins/dashboard/top_dashboard.php");


        $SQL = "select g.id, g.username from user_auth g order by g.username";
        $queryrows = db_fetch_assoc($SQL);
        $graph = intval($_GET['graph_id']);
        $rra = intval($_GET['rra_id']);
        if (sizeof($queryrows) > 0)
        {
            print "<h3>Add to which user Dashboard?</h3><form method='post' action='dashboard.php?action=add&rra_id=$rra&graph_id=$graph'><select name=selectedUser>";
	    printf("<option value=''</option>");
            foreach ($queryrows as $tr)
            {
                printf("<option value='%d'>%s</option>", $tr['id'], htmlspecialchars($tr['username']));
            }
		        $user_id = intval($_POST['selectedUser']);

		print '<input type=submit value=Add title="Add this graph to Dashboard"></form>';
        }

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
	    $user_id = ($_POST['selectedUser']);
     	    $title = mysql_escape_string(
                db_fetch_cell("select title_cache from graph_templates_graph where local_graph_id=$graph"));
            $SQL =
                "insert into dashboard_graphs (userid,local_graph_id,rra_id,title) values ($user_id,$graph,$rra,'$title');";
            $result = db_execute($SQL);
       }
        header("Location: dashboard.php");
        break;
}
?>
