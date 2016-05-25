<?php
// Copyright Howard Jones, 2005 howie@thingy.com
// http://wotsit.thingy.com/haj/cacti/
// Released under the GNU Public License

// A simple port of the guts of Apache's mod_imap
// - if you have an image control in a form, it's not really defined what happens to USEMAP
//   attributes. They are allowed in HTML 4.0 and XHTML, but some testing shows that they're
//   basically ignored. So you need to use server-side imagemaps if you want to have a form
//   where you are choosing a verb from (for example) a <SELECT> and also specifying part of
//   an image with an IMAGE control.
//
//
class HTML_ImageMap_Area
{
	var $href;
	var $name;
	var $id;
	var $alt;
	var $z;
	var $extrahtml;

	function common_html()
	{
		$h = "";
		if($this->name != "")
		{
			// $h .= " alt=\"".$this->name."\" ";
			$h .= "id=\"".$this->name."\" ";
		}
		if($this->href != "")
		{
			$h .= "href=\"".$this->href."\" ";
		}
		else { $h .= "nohref "; }
		if($this->extrahtml != "")
		{
			$h .= $this->extrahtml." ";
		}
		return $h;
	}

}

class HTML_ImageMap_Area_Polygon extends HTML_ImageMap_Area
{
	var $points = array();
	var $minx,$maxx,$miny,$maxy; // bounding box
	var $npoints;

	function asHTML()
	{
		foreach ($this->points as $point)
		{
			$flatpoints[] = $point[0];
			$flatpoints[] = $point[1];
		}
		$coordstring = join(",",$flatpoints);

		return '<area '.$this->common_html().'shape="poly" coords="'.$coordstring.'" />';
	}

	function asJSON()
	{
		$json = "{ \"shape\":'poly', \"npoints\":".$this->npoints.", \"name\":'".$this->name."',";

		$xlist = '';
		$ylist = '';
		foreach ($this->points as $point)
		{
			$xlist .= $point[0].",";
			$ylist .= $point[1].",";
		}
		$xlist = rtrim($xlist,", ");
		$ylist = rtrim($ylist,", ");
		$json .= " \"x\": [ $xlist ], \"y\":[ $ylist ], \"minx\": ".$this->minx.", \"miny\": ".$this->miny.", \"maxx\":".$this->maxx.", \"maxy\":".$this->maxy."}";

		return($json);
	}

	function hitTest($x,$y)
	{
		$c = 0;
		// do the easy bounding-box test first.
		if( ($x < $this->minx) || ($x>$this->maxx) || ($y<$this->miny) || ($y>$this->maxy))
		{
			return false;
		}

		// Algotithm from from
		// http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html#The%20C%20Code
		for ($i = 0, $j = $this->npoints-1; $i < $this->npoints; $j = $i++)
		{
			// print "Checking: $i, $j\n";
			$x1 = $this->points[$i][0];
			$y1 = $this->points[$i][1];
			$x2 = $this->points[$j][0];
			$y2 = $this->points[$j][1];

			//  print "($x,$y) vs ($x1,$y1)-($x2,$y2)\n";

			if (((($y1<=$y) && ($y<$y2)) || (($y2<=$y) && ($y<$y1))) &&
				($x < ($x2 - $x1) * ($y - $y1) / ($y2 - $y1) + $x1))
			{
				$c = !$c;
			}
		}

		return ($c);
	}

	function HTML_ImageMap_Area_Polygon ( $name="", $href="",$coords)
	{
		$c = $coords[0];

		$this->name = $name;
		$this->href= $href;
		$this->npoints = count($c)/2;

		if( intval($this->npoints) != ($this->npoints))
		{
			die("Odd number of points!");
		}

		for ($i=0; $i<count($c); $i+=2)
		{
			$x = round($c[$i]);
			$y = round($c[$i+1]);
			$point = array($x,$y);
			$xlist[] = $x; // these two are used to get the bounding box in a moment
			$ylist[] = $y;
			$this->points[] = $point;
		}

		$this->minx = min($xlist);
		$this->maxx = max($xlist);
		$this->miny = min($ylist);
		$this->maxy = max($ylist);

		//        print $this->asHTML()."\n";
	}

}

