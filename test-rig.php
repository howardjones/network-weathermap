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
//      include('spyc.php');


$mapfile = "configs/096-test.conf";

$map = new WeatherMap;

	//   $map->debugging = TRUE;
	$weathermap_debugging=TRUE;


	$map->ReadConfigNG($mapfile);

	exit();
	
	list($bottom, $top) = $map->FindScaleExtent("DEFAULT");
	
	print "SCALE goes from $bottom to $top\n";

	$r2 = $map->NewColourFromPercent(104,"DEFAULT","test2",FALSE);
	$r3 = $map->NewColourFromPercent(-5,"DEFAULT","test3",FALSE);
	$r4 = $map->NewColourFromPercent(5,"DEFAULT","test4",FALSE);
	$r5 = $map->NewColourFromPercent(-35,"DEFAULT","test5",FALSE);

	print "Did tests\n";

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



$map->WriteConfig("output.conf");

print "Wrote config\n";
//	print_r($map);

// $yaml = Spyc::YAMLDump($map);
// print $yaml;



// vim:ts=4:sw=4:
?>
