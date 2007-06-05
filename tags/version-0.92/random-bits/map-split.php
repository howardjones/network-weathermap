<?php
	require_once 'Weathermap.class.php';
	
	// EDIT THESE!
	// Which file to read in (the big map)
	$input_mapfile = "configs/09-test.conf";
	// how big do you want your new maps to be?
	$desired_width = 640;
	$desired_height = 480;
			
	$map = new WeatherMap;	
	$map->ReadConfig($input_mapfile);
	
	print "Size of source is ".$map->width."x".$map->height."\n";

	$rows = intval($map->height/$desired_height)+1;
	$cols = intval($map->width/$desired_width)+1;
	$num = $rows * $cols;
	
	
	if($num == 1)
	{
		print "This map is already within your constraints.\n";
	}
	else
	{
		print "We'll need to make $num ($cols x $rows) smaller maps\n";
		for($row=0;$row < $rows; $row++)
		{
			for($col=0;$col<$cols; $col++)
			{
				print "=====================================\nMaking the submap $col,$row\n";
				$min_x = $col*$desired_width;
				$min_y = $row*$desired_height;
				$max_x = ($col+1)*$desired_width;
				$max_y = ($row+1)*$desired_height;
				print "We'll read the map, and throw out everything not inside ($min_x,$min_y)->($max_x,$max_y)\n";
				
				$map = new WeatherMap;	
				$map->ReadConfig($input_mapfile);
				
				foreach ($map->nodes as $node)
				{
					$target = $node->name;			
					if( ($node->x < $min_x) || ($node->x >= $max_x) ||
						($node->y < $min_y) || ($node->y >= $max_y) )
					{
						
						print "$target falls outside of this map. Deleting it and links that use it.\n";
						
						foreach ($map->links as $link)
						{
							if( ($target == $link->a->name) || ($target == $link->b->name) )
							{
								print "link $link->name uses it. Deleted.\n";
								unset($map->links[$link->name]);
							}
						}
						unset($map->nodes[$target]);
					}
					else
					{
						print "$target is OK, but will be moved for the new map from ".$node->x.",".$node->y." to ";
						$x = $node->x;
						$y = $node->y;
						
						$x = $node->x  - $min_x;
						$y = $node->y  - $min_y;
						$map->nodes[$target]->x = $x;
						$map->nodes[$target]->y = $y;
						print "$x,$y\n";
					}
				}
				$output_mapfile = $input_mapfile."-".$row."-".$col.".conf";
				$map->width = $desired_width;
				$map->height = $desired_height;
				$map->background="";
				$map->WriteConfig($output_mapfile);
			}
		}
		
	}

?>
