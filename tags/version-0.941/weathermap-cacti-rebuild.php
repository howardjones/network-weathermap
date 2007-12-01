<?php
global $config;
global $weathermap_debugging, $WEATHERMAP_VERSION;

$cacti_base = "C:/Program Files/xampp/htdocs/cacti/";
$cacti_base = "/usr/local/share/cacti/";

include_once($cacti_base."/include/global.php");

include_once($config["library_path"] . DIRECTORY_SEPARATOR."database.php");
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."setup.php");
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."poller-common.php");

weathermap_setup_table();

weathermap_run_maps(dirname(__FILE__) );


// vim:ts=4:sw=4:
?>
