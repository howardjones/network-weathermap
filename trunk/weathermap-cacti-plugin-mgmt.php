<?php

chdir('../../');
include_once("./include/auth.php");
include_once("./include/config.php");

include_once($config["library_path"] . "/database.php");

$weathermap_confdir = realpath(dirname(__FILE__).'/configs');

$action = "";
if (isset($_POST['action'])) {
	$action = $_POST['action'];
} else if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

switch ($action) {
case 'perms_add_user':
	if( isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])
		&& isset($_REQUEST['userid']) && is_numeric($_REQUEST['userid'])
		)
	{
		perms_add_user($_REQUEST['mapid'],$_REQUEST['userid']);
		header("Location: weathermap-cacti-plugin-mgmt.php?action=perms_edit&id=".$_REQUEST['mapid']);
	}
	break;
case 'perms_delete_user':
	if( isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])
		&& isset($_REQUEST['userid']) && is_numeric($_REQUEST['userid'])
		)
	{
		perms_delete_user($_REQUEST['mapid'],$_REQUEST['userid']);
		header("Location: weathermap-cacti-plugin-mgmt.php?action=perms_edit&id=".$_REQUEST['mapid']);
	}
	break;
case 'perms_edit':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) )
	{
		include_once($config["base_path"]."/include/top_header.php");
		perms_list($_REQUEST['id']);
		include_once($config["base_path"]."/include/bottom_footer.php");
	}
	else
	{
		print "Something got lost back there.";
	}
	break;

case 'delete_map':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ) map_delete($_REQUEST['id']);
	header("Location: weathermap-cacti-plugin-mgmt.php");
	break;

case 'deactivate_map':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ) map_deactivate($_REQUEST['id']);
	header("Location: weathermap-cacti-plugin-mgmt.php");
	break;

case 'activate_map':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ) map_activate($_REQUEST['id']);
	header("Location: weathermap-cacti-plugin-mgmt.php");
	break;

case 'move_map_up':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) &&
		isset($_REQUEST['order']) && is_numeric($_REQUEST['order']) )
		map_move($_REQUEST['id'],$_REQUEST['order'],-1);
	header("Location: weathermap-cacti-plugin-mgmt.php");
	break;
case 'move_map_down':
	if( isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) &&
		isset($_REQUEST['order']) && is_numeric($_REQUEST['order']) )
		map_move($_REQUEST['id'],$_REQUEST['order'],+1);
	header("Location: weathermap-cacti-plugin-mgmt.php");
	break;

case 'viewconfig':
	include_once($config["base_path"]."/include/top_graph_header.php");
	if(isset($_REQUEST['file']))
	{
		preview_config($_REQUEST['file']);
	}
	else
	{
		print "No such file.";
	}
	include_once($config["base_path"]."/include/bottom_footer.php");
	break;

case 'addmap_picker':
	include_once($config["base_path"]."/include/top_header.php");
	addmap_picker();
	include_once($config["base_path"]."/include/bottom_footer.php");
	break;

case 'addmap':
	if(isset($_REQUEST['file']))
	{
		add_config($_REQUEST['file']);
		header("Location: weathermap-cacti-plugin-mgmt.php");
	}
	else
	{
		print "No such file.";
	}

	break;

case 'editor':
	chdir(dirname(__FILE__));
	include_once('./editor.php');
	break;

case 'rebuildnow':
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."Weathermap.class.php");
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."poller-common.php");

		include_once($config["base_path"]."/include/top_header.php");
	print "<h3>Rebuilding all maps</h3><strong>NOTE: Because your Cacti poller process probably doesn't run as the same user as your webserver, it's possible this will fail with file permission problems even though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if your mod_php/ISAPI module uses a different php.ini to your command-line PHP.</strong><hr><pre>";
	weathermap_run_maps(dirname(__FILE__));
	print "</pre>";
	print "<hr /><h3>Done.</h3>";
	include_once($config["base_path"]."/include/bottom_footer.php");

		break;

	// by default, just list the map setup
default:
	include_once($config["base_path"]."/include/top_header.php");
	maplist();
	include_once($config["base_path"]."/include/bottom_footer.php");
	break;
}

///////////////////////////////////////////////////////////////////////////

