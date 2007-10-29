<?php
/*******************************************************************************

	Author ......... Howard Jones
	Contact ........ howie@thingy.com
	Home Site ...... http://wotsit.thingy.com/haj/
	Program ........ Network Weathermap for Cacti
	Version ........ See code below
	Purpose ........ Network Usage Overview

*******************************************************************************/

function weathermap_version () {
	return array( 	'name'    	=> 'weathermap',
		'version'       => '0.94dev',
		'longname'      => 'PHP Network Weathermap',
		'author'        => 'Howard Jones',
		'homepage'      => 'http://www.network-weathermap.com/',
		'email' 	=> 'howie@thingy.com',
		'url'           => 'http://www.network-weathermap.com/versions.php'
	);
}

function plugin_init_weathermap() {
	global $plugin_hooks;
	$plugin_hooks['top_header_tabs']['weathermap'] = 'weathermap_show_tab';
	$plugin_hooks['top_graph_header_tabs']['weathermap'] = 'weathermap_show_tab';

	$plugin_hooks['config_arrays']['weathermap'] = 'weathermap_config_arrays';
	$plugin_hooks['draw_navigation_text']['weathermap'] = 'weathermap_draw_navigation_text';
	$plugin_hooks['config_settings']['weathermap'] = 'weathermap_config_settings';
	$plugin_hooks['poller_bottom']['weathermap'] = 'weathermap_poller_bottom';
	$plugin_hooks['poller_output']['weathermap'] = 'weathermap_poller_output';

	$plugin_hooks['top_graph_refresh']['weathermap'] = 'weathermap_top_graph_refresh';
}

function weathermap_top_graph_refresh($refresh)
{
	if (basename($_SERVER["PHP_SELF"]) != "weathermap-cacti-plugin.php")
		return $refresh;

	// if we're cycling maps, then we want to handle reloads ourselves, thanks
	if($_REQUEST["action"] == 'viewmapcycle')
	{
		return(86400);
	}
	return ($refresh);
}

function weathermap_config_settings () {
	global $tabs, $settings;
	$tabs["misc"] = "Misc";

	$temp = array(
		"weathermap_header" => array(
			"friendly_name" => "Network Weathermap",
			"method" => "spacer",
		),
		"weathermap_pagestyle" => array(
			"friendly_name" => "Page style",
			"description" => "How to display multiple maps.",
			"method" => "drop_array",
			"array" => array(0 => "Thumbnail Overview", 1 => "Full Images", 2 => "Show Only First")
		),
		"weathermap_thumbsize" => array(
			"friendly_name" => "Thumbnail Maximum Size",
			"description" => "The maximum width or height for thumbnails in thumbnail view, in pixels. Takes effect after the next poller run.",
			"method" => "textbox",
			"max_length" => 5,
		),
		"weathermap_cycle_refresh" => array(
			"friendly_name" => "Refresh Time",
			"description" => "How often to refresh the page in Cycle mode. Automatic makes all available maps fit into 5 minutes.",
			"method" => "drop_array",
			"array" => array(0 => "Automatic", 5 => "5 Seconds",
			15 => '15 Seconds',
			30 => '30 Seconds',
			60 => '1 Minute',
			120 => '2 Minutes',
			300 => '5 Minutes',
		)
	),
	"weathermap_output_format" => array(
		"friendly_name" => "Output Format",
		"description" => "What format do you prefer for the generated map images and thumbnails?",
		"method" => "drop_array",
		"array" => array('png' => "PNG (default)",
		'jpg' => "JPEG",
		'gif' => 'GIF'
	)
),
"weathermap_render_period" => array(
	"friendly_name" => "Map Rendering Interval",
	"description" => "How often do you want Weathermap to recalculate it's maps? You should not touch this unless you know what you are doing! It is mainly needed for people with non-standard polling setups.",
	"method" => "drop_array",
	"array" => array(-1 => "Never (manual updates)", 
		0 => "Every Poller Cycle (default)",
		2 => 'Every 2 Poller Cycles',
		3 => 'Every 3 Poller Cycles',
		4 => 'Every 4 Poller Cycles',
		5 => 'Every 5 Poller Cycles',
		10 => 'Every 10 Poller Cycles',
		12 => 'Every 12 Poller Cycles',
		24 => 'Every 24 Poller Cycles',
		36 => 'Every 36 Poller Cycles',
		48 => 'Every 48 Poller Cycles',
		72 => 'Every 72 Poller Cycles',
		288 => 'Every 288 Poller Cycles',
		),
	),

	"weathermap_quiet_logging" => array(
		"friendly_name" => "Quiet Logging",
		"description" => "By default, even in LOW level logging, Weathermap logs normal activity. This makes it REALLY log only errors in LOW mode.",
		"method" => "drop_array",
		"array" => array(0=>"Chatty (default)",1=>"Quiet")
		)
	);
	if (isset($settings["misc"]))
		$settings["misc"] = array_merge($settings["misc"], $temp);
	else
		$settings["misc"]=$temp;
}


