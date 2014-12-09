<?php

//
// XXX - This should eventually come from a config file
//
$config = array (
		"sources" => array (
				"cacti_rrddata" => array (
						"Cacti Data Sources (RRD File)",
						"CactiDataPicker",
						array (
								"db_database" => "cactitest",
								"db_password" => "jCp49SmYJ8C5298F",
								"db_username" => "browser",
								"db_hostname" => "localhost" 
						) 
				),
				"cacti_dsdata" => array (
						"Cacti Data Sources (DSStats)",
						"CactiDSDataPicker",
						array (
								"db_database" => "cactitest",
								"db_password" => "jCp49SmYJ8C5298F",
								"db_username" => "browser",
								"db_hostname" => "localhost" 
						) 
				),
				"cacti_host" => array (
						"Cacti Host Status",
						"CactiHostDataPicker",
						array (
								"db_database" => "cactitest",
								"db_password" => "jCp49SmYJ8C5298F",
								"db_username" => "browser",
								"db_hostname" => "localhost" 
						) 
				),
				"cacti_thold" => array (
						"Cacti THold Status",
						"CactiTHoldDataPicker",
						array (
								"db_database" => "cactitest",
								"db_password" => "jCp49SmYJ8C5298F",
								"db_username" => "browser",
								"db_hostname" => "localhost" 
						) 
				),
				"rrdfile:1" => array (
						"Cricket .rrd Files",
						"GenericRRDFileDataPicker" 
				),
				"rrdfile:2" => array (
						"MRTG .rrd Files",
						"GenericRRDFileDataPicker" 
				),
				"somethingelse:1" => array (
						"Random Data Source",
						"GenericRRDFileDataPicker" 
				) 
		) 
);


require "lib/WMGraphPicker.class.php";
require "lib/WMDataPicker.class.php";
require "lib/editor-pickers/WeatherMapDataPicker_cactirrd.class.php";

$action = "";
$source = "";
if (isset ( $_GET ['act'] )) {
	$action = $_GET ['act'];
}

if (isset ( $_GET ['source'] )) {
	$source = $_GET ['source'];
	$config = $wm_picker_plugin_config ["sources"] [$source];
	
	$pick = new $config [1] ( $config [2] );
}

switch ($action) {
	case "metameta" :
		
		$metameta = array (
				"sources" => array () 
		);
		
		foreach ( $wm_picker_plugin_config ["sources"] as $conf_name => $conf ) {
			$metameta ['sources'] [$conf_name] = array (
					$conf [0] 
			);
		}
		
		header ( "Content-type: text/json" );
		print json_encode ( $metameta );
		break;
	
	case "meta" :
		$meta = $pick->get_metadata ();
		header ( "Content-type: text/json" );
		print json_encode ( $meta );
		break;
	
	case "list" :
		$keys = array ();
		if (isset ( $_GET ['keys'] ))
			$keys = $_GET ['keys'];
		$result_list = $pick->get_list ( $keys );
		
		header ( "Content-type: text/json" );
		print json_encode ( $result_list );
		break;
	
	default :
		exit ();
}