// Repair the sort order column (for when something is deleted or inserted)
function map_resort()
{
	$list = db_fetch_assoc("select * from weathermap_maps order by sortorder;");
	$i = 1;
	foreach ($list as $map)
	{
		$sql[] = "update weathermap_maps set sortorder = $i where id = ".$map['id'];
		$i++;
	}
	if (!empty($sql)) {
		for ($a = 0; $a < count($sql); $a++) {
			$result = db_execute($sql[$a]);
		}
	}
}

function map_move($mapid,$junk,$direction)
{
	$source = db_fetch_assoc("select * from weathermap_maps where id=$mapid");
	$oldorder = $source[0]['sortorder'];

	$neworder = $oldorder + $direction;
	$target = db_fetch_assoc("select * from weathermap_maps where sortorder = $neworder");

	if(!empty($target[0]['id']))
	{
		$otherid = $target[0]['id'];
		// move $mapid in direction $direction
		$sql[] = "update weathermap_maps set sortorder = $neworder where id=$mapid";
		// then find the other one with the same sortorder and move that in the opposite direction
		$sql[] = "update weathermap_maps set sortorder = $oldorder where id=$otherid";
	}
	if (!empty($sql)) {
		for ($a = 0; $a < count($sql); $a++) {
			$result = db_execute($sql[$a]);
		}
	}
}

function maplist()
{
	global $colors;

	html_start_box("<strong>Weathermaps</strong>", "78%", $colors["header"], "3", "center", "weathermap-cacti-plugin-mgmt.php?action=addmap_picker");

	html_header(array("Config File", "Title", "Active", "Sort Order", "Accessible By",""));

	$query = db_fetch_assoc("select id,username from user_auth");
	$users[0] = 'Anyone';

	foreach ($query as $user)
	{
		$users[$user['id']] = $user['username'];
	}

	$i = 0;
	$queryrows = db_fetch_assoc("select * from weathermap_maps order by sortorder");
	// or die (mysql_error("Could not connect to database") )

	$previous_id = -2;
	if( is_array($queryrows) )
	{
		foreach ($queryrows as $map)
		{
			form_alternate_row_color($colors["alternate"],$colors["light"],$i);

			print '<td><a href="editor.php?plug=1&mapname='.htmlspecialchars($map['configfile']).'">'.htmlspecialchars($map['configfile']).'</a>';
			print '<a href="?action=editor&plug=1&mapname='.htmlspecialchars($map['configfile']).'">[edit]</a></td>';
			print '<td>'.htmlspecialchars($map['titlecache']).'</td>';
			if($map['active'] == 'on')
			{
				print '<td><a href="?action=deactivate_map&id='.$map['id'].'"><font color="green">Yes</font></a></td>';
			}
			else
			{
				print '<td><a href="?action=activate_map&id='.$map['id'].'"><font color="red">No</font></a></td>';
			}

			print '<td>';

			print '<a href="?action=move_map_up&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Map Up"></a>';
			print '<a href="?action=move_map_down&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Map Down"></a>';
// print $map['sortorder'];

			print "</td>";

			print '<td>';
			$UserSQL = 'select * from weathermap_auth where mapid='.$map['id'].' order by userid';
			$userlist = db_fetch_assoc($UserSQL);

			$mapusers = array();
			foreach ($userlist as $user)
			{
				if(array_key_exists($user['userid'],$users))
				{
					$mapusers[] = $users[$user['userid']];
				}
			}

			print '<a href="?action=perms_edit&id='.$map['id'].'">';
			if(count($mapusers) == 0)
			{
				print "(no users)";
			}
			else
			{
				print join(", ",$mapusers);
			}
			print '</a>';

			print '</td>';
			//  print '<td><a href="?action=editor&mapname='.urlencode($map['configfile']).'">Edit Map</a></td>';
			print '<td>';
			print '<a href="?action=delete_map&id='.$map['id'].'"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Delete Map"></a>';
			print '</td>';

			print '</tr>';
			$i++;
		}
	}

	if($i==0)
	{
		print "<tr><td><em>No Weathermaps Configured</em></td></tr>\n";
	}

	html_end_box();

	if($i>0)
	{
		print '<div align="center"><a href="?action=rebuildnow"><img src="images/btn_recalc.png" border="0" alt="Rebuild All Maps Right Now"><br />(Experimental - You should NOT need to use this normally)</a></div>';
	}


}

