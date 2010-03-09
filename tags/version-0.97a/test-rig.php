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


	// load up the config schema
	$valid_commands = array();

	$f = fopen("config-schema.tsv","r");
	while( ! feof($f))
	{
		$line = fgets($f);
		$parts = split("\t",$line);
		$context = array_shift($parts);
		$command = strtolower(array_shift($parts));
		
		$valid_commands[$context.".".$command][] = $parts;
	}
	fclose($f);

$test = $valid_commands["SCALE.scale"][0] ;
$values = array( 0,0,192,192,192, 20,20,20, 'pop' );

print_r($test);
print_r($values);

if(ConfigParamValidate( $test, $values))
{
	print "Validated.\n";
}
else
{
	print "Nope.\n";
}

exit();

# take an array as a schema (from valid_commands)
# and another array of values (from ReadConfigNNG)
# and see if they fit.
function ConfigParamValidate($schema, $values)
{
	$valid = false;
	
	print sizeof($schema)." items in schema\n";
	print sizeof($values)." items in values\n";
	
	
	while( sizeof($values) > 0 && strlen($values[0]) != 0 )
	{
		print sizeof($schema)." items in schema\n";
		print sizeof($values)." items in values\n";
		
		$s_type = array_shift($schema);
		$value = array_shift($values);
		
		print "CHECKING $s_type against $value\n";
	
		if(substr($s_type,0,1) != '{')
		{
			$valid = ( strtolower($value) == strtolower($schema) );
		}
		else
		{
			switch($s_type)
			{
				case '{signedint}':
					if(preg_match("/^[+-]?\d+$/", $value)) $valid = true;
					break;
				
				case '{int}':
					if(preg_match("/^\d+$/", $value)) $valid = true;
					break;
				
				case '{float}':
					if(preg_match("/^(\-?\d+\.?\d*)$/", $value)) $valid = true;
					break;
				
				case '{colourspec}':
					$value2 = array_shift($values);
					$value3 = array_shift($values);
					if( is_numeric($value) && is_numeric($value2) && is_numeric($value3) )
					{
						if ( preg_match("/^\d+$/", $value)
						    && preg_match("/^\d+$/", $value2)
						    && preg_match("/^\d+$/", $value3)
						    )
						{
							if( ($value >= 0) && ($value2 >= 0) && ($value3 >= 0)
								&& ($value < 256) && ($value2 < 256) && ($value3 < 256) )
							$valid = true;
						}
					}
					break;
							
				
				case '{string}':
					$valid = true;
					break;
				
				default:
					$valid = false;
					print "UNHANDLED TYPE: $s_type\n";
					break;
			}
		}
		print "Valid: ". ($valid ? "YES" : "NO") . "\n";
	}
	
	return $valid;
}

#$mapfile = "configs/097-test.conf";
$mapfile = "configs/simple.conf";

$map = new WeatherMap;

	//   $map->debugging = TRUE;
	$weathermap_debugging=TRUE;


	$map->ReadConfigNNG($mapfile);

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
