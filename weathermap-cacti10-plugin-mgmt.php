<?php

chdir('../../');
include_once './include/auth.php';
include_once './include/config.php';
include_once $config['library_path'] . '/database.php';

include_once dirname(__FILE__) . '/lib/WeatherMapCacti10ManagementPlugin.php';

$plugin = new WeatherMapCacti10ManagementPlugin($config);

$plugin->main($_REQUEST);