function addmap_picker()
{
	global $weathermap_confdir;
	global $colors;

	$loaded=array();
	// find out what maps are already in the database, so we can skip those
	$queryrows = db_fetch_assoc("select * from weathermap_maps");
	if( is_array($queryrows) )
	{
		foreach ($queryrows as $map)
		{
			$loaded[]=$map['configfile'];
		}
	}

	html_start_box("<strong>Available Weathermap Configuration Files</strong>", "78%", $colors["header"], "2", "center", "");

	if( is_dir($weathermap_confdir))
	{
		$n=0;
		$dh = opendir($weathermap_confdir);
		if($dh)
		{
			$i = 0; $skipped = 0;
			html_header(array("Config File", "Title",""),2);

			while($file = readdir($dh))
			{
				$realfile = $weathermap_confdir.'/'.$file;
				if(is_file($realfile) && ! in_array($file,$loaded) )
				{
					if(in_array($file,$loaded))
					{
						$skipped++;
					}
					else
					{
						

						$title = wmap_get_title($realfile);
						$titles[$file] = $title;
						

						$i++;
					}
				}
			}
			closedir($dh);
			
			if($i>0)
			{
				ksort($titles);
			
				$i=0;
				foreach ($titles as $file=>$title)
				{
					$title = $titles[$file];
					form_alternate_row_color($colors["alternate"],$colors["light"],$i);
					print '<td>'.htmlspecialchars($file).'</td>';
					print '<td><em>'.htmlspecialchars($title).'</em></td>';
					print '<td><a href="?action=viewconfig&amp;file='.$file.'" title="View the configuration file in a new window" target="_blank">View</a></td>';
					print '<td><a href="?action=addmap&amp;file='.$file.'" title="Add the configuration file">Add</a></td>';
					print '</tr>';
					$i++;
				}
			}
			
			if( ($i + $skipped) == 0 )
			{
				print "<tr><td>No files were found in the configs directory.</td></tr>";
			}		

			if( ($i == 0) && $skipped>0)
			{
				print "<tr><td>($skipped files weren't shown because they are already in the database)</td></tr>";
			}
		}
		else
		{
			print "<tr><td>Can't open $weathermap_confdir to read - you should set it to be readable by the webserver.</td></tr>";
		}
	}
	else
	{
		print "<tr><td>There is no directory named $weathermap_confdir - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.</td></tr>";
	}

	html_end_box();

}

function preview_config($file)
{
	global $weathermap_confdir;
	global $colors;

	chdir($weathermap_confdir);

	$path_parts = pathinfo($file);
	$file_dir = realpath($path_parts['dirname']);

	if($file_dir != $weathermap_confdir)
	{
		// someone is trying to read arbitrary files?
		// print "$file_dir != $weathermap_confdir";
		print "<h3>Path mismatch</h3>";
	}
	else
	{
		html_start_box("<strong>Preview of $file</strong>", "98%", $colors["header"], "3", "center", "");

		print '<tr><td valign="top" bgcolor="#'.$colors["light"].'" class="textArea">';
		print '<pre>';
		$realfile = $weathermap_confdir.'/'.$file;
		if( is_file($realfile) )
		{
			$fd = fopen($realfile,"r");
			while (!feof($fd))
			{
				$buffer = fgets($fd,4096);
				print $buffer;
			}
			fclose($fd);
		}
		print '</pre>';
		print '</td></tr>';
		html_end_box();
	}
}

function add_config($file)
{
	global $weathermap_confdir;
	global $colors;

	chdir($weathermap_confdir);

	$path_parts = pathinfo($file);
	$file_dir = realpath($path_parts['dirname']);

	if($file_dir != $weathermap_confdir)
	{
		// someone is trying to read arbitrary files?
		// print "$file_dir != $weathermap_confdir";
		print "<h3>Path mismatch</h3>";
	}
	else
	{
		$realfile = $weathermap_confdir.DIRECTORY_SEPARATOR.$file;
		$title = wmap_get_title($realfile);

		$file = mysql_real_escape_string($file);
		$title = mysql_real_escape_string($title);
		$SQL = "insert into weathermap_maps (configfile,titlecache,active,imagefile,htmlfile) VALUES ('$file','$title','on','','')";
		db_execute($SQL);

		// add auth for 'admin'
		$last_id = mysql_insert_id();
		$myuid = (int)$_SESSION["sess_user_id"];
		$SQL = "insert into weathermap_auth (mapid,userid) VALUES ($last_id,$myuid)";
		db_execute($SQL);

		map_resort();
	}
}

