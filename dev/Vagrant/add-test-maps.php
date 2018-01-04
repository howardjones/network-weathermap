<?php

// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

use Weathermap\Integrations\Cacti\CactiApplicationInterface;
use Weathermap\Integrations\MapManager;

// stop Cacti trying to do CSRF protection
$no_http_headers = true;

// these paths are correct within the VM
require_once __DIR__ . '/../../../../include/global.php';
require_once __DIR__ . '/../../lib/all.php';
require_once __DIR__ . '/../../lib/Weathermap/Integrations/Cacti/database.php';


$configDirectory = realpath('./configs');
print("dir is $configDirectory\n");

$app = new CactiApplicationInterface(weathermap_get_pdo());
$manager = new MapManager(weathermap_get_pdo(), $configDirectory, $app);

print_r($manager->getMaps());

$manager->addMap("simple.conf");
$manager->addMap("weathermap.conf");

print_r($manager->getMaps());

