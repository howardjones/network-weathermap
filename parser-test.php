<?php

$dom[':: DEFAULTNODE ::'] = array(
	'name' => '',
	'label' => '',
	'grub' => 'fadsf',
	'position' => array(0,0),
	'template' => NULL
);
$dom[':: DEFAULTLINK ::'] = array(
	'template' => NULL,
	'name' => '',
);

$defnode = uniqid("N");
$deflink = uniqid("L");

$dom[ $defnode ] = array (
	'template' => ':: DEFAULTNODE ::',
	'target' => 'ploip'
);

$dom[ $deflink ] = array (
	'template' => ':: DEFAULTLINK ::'
);

$node1 = uniqid("N");
$node2 = uniqid("N");
$dom[ $node1 ] = array(
	'template' => $defnode,
	'label' => 'Node 1',
	'target' => 'poop'
);

$dom[ $node2 ] = array(
	'template' => $node1,
	'label' => 'Node 2'
);

print_r($dom);

$v = get_value($node2,"grub");
print "\n\n";
print "Value is $v\n";
print "\n\n";

function get_value($itemid, $name)
{
	global $dom; 
	
	if( isset($dom[$itemid]))
	{
		if(isset($dom[$itemid][$name]))
		{
			print "Found value\n";
			return ( $dom[$itemid][$name] );
		}
		else
		{
			print "Punting to parent\n";
			return(get_value($dom[$itemid]['template'], $name));
		}
	}
	else
	{
		print "Invalid itemid\n";
		return "";
	}
}


?>