function wmap_get_title($filename)
{
	$title = "(no title)";
	$fd = fopen($filename,"r");
	while (!feof($fd))
	{
		$buffer = fgets($fd,4096);
		if(preg_match("/^\s*TITLE\s+(.*)/i",$buffer, $matches))
		{
			$title = $matches[1];
		}
		// this regexp is tweaked from the ReadConfig version, to only match TITLEPOS lines *with* a title appended
		if(preg_match("/^\s*TITLEPOS\s+\d+\s+\d+\s+(.+)/i",$buffer, $matches))
		{
			$title = $matches[1];
		}
	}
	fclose($fd);

	return($title);
}

function map_deactivate($id)
{
	$SQL = "update weathermap_maps set active='off' where id=".$id;
	db_execute($SQL);
}

function map_activate($id)
{
	$SQL = "update weathermap_maps set active='on' where id=".$id;
	db_execute($SQL);
}

function map_delete($id)
{
	$SQL = "delete from weathermap_maps where id=".$id;
	db_execute($SQL);

	$SQL = "delete from weathermap_auth where mapid=".$id;
	db_execute($SQL);

	map_resort();
}

function perms_add_user($mapid,$userid)
{
	$SQL = "insert into weathermap_auth (mapid,userid) values($mapid,$userid)";
	db_execute($SQL);
}

function perms_delete_user($mapid,$userid)
{
	$SQL = "delete from weathermap_auth where mapid=$mapid and userid=$userid";
	db_execute($SQL);
}

function perms_list($id)
{
	global $colors;

	$title_sql = "select titlecache from weathermap_maps where id=$id";
	$results = db_fetch_assoc($title_sql);
	$title = $results[0]['titlecache'];

	$auth_sql = "select * from weathermap_auth where mapid=$id order by userid";

	$query = db_fetch_assoc("select id,username from user_auth order by username");
	$users[0] = 'Anyone';
	foreach ($query as $user)
	{
		$users[$user['id']] = $user['username'];
	}

	$auth_results = db_fetch_assoc($auth_sql);
	$mapusers = array();
	$mapuserids = array();
	foreach ($auth_results as $user)
	{
		if(isset($users[$user['userid']]))
		{
			$mapusers[] = $users[$user['userid']];
			$mapuserids[] = $user['userid'];
		}
	}

	$userselect="";
	foreach ($users as $uid => $name)
	{
		if(! in_array($uid,$mapuserids))    $userselect .= "<option value=\"$uid\">$name</option>\n";
	}

	html_start_box("<strong>Edit permissions for Weathermap $id: $title</strong>", "70%", $colors["header"], "2", "center", "");
	html_header(array("Username", ""));

	$n = 0;
	foreach($mapuserids as $user)
	{
		form_alternate_row_color($colors["alternate"],$colors["light"],$n);
		print "<td>".$users[$user]."</td>";
		print '<td><a href="?action=perms_delete_user&mapid='.$id.'&userid='.$user.'"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Remove permissions for this user to see this map"></a></td>';

		print "</tr>";
		$n++;
	}
	if($n==0)
	{
		print "<tr><td><em><strong>nobody</strong> can see this map</em></td></tr>";
	}
	html_end_box();

	html_start_box("", "70%", $colors["header"], "3", "center", "");
	print "<tr>";
	if($userselect == '')
	{
		print "<td><em>There aren't any users left to add!</em></td></tr>";
	}
	else
	{
		print "<td><form action=\"\">Allow <input type=\"hidden\" name=\"action\" value=\"perms_add_user\"><input type=\"hidden\" name=\"mapid\" value=\"$id\"><select name=\"userid\">";
		print $userselect;
		print "</select> to see this map <input type=\"submit\" value=\"Update\"></form></td>";
		print "</tr>";
	}
	html_end_box();
}
// vim:ts=4:sw=4:
?>
