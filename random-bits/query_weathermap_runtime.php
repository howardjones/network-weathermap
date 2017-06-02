#!/usr/bin/php
<?php

include dirname(__FILE__) . '/../../../include/config.php';
include dirname(__FILE__) . '/../lib/database.php';

$pdo = weathermap_get_pdo();

$statement = $pdo->prepare("SELECT * FROM settings WHERE name LIKE 'weathermap_%'");

$nmaps = 0;
$warnings = 0;

$statement->execute();
$lines = $statement->fetchAll(PDO::FETCH_CLASS);

foreach ($lines as $line) {
    $values[$line->name] = $line->value;
}

$nmaps = $values['weathermap_last_map_count'];

$statement = $pdo->prepare("SELECT sum(warncount) AS c FROM weathermap_maps WHERE active='on'");
$statement->execute();
$line = $statement->fetchAll(PDO::FETCH_CLASS);
$warnings = $line->c;

$duration = $values['weathermap_last_finish_time'] - $values['weathermap_last_start_time'];

if ($duration < 0) {
    $duration = "U";
}

print "duration:$duration nmaps:$nmaps warnings:$warnings ";
if (isset($values['weathermap_final_memory']) && isset($values['weathermap_initial_memory']) && $values['weathermap_loaded_memory'] && isset($values['weathermap_highwater_memory'])) {
    print "initmem:" . $values['weathermap_initial_memory'] . " ";
    print "loadedmem:" . $values['weathermap_loaded_memory'] . " ";
    print "finalmem:" . $values['weathermap_final_memory'] . " ";
    print "highmem:" . $values['weathermap_highwater_memory'] . " ";
} else {
    print "initmem:U loadedmem:U finalmem:U highmem:U";
}

if (isset($values['weathermap_final_memory'])) {
    print "peak:" . $values['weathermap_final_memory'] . " ";
} else {
    print "peak:U ";
}
