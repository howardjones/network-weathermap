<?php

// Simple script to add some sample data into the database
// (before all the UI is working again)

// Needs to live in the Weathermap base directory

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
$manager->addMap("quick-test.conf");

$groupid = $manager->createGroup("Other Maps");
$mapid = $manager->addMap("weathermap.conf");

$manager->updateMap($mapid, array("group_id"=>$groupid));

$groupid = $manager->createGroup("Empty Group");

print_r($manager->getMaps());