function weathermap_setup_table () {
	global $config, $database_default;
	include_once($config["library_path"] . DIRECTORY_SEPARATOR . "database.php");

	$sql = "show tables";
	$result = db_fetch_assoc($sql) or die (mysql_error());

	$tables = array();
	$sql = array();

	foreach($result as $index => $arr) {
		foreach ($arr as $t) {
			$tables[] = $t;
		}
	}

	$sql[] = "update weathermap_maps set sortorder=id where sortorder is null;";

	if (!in_array('weathermap_maps', $tables)) {
		$sql[] = "CREATE TABLE weathermap_maps (
			id int(11) NOT NULL auto_increment,
			sortorder int(11) NOT NULL default 0,
			active set('on','off') NOT NULL default 'on',
			configfile text NOT NULL,
			imagefile text NOT NULL,
			htmlfile text NOT NULL,
			titlecache text NOT NULL,
			PRIMARY KEY  (id)
		) TYPE=MyISAM;";
	}
	else
	{
		$colsql = "show columns from weathermap_maps from " . $database_default;
		$result = mysql_query($colsql) or die (mysql_error());
		$found = false;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($row['Field'] == 'sortorder')
				$found = true;
		}
		if (!$found)
		{
			$sql[] = "alter table weathermap_maps add sortorder int(11) NOT NULL default 0 after id";
		}
	}

	if (!in_array('weathermap_auth', $tables)) {
		$sql[] = "CREATE TABLE weathermap_auth (
			userid mediumint(9) NOT NULL default '0',
			mapid int(11) NOT NULL default '0'
		) TYPE=MyISAM;";
	}
	
	if (!in_array('weathermap_data', $tables)) {
		$sql[] = "CREATE TABLE IF NOT EXISTS weathermap_data (id int(11) NOT NULL auto_increment,
			rrdfile varchar(255) NOT NULL,data_source_name varchar(19) NOT NULL,
			  last_time int(11) NOT NULL,last_value varchar(255) NOT NULL,
			last_calc varchar(255) NOT NULL, sequence int(11) NOT NULL, PRIMARY KEY  (id), KEY rrdfile (rrdfile),
			  KEY data_source_name (data_source_name) ) TYPE=MyISAM";
	}

	// create the settings entries, if necessary

	$pagestyle = read_config_option("weathermap_pagestyle");
	if($pagestyle == '' or $pagestyle < 0 or $pagestyle >2)
	{
		$sql[] = "replace into settings values('weathermap_pagestyle',0)";
	}

	$cycledelay = read_config_option("weathermap_cycle_refresh");  
	if($cycledelay == '' or intval($cycledelay < 0) )
	{
		$sql[] = "replace into settings values('weathermap_cycle_refresh',0)";
	}

	$renderperiod = read_config_option("weathermap_render_period");  
	if($renderperiod == '' or intval($renderperiod < -1) )
	{
		$sql[] = "replace into settings values('weathermap_render_period',0)";
	}
	
	$quietlogging = read_config_option("weathermap_quiet_logging");  
	if($quietlogging == '' or intval($quietlogging < -1) )
	{
		$sql[] = "replace into settings values('weathermap_quiet_logging',0)";
	}

	$rendercounter = read_config_option("weathermap_render_counter");  
	if($rendercounter == '' or intval($rendercounter < 0) )
	{
		$sql[] = "replace into settings values('weathermap_render_counter',0)";
	}

	$outputformat = read_config_option("weathermap_output_format");  
	if($outputformat == '' )
	{
		$sql[] = "replace into settings values('weathermap_output_format','png')";
	}

	$tsize = read_config_option("weathermap_thumbsize");
	if($tsize == '' or $tsize < 1)
	{
		$sql[] = "replace into settings values('weathermap_thumbsize',250)";
	}

	// patch up the sortorder for any maps that don't have one.
	$sql[] = "update weathermap_maps set sortorder=id where sortorder is null or sortorder=0;";

	if (!empty($sql)) {
		for ($a = 0; $a < count($sql); $a++) {
			$result = db_execute($sql[$a]);
		}
	}
}

function weathermap_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu;

	$user_auth_realms[42]='Plugin -> Weathermap: Configure/Manage';
	$user_auth_realms[43]='Plugin -> Weathermap: View';
	$user_auth_realm_filenames['weathermap-cacti-plugin.php'] = 43;
	$user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'] = 42;

	$menu["Management"]['plugins/weathermap/weathermap-cacti-plugin-mgmt.php'] = "Weathermaps";
}

function weathermap_show_tab () {
	global $config, $user_auth_realms, $user_auth_realm_filenames;
	$realm_id2 = 0;

	if (isset($user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')])) {
		$realm_id2 = $user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')];
	}

	if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"] . "' and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {

		print '<a href="' . $config['url_path'] . 'plugins/weathermap/weathermap-cacti-plugin.php"><img src="' . $config['url_path'] . 'plugins/weathermap/images/tab_weathermap';
		// if we're ON a weathermap page, print '_red'
		if(preg_match('/plugins\/weathermap\//',$_SERVER['REQUEST_URI'] ,$matches))
		{
			print "_red";
		}
		print '.png" alt="Weathermap" align="absmiddle" border="0"></a>';

	}

	weathermap_setup_table();
}

function weathermap_draw_navigation_text ($nav) {
	$nav["weathermap-cacti-plugin.php:"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
	$nav["weathermap-cacti-plugin.php:viewmap"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
	$nav["weathermap-cacti-plugin.php:viewmapcycle"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");

	$nav["weathermap-cacti-plugin-mgmt.php:"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	//   $nav["weathermap-cacti-plugin-mgmt.php:addmap_picker"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:viewconfig"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:addmap"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:editmap"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:editor"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

	//  "graphs.php:graph_edit" => array("title" => "(Edit)", "mapping" => "index.php:,graphs.php:", "url" => "", "level" => "2"),

	$nav["weathermap-cacti-plugin-mgmt.php:perms_edit"] = array("title" => "Edit Permissions", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
	$nav["weathermap-cacti-plugin-mgmt.php:addmap_picker"] = array("title" => "Add Map", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");


	// $nav["weathermap-cacti-plugin-mgmt.php:perms_edit"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:perms_add_user"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:perms_delete_user"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:delete_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:move_map_down"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:move_map_up"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:activate_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:deactivate_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:rebuildnow"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	$nav["weathermap-cacti-plugin-mgmt.php:rebuildnow2"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
	
	return $nav;
}

function weathermap_poller_output ($rrd_update_array) {
	global $config;
	// global $weathermap_debugging;

	// partially borrowed from Jimmy Conner's THold plugin.
	// (although I do things slightly differently - I go from filenames, and don't use the poller_interval)

	cacti_log("*****************************************************************\npoller_output starting\n",true,"WEATHERMAP");
	
	$requiredlist = db_fetch_assoc("select distinct weathermap_data.*, data_template_data.local_data_id, data_template_rrd.data_source_type_id from weathermap_data, data_template_data, data_template_rrd where weathermap_data.rrdfile=data_template_data.data_source_path and data_template_rrd.local_data_id=data_template_data.local_data_id");
	
	foreach ($requiredlist as $required)
	{
		$file = str_replace("<path_rra>", $config['base_path'].'/rra', $required['rrdfile']);
		$dsname = $required['data_source_name'];
		
		if( isset( $rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname} ) )
		{
			$value = $rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname};
			$time = key($rrd_update_array[$file]['times']);
			cacti_log("Got one! $file:$dsname -> $time $value\n",true,"WEATHERMAP");
			
			$period = $time - $required['last_time'];
			$lastval = $required['last_value'];
			
			switch($required['data_source_type_id'])
			{
				case 1: //GAUGE
					$newvalue = $value;
					break;
				
				case 2: //COUNTER
					if ($value >= $lastval) {
						// Everything is normal
						$newvalue = $value - $lastval;
					} else {
						// Possible overflow, see if its 32bit or 64bit
						if ($lastval > 4294967295) {
							$newvalue = (18446744073709551615 - $lastval) + $value;
						} else {
							$newvalue = (4294967295 - $lastval) + $value;
						}
					}
					$newvalue = $newvalue / $period;
					break;
				
				case 3: //DERIVE
					$newvalue = ($value-$lastval) / $period;
					break;
				
				case 4: //ABSOLUTE
					$newvalue = $value / $period;
					break;
				
				default: // do something somewhat sensible in case something odd happens
					$newvalue = $value;
					break;
			}
			db_execute("UPDATE weathermap_data SET last_time=$time, last_calc='$newvalue', last_value='$value',sequence=sequence+1  where id = " . $required['id']);
			cacti_log("Final value is $newvalue (was $lastval, period was $period)\n",true,"WEATHERMAP");
		}
	}

	cacti_log("poller_output done\n*****************************************************************\n",true,"WEATHERMAP");
	
	return $rrd_update_array;
}

function weathermap_poller_bottom () {
	global $config;
	global $weathermap_debugging, $WEATHERMAP_VERSION;

	include_once($config["library_path"] . DIRECTORY_SEPARATOR."database.php");
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."poller-common.php");

	weathermap_setup_table();

	$renderperiod = read_config_option("weathermap_render_period");  
	$rendercounter = read_config_option("weathermap_render_counter");  
	$quietlogging = read_config_option("weathermap_quiet_logging");  

	if($renderperiod<0)
	{
		// manual updates only
		if($quietlogging==0) cacti_log("Weathermap $WEATHERMAP_VERSION - no updates ever",true,"WEATHERMAP");
		return;
	}
	else
	{
		// if we're due, run the render updates
		if( ( $renderperiod == 0) || ( ($rendercounter % $renderperiod) == 0) )
		{
			weathermap_run_maps(dirname(__FILE__) );
		}
		else
		{
			if($quietlogging==0) cacti_log("Weathermap $WEATHERMAP_VERSION - no update in this cycle ($rendercounter)",true,"WEATHERMAP");
		}
		# cacti_log("Weathermap counter is $rendercounter. period is $renderperiod.", true, "WEATHERMAP");
		// increment the counter
		$newcount = ($rendercounter+1)%1000;
		db_execute("replace into settings values('weathermap_render_counter',".$newcount.")");
	}
}

// vim:ts=4:sw=4:
?>
