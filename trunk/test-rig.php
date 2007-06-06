<?php

	//
	// Mainly used to test editor functions.
	// - reads in a config file
	// - optional does "something" to it
	// - writes it back out
	//
	// A good test that WriteConfig and ReadConfig are really in sync
	//
include_once 'editor-config.php';
require_once 'Weathermap.class.php';

$mapfile = "random-bits\\suite-2.conf";

$map = new WeatherMap;

	//   $map->debugging = TRUE;

	$map->ReadConfig($mapfile);

	if(1==0)
	{
	$nodename = "Centre";
$newnodename = "dave";

$newnode = $map->nodes[$nodename];
$newnode->name = $newnodename;
$map->nodes[$newnodename] = $newnode;
unset($map->nodes[$nodename]);

foreach ($map->links as $link)
{
	if($link->a->name == $nodename)
	{
		$map->links[$link->name]->a = $newnode;
	}
	if($link->b->name == $nodename)
	{
		$map->links[$link->name]->b = $newnode;
	}
}

//   print_r($map->nodes['main']);

}

$map->WriteConfig("output.conf");

print "Wrote config\n";
//	print_r($map);


// vim:ts=4:sw=4:
?>
