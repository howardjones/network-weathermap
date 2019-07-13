<?php

$guest_account = true;

chdir('../../');
include_once './include/auth.php';
include_once './include/global.php';
include_once $config['library_path'] . '/database.php';

require_once dirname(__FILE__) . "/lib/all.php";


use Weathermap\Integrations\Cacti\WeatherMapCacti88UserPlugin;

$plugin = new WeatherMapCacti88UserPlugin($config, $colors, "png", realpath(dirname(__FILE__)));

$plugin->main($_REQUEST);


