<?php

// ******************************************
// sensible defaults
$mapdir='configs';
$cacti_base = '../../';
$cacti_url = '/';
$ignore_cacti=FALSE;

@include_once('editor-config.php');

if( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
{
	// include the cacti-config, so we know about the database
	include_once($cacti_base."/include/config.php");

	// CHANGE: this to be the URL of the base of your Cacti install
	// it MUST end with a / character!
	$config['base_url']=$cacti_url;
	$cacti_found=TRUE;
}
else
{
	$cacti_found = FALSE;
}

// ******************************************

function js_escape($str)
{
	$str = str_replace('\\', '\\\\', $str);
	$str = str_replace("'", "\\\'", $str);

	$str = "'".$str."'";

	return($str);
}

if(isset($_REQUEST['command']) && $_REQUEST["command"]=='link_step2')
{
	$dataid = intval($_REQUEST['dataid']);

	$SQL_graphid = "select graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd where graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  and task_item_id=data_template_rrd.id and local_data_id=$dataid LIMIT 1;";

	$link = mysql_connect($database_hostname,$database_username,$database_password)
		or die('Could not connect: ' . mysql_error());
	mysql_selectdb($database_default,$link) or die('Could not select database: '.mysql_error());

	$result = mysql_query($SQL_graphid) or die('Query failed: ' . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$graphid = $line['local_graph_id'];

?>
<html>
<head>
	<script type="text/javascript">
	function update_source_step2(graphid)
	{
		var graph_url, hover_url;

		var base_url = '<?php echo isset($config['base_url'])?$config['base_url']:''; ?>';

		if (typeof window.opener == "object") {

			graph_url = base_url + 'graph_image.php?local_graph_id=' + graphid + '&rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300';
			info_url = base_url + 'graph.php?rra_id=all&local_graph_id=' + graphid;

			opener.document.forms["frmMain"].link_infourl.value = info_url;
			opener.document.forms["frmMain"].link_hover.value = graph_url;
		}
		self.close();
	}

	window.onload = update_source_step2(<?php echo $graphid ?>);

	</script>
</head>
<body>
This window should disappear in a moment.
</body>
</html>
<?php
	// end of link step 2
}

if(isset($_REQUEST['command']) && $_REQUEST["command"]=='link_step1')
{
?>
<html>
<head>
	<script type="text/javascript" src="lib/javascript/jquery-latest.pack.js"></script>
	<script type="text/javascript">

	function filterlist(previous)
	{
		var filterstring = $('input#filterstring').val();	
		
		if(filterstring=='')
		{
			$('ul#dslist > li').show();
			return;
		}
		
		if(filterstring!=previous)
		{	
				$('ul#dslist > li').hide();
				$('ul#dslist > li').contains(filterstring).show();
		}
	}

	$(document).ready( function() {
		$('span.filter').keyup(function() {
			var previous = $('input#filterstring').val();
			setTimeout(function () {filterlist(previous)}, 500);
		}).show();
	});

	function update_source_step1(dataid,datasource)
	{
		var newlocation;
		var fullpath;

		var rra_path = <?php echo js_escape($config['base_path']); ?>;

		if (typeof window.opener == "object") {
			fullpath = datasource.replace(/<path_rra>/, rra_path + '/rra');

			opener.document.forms["frmMain"].link_target.value = fullpath;
		}
		if(document.forms['mini'].overlib.checked)
		{
			newlocation = 'cacti-pick.php?command=link_step2&dataid=' + dataid;
			self.location = newlocation;
		}
		else
		{
			self.close();
		}
	}
	</script>
<style type="text/css">
body { font-family: sans-serif; font-size: 10pt; }
ul { list-style: none;  margin: 0; padding: 0; }
ul { border: 1px solid black; }
ul li.row0 { background: #ddd;}
ul li.row1 { background: #ccc;}
ul li { border-bottom: 1px solid #aaa; border-top: 1px solid #eee; padding: 2px;}
ul li a { text-decoration: none; color: black; }
</style>
<title>Pick a data source</title>
</head>
<body>
<?php

	# print "Cacti is ".$config["cacti_version"] = "0.8.6g";

#	$SQL_picklist = "select data_template_data.local_data_id, data_template_data.name_cache, data_template_data.active, data_template_data.data_source_path from data_local,data_template_data,data_input,data_template  left join data_input on data_input.id=data_template_data.data_input_id left join data_template on data_local.data_template_id=data_template.id where data_local.id=data_template_data.local_data_id order by name_cache;";
	$SQL_picklist = "select data_template_data.local_data_id, data_template_data.name_cache, data_template_data.active, data_template_data.data_source_path from data_local,data_template_data,data_input,data_template where data_local.id=data_template_data.local_data_id and data_input.id=data_template_data.data_input_id and data_local.data_template_id=data_template.id order by name_cache;";
	#$link = mysql_connect($database_hostname,$database_username,$database_password)
	#  or die('Could not connect: ' . mysql_error());
	#  mysql_selectdb($database_default,$link) or die('Could not select database: '.mysql_error());

	#$result = mysql_query($SQL_picklist) or die('Query failed: ' . mysql_error());
?>
<h3>Pick a data source:</h3>

<form name="mini">
<span class="filter" style="display: none;">Filter: <input id="filterstring" name="filterstring" size="20"> (case-sensitive)<br /></span>
<input name="overlib" type="checkbox" value="yes" checked> Also set OVERLIBGRAPH and INFOURL.
</form>
<div class="listcontainer">
<ul id="dslist">
<?php
	$queryrows = db_fetch_assoc($SQL_picklist);

	// print $SQL_picklist;

	$i=0;
	if( is_array($queryrows) )
	{
		foreach ($queryrows as $line) {
			//while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<li class=\"row".($i%2)."\">";
			$key = $line['local_data_id']."','".$line['data_source_path'];
			echo "<a href=\"#\" onclick=\"update_source_step1('$key')\">". $line['name_cache'] . "</a>";
			echo "</li>\n";
			
			$i++;
		}
	}
	else
	{
		print "<li>No results...</li>";
	}

	// Free resultset
	//mysql_free_result($result);

	// Closing connection
	//mysql_close($link);

?>
</ul>
</div>
</body>
</html>
<?php
} // end of link step 1

if(isset($_REQUEST['command']) && $_REQUEST["command"]=='node_step1')
{
?>
<html>
<head>
	<script type="text/javascript">

	function update_source_step1(graphid)
	{
		var graph_url, hover_url;

		var base_url = '<?php echo isset($config['base_url'])?$config['base_url']:''; ?>';

		if (typeof window.opener == "object") {

			graph_url = base_url + 'graph_image.php?local_graph_id=' + graphid + '&rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300';
			info_url = base_url + 'graph.php?rra_id=all&local_graph_id=' + graphid;

			opener.document.forms["frmMain"].node_infourl.value = info_url;
			opener.document.forms["frmMain"].node_hover.value = graph_url;
		}
		self.close();		
	}
	</script>
<style type="text/css">
body { font-family: sans-serif; font-size: 10pt; }
ul { list-style: none;  margin: 0; padding: 0; }
ul { border: 1px solid black; }
ul li.row0 { background: #ddd;}
ul li.row1 { background: #ccc;}
ul li { border-bottom: 1px solid #aaa; border-top: 1px solid #eee; padding: 2px;}
ul li a { text-decoration: none; color: black; }
</style>
<title>Pick a data source</title>
</head>
<body>

<?php

	# print "Cacti is ".$config["cacti_version"] = "0.8.6g";

#	$SQL_picklist = "select data_template_data.local_data_id, data_template_data.name_cache, data_template_data.active, data_template_data.data_source_path from data_local,data_template_data,data_input,data_template  left join data_input on data_input.id=data_template_data.data_input_id left join data_template on data_local.data_template_id=data_template.id where data_local.id=data_template_data.local_data_id order by name_cache;";
#	$SQL_picklist = "select data_template_data.local_data_id, data_template_data.name_cache, data_template_data.active, data_template_data.data_source_path from data_local,data_template_data,data_input,data_template where data_local.id=data_template_data.local_data_id and data_input.id=data_template_data.data_input_id and data_local.data_template_id=data_template.id order by name_cache;";
	$SQL_picklist = "SELECT graph_templates_graph.id, graph_templates_graph.local_graph_id, graph_templates_graph.height, graph_templates_graph.width, graph_templates_graph.title_cache, graph_templates.name, graph_local.host_id	FROM (graph_local,graph_templates_graph) LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) WHERE graph_local.id=graph_templates_graph.local_graph_id order by title_cache";
	#$link = mysql_connect($database_hostname,$database_username,$database_password)
	#  or die('Could not connect: ' . mysql_error());
	#  mysql_selectdb($database_default,$link) or die('Could not select database: '.mysql_error());

	#$result = mysql_query($SQL_picklist) or die('Query failed: ' . mysql_error());
?>
<h3>Pick a graph:</h3>

<ul>
<?php
	$queryrows = db_fetch_assoc($SQL_picklist);

	// print $SQL_picklist;

	$i=0;
	if( is_array($queryrows) )
	{
		foreach ($queryrows as $line) {
			//while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<li class=\"row".($i%2)."\">";
			$key = $line['local_graph_id'];
			echo "<a href=\"#\" onclick=\"update_source_step1('$key')\">". $line['title_cache'] . "</a>";
			echo "</li>\n";
			$i++;
		}
	}
	else
	{
		print "No results...";
	}

	// Free resultset
	//mysql_free_result($result);

	// Closing connection
	//mysql_close($link);
?>
</ul>
</body>
</html>
<?php
} // end of node step 1

// vim:ts=4:sw=4:
?>
