<?php

chdir('../../');

include_once "./include/auth.php";
include_once "./include/config.php";
include_once $config["library_path"] . "/database.php";

require_once dirname(__FILE__) . "/lib/all.php";


use Weathermap\Integrations\Cacti\WeatherMapCacti88ManagementPlugin;

$wm_showOldUI = false;

$plugin = new WeatherMapCacti88ManagementPlugin($config, $colors, realpath(dirname(__FILE__)));

$plugin->main($_REQUEST);

// vim:ts=4:sw=4:
