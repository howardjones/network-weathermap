<?php

$guest_account = true;

chdir('../../');
include_once './include/auth.php';
include_once './include/config.php';
include_once $config['library_path'] . '/database.php';

include_once dirname(__FILE__) . '/lib/WeatherMapCacti88UserPlugin.php';

$plugin = new WeatherMapCacti88UserPlugin($config, $colors, "png");

$plugin->main($_REQUEST);


// vim:ts=4:sw=4:
