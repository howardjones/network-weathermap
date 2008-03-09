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

$mapfile = "configs/095-test.conf";

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


#$mynode = $map->nodes['node80111'];
#$ddnode = $map->inherit_fieldlist;
#$dnode = $map->defaultnode;

#print $mynode->usescale."\n";
#print $dnode->usescale."\n";
#print $ddnode->usescale."\n";

$map->LoopAllItems();

$map->WriteConfig("output.conf");

print "Wrote config\n";
//	print_r($map);


// vim:ts=4:sw=4:
?>
