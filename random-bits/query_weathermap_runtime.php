#!/usr/bin/php
<?php


$SQL = "select * from settings where name like 'weathermap_%'";

include dirname(__FILE__).'/../../../include/config.php';

 $link = mysql_connect($database_hostname, $database_username,
        $database_password) or die('Could not connect: ' . mysql_error());
    mysql_selectdb($database_default,
        $link) or die('Could not select database: ' . mysql_error());

$nmaps = 0;
$warnings = 0;

$result = mysql_query($SQL) or die(mysql_error() );

while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$values[ $line['name'] ] = $line['value'];
}

$nmaps = $values['weathermap_last_map_count'];

$SQL = "select sum(warncount) as c from weathermap_maps where active='on'";
$result = mysql_query($SQL) or die(mysql_error());
$line = mysql_fetch_array($result, MYSQL_ASSOC);
$warnings = $line['c'];

$duration = $values['weathermap_last_finish_time'] - $values['weathermap_last_start_time'];

if ($duration < 0)
{
        $duration = "U";
}

print "duration:$duration nmaps:$nmaps warnings:$warnings ";
if (isset($values['weathermap_final_memory']) &&  isset($values['weathermap_initial_memory']) && $values['weathermap_loaded_memory'] && isset($values['weathermap_highwater_memory']) ) {
	print "initmem:".$values['weathermap_initial_memory']." ";
	print "loadedmem:".$values['weathermap_loaded_memory']." ";
	print "finalmem:".$values['weathermap_final_memory']." ";
	print "highmem:".$values['weathermap_highwater_memory']." ";
}
else
{
	print "initmem:U loadedmem:U finalmem:U highmem:U";
}

if (isset($values['weathermap_final_memory'])) {
	print "peak:".$values['weathermap_final_memory']." ";
}
else
{
	print "peak:U ";
}


