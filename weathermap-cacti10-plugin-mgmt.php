<?php

// Temporary to allow testing
header("Access-Control-Allow-Origin: *");

chdir('../../');
include_once './include/auth.php';
include_once './include/config.php';
include_once $config['library_path'] . '/database.php';

require_once dirname(__FILE__)."/lib/all.php";


use Weathermap\Integrations\Cacti\WeatherMapCacti10ManagementPlugin;

$wm_showOldUI = false;

$plugin = new WeatherMapCacti10ManagementPlugin($config, realpath(dirname(__FILE__)));

$plugin->main($_REQUEST);
