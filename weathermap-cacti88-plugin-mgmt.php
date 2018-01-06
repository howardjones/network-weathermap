<?php

chdir('../../');

include_once "./include/auth.php";
include_once "./include/config.php";
include_once $config["library_path"] . "/database.php";

use Weathermap\Integrations\Cacti\WeatherMapCacti88ManagementPlugin;


$plugin = new WeatherMapCacti88ManagementPlugin($config, $colors, realpath(dirname(__FILE__)));

$plugin->main($_REQUEST);

// vim:ts=4:sw=4:
