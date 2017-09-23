<?php

require_once 'lib/Map.php';

$map = new WeatherMap();

$reader = new ConfigReader($map);
$count = $reader->dumpKeywords();

print "\n\nThere were $count variations\n";
