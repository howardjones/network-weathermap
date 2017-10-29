<?php

$guest_account = true;

chdir('../../');
include_once './include/auth.php';
include_once './include/config.php';
include_once $config['library_path'] . '/database.php';

include_once dirname(__FILE__) . '/lib/Weathermap/Integrations/Cacti/WeatherMapCacti10UserPlugin.php';

use Weathermap\Integrations\Cacti\WeatherMapCacti10UserPlugin;

$plugin = new WeatherMapCacti10UserPlugin($config, "png");

$plugin->main($_REQUEST);

// vim:ts=4:sw=4:
