<?php

chdir('../../');

include_once "./include/auth.php";
include_once "./include/config.php";
include_once $config["library_path"] . "/database.php";

include_once dirname(__FILE__) . '/lib/WeatherMapCacti88ManagementPlugin.php';

$plugin = new WeatherMapCacti88ManagementPlugin($config, $colors);

$plugin->main($_REQUEST);

// vim:ts=4:sw=4:
