<?php

require_once 'lib/Weathermap.class.php';

$map = new WeatherMap();

$reader = new WeatherMapConfigReader($map);
$count = $reader->dumpKeywords();

print "\n\nThere were $count variations\n";
