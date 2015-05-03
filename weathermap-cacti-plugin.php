<?php

/** This file is from Weathermap version 0.97d */

$guest_account = true;

chdir('../../');
require_once "./include/auth.php";

// include the weathermap class so that we can get the version
require_once dirname(__FILE__)."/lib/globals.php";
require_once dirname(__FILE__)."/lib/cacti-plugin-user.php";
require_once dirname(__FILE__)."/lib/cacti-plugin-common.php";
require_once dirname(__FILE__)."/lib/WMCactiAPI.class.php";

$action = "";
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$plugin = new WeatherMapCactiUserPlugin($config, $colors, WMCactiAPI::getConfigOption("weathermap_image_format", "png"));

$plugin->dispatchRequest($action, $_REQUEST, null);

// vim:ts=4:sw=4:
