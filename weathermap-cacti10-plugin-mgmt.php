<?php

chdir('../../');
include_once './include/auth.php';
include_once './include/config.php';
include_once $config['library_path'] . '/database.php';

use Weathermap\Integrations\Cacti\WeatherMapCacti10ManagementPlugin;

$plugin = new WeatherMapCacti10ManagementPlugin($config, realpath(dirname(__FILE__)));

$plugin->main($_REQUEST);
