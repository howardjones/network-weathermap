<?php

//
// XXX - This should eventually come from a config file
//
$wm_picker_plugin_config = array (
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

class WMGraphPicker {
}

class WMDataPicker {
}

class CactiDataPicker extends WMDataPicker {
	var $config;
	var $db_link = null;
	function CactiDataPicker($cfg) {
		$this->config = $cfg;
	}
	function db_connect() {
		if (is_null ( $this->db_link )) {
			$this->db_link = mysql_connect ( $this->config ['db_hostname'], $this->config ['db_username'], $this->config ['db_password'] ) or die ( 'Could not connect: ' . mysql_error () );
			mysql_selectdb ( $this->config ['db_database'], $this->db_link ) or die ( 'Could not select database: ' . mysql_error () );
		}
	}
	function get_metadata() {
		return array (
				"name" => "Cacti Data Sources",
				"levels" => 3,
				"final_ds" => 1,
				"headings" => array (
						"Host",
						"Data Template",
						"Datasource",
						"DS Names" 
				) 
		);
	}
	function get_list($keys) {
		$this->db_connect ();
		
		$result_list = array ();
		
		// first level - pick a host
		if (! isset ( $keys ) || count ( $keys ) == 0) {
			$sql = "select id, description, hostname from host order by description,hostname";
			
			$result = mysql_query ( $sql );
			
			$result_list = array ();
			
			while ( $host = mysql_fetch_assoc ( $result ) ) {
				$result_list [] = array (
						$host ['id'],
						$host ['description'],
						$host ['hostname'] 
				);
			}
			mysql_free_result ( $result );
		}
		
		// second level - show data templates for that host
		if (isset ( $keys ) && count ( $keys ) == 1) {
			$result_list [] = array (
					"X",
					"Data Template Name",
					"description" 
			);
		}		// third level - show data sources for that host and template
		elseif (isset ( $keys ) && count ( $keys ) == 2) {
			$result_list [] = array (
					"X",
					"Data Source",
					"description" 
			);
		} else {
			$result_list [] = "CONFUSED";
		}
		
		return $result_list;
	}
}

$action = "";
$source = "";
if (isset ( $_REQUEST ['act'] )) {
	$action = $_REQUEST ['act'];
}

if (isset ( $_REQUEST ['source'] )) {
	$source = $_REQUEST ['source'];
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
		if (isset ( $_REQUEST ['keys'] ))
			$keys = $_REQUEST ['keys'];
		$result_list = $pick->get_list ( $keys );
		
		header ( "Content-type: text/json" );
		print json_encode ( $result_list );
		break;
	
	default :
		exit ();
}