class HTML_ImageMap_Area_Rectangle extends HTML_ImageMap_Area
{
	var $x1,$x2,$y1,$y2;

	function HTML_ImageMap_Area_Rectangle ( $name="", $href="",$coords)
	{

		$c = $coords[0];

		$x1 = round($c[0]);
		$y1 = round($c[1]);
		$x2 = round($c[2]);
		$y2 = round($c[3]);

		// sort the points, so that the first is the top-left
		if($x1>$x2)
		{
			$this->x1=$x2;
			$this->x2=$x1;
		}
		else
		{
			$this->x1=$x1;
			$this->x2=$x2;
		}

		if($y1>$y2)
		{
			$this->y1=$y2;
			$this->y2=$y1;
		}
		else
		{
			$this->y1=$y1;
			$this->y2=$y2;
		}

		$this->name = $name;
		$this->href = $href;
	}

	function hitTest($x,$y)
	{
		return  ( ($x > $this->x1) && ($x < $this->x2) && ($y > $this->y1) && ($y < $this->y2) );
	}

	function asHTML()
	{
		$coordstring = join(",",array($this->x1,$this->y1,$this->x2,$this->y2));
		return '<area '.$this->common_html().'shape="rect" coords="'.$coordstring.'" />';

	}

	function asJSON()
	{
		$json = "{ \"shape\":'rect', ";

		$json .= " \"x1\":".$this->x1.", \"y1\":".$this->y1.", \"x2\":".$this->x2.", \"y2\":".$this->y2.", \"name\":'".$this->name."'}";

		return($json);
	}

}

class HTML_ImageMap_Area_Circle extends HTML_ImageMap_Area
{
	var $centx,$centy, $edgex, $edgey;

	function asHTML()
	{
		$coordstring = join(",",array($this->centx,$this->centy,$this->edgex,$this->edgey) );
		return '<area '.$this->common_html().'shape="circle" coords="'.$coordstring.'" />';
	}

	function hitTest($x,$y)
	{
		$radius1 = ($this->edgey - $this->centy) * ($this->edgey - $this->centy)
			+ ($this->edgex - $this->centx) * ($this->edgex - $this->centx);

		$radius2 = ($this->centy - $y) * ($this->centy - $y)
			+ ($this->centx - $x) * ($this->centx - $x);

		return ($radius2 <= $radius1);
	}

	function HTML_ImageMap_Area_Circle($name="", $href="",$coords)
	{
		$c = $coords[0];

		$this->name = $name;
		$this->href = $href;
		$this->centx = round($c[0]);
		$this->centy = round($c[1]);
		$this->edgex = round($c[2]);
		$this->edgey = round($c[3]);
	}
}

class HTML_ImageMap
{
	var $shapes;
	var $nshapes;
	var $name;

	function HTML_ImageMap($name="")
	{
		$this->Reset();
		$this->name = $name;
	}

	function Reset()
	{
		$this->shapes = array();
		$this->nshapes = 0;
		$this->name = "";
	}

	// add an element to the map - takes an array with the info, in a similar way to HTML_QuickForm
	function addArea($element)
	{
		if (is_object($element) && is_subclass_of($element, 'html_imagemap_area')) {
			$elementObject = &$element;
		} else {
			$args = func_get_args();
			$className = "HTML_ImageMap_Area_".$element;
			$elementObject = new $className($args[1],$args[2],array_slice($args, 3));
		}

		$this->shapes[] =& $elementObject;
		$this->nshapes++;
		//      print $this->nshapes." shapes\n";
	}

	// do a hit-test based on the current map
	// - can be limited to only match elements whose names match the filter
	//   (e.g. pick a building, in a campus map)
	function hitTest($x,$y,$namefilter="")
	{
		$preg = '/'.$namefilter.'/';
		foreach ($this->shapes as $shape)
		{
			if($shape->hitTest($x,$y))
			{
				if( ($namefilter == "") || ( preg_match($preg,$shape->name) ) )
				{
					return $shape->name;
				}
			}
		}
		return false;
	}

	// update a property on all elements in the map that match a name
	// (use it for retro-actively adding in link information to a pre-built geometry before generating HTML)
	// returns the number of elements that were matched/changed
	function setProp($which, $what, $where)
	{
		$count = 0;
		for($i=0; $i<count($this->shapes); $i++)
		{
			// this USED to be a substring match, but that broke some things
			// and wasn't actually used as one anywhere.
			if( ($where == "") || ( $this->shapes[$i]->name==$where) )
			{
				switch($which)
				{
				case 'href':
					$this->shapes[$i]->href= $what;
					break;
				case 'extrahtml':
					$this->shapes[$i]->extrahtml= $what;
					#print "IMAGEMAP: Found $where and adding $which\n";
					break;
				}
				$count++;
			}
		}
		return $count;
	}

        // update a property on all elements in the map that match a name as a substring
        // (use it for retro-actively adding in link information to a pre-built geometry before generating HTML)
        // returns the number of elements that were matched/changed
        function setPropSub($which, $what, $where)
        {

                $count = 0;
                for($i=0; $i<count($this->shapes); $i++)
                {
                        if( ($where == "") || ( strstr($this->shapes[$i]->name,$where)!=FALSE ) )
                        {
                                switch($which)
                                {
                                case 'href':
                                        $this->shapes[$i]->href= $what;
                                        break;
                                case 'extrahtml':
                                        $this->shapes[$i]->extrahtml= $what;
                                        break;
                                }
                                $count++;
                        }
                }
                return $count;
        }

	// Return the imagemap as an HTML client-side imagemap for inclusion in a page
	function asHTML()
	{
		$html = '<map';
		if($this->name != "")
		{
			$html .= ' name="'.$this->name.'"';
		}
		$html .=">\n";
		foreach ($this->shapes as $shape)
		{
			$html .= $shape->asHTML()."\n";
			$html .= "\n";
		}
		$html .= "</map>\n";

		return $html;
	}

	function subJSON($namefilter="",$reverseorder=false)
	{
		$json = '';

		$preg = '/'.$namefilter.'/';
		foreach ($this->shapes as $shape)
		{
			if( ($namefilter == "") || ( preg_match($preg,$shape->name) ))
			{
				if($reverseorder)
				{
					$json = $shape->asJSON().",\n".$json;
				}
				else
				{
					$json .= $shape->asJSON().",\n";
				}
			}
		}
		$json = rtrim($json,"\n, ");
		$json .= "\n";

		return $json;
	}

	// return HTML for a subset of the map, specified by the filter string
	// (suppose you want some partof your UI to have precedence over another part
	//  - the imagemap is checked from top-to-bottom in the HTML)
	// - skipnolinks -> in normal HTML output, we don't need areas for things with no href
	function subHTML($namefilter="",$reverseorder=false, $skipnolinks=false)
	{
		$html = "";
		$preg = '/'.$namefilter.'/';
		
		foreach ($this->shapes as $shape)
		{
			# if( ($namefilter == "") || ( preg_match($preg,$shape->name) ))
			if( ($namefilter == "") || ( strstr($shape->name, $namefilter) !== FALSE ))
			{
				if(!$skipnolinks || $shape->href != "" || $shape->extrahtml != "" )
				{
					if($reverseorder)
					{
						$html = $shape->asHTML()."\n".$html;
					}
					else
					{
						$html .= $shape->asHTML()."\n";
					}
				}

			}
		}
		return $html;
	}

}
// vim:ts=4:sw=4:
