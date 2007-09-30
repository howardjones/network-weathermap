<?php
// PHP Weathermap 0.93
// Copyright Howard Jones, 2005-2007 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

$WEATHERMAP_VERSION="0.93";
$weathermap_debugging=FALSE;

// Turn on ALL error reporting for now.
error_reporting (E_ALL);

// parameterise the in/out stuff a bit
define("IN",0);
define("OUT",1);
define("WMCHANNELS",2);

// Utility functions
// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
function module_checks()
{
	if (!extension_loaded('gd'))
	{
		warn ("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
		warn ("\nrun check.php to check PHP requirements.\n\n");

		return (FALSE);
	}

	if (!function_exists('imagecreatefrompng'))
	{
		warn ("Your GD php module doesn't support PNG format. [WMWARN21]\n");
		warn ("\nrun check.php to check PHP requirements.\n\n");
		return (FALSE);
	}

	if (!function_exists('imagecreatetruecolor'))
	{
		warn ("Your GD php module doesn't support truecolor. [WMWARN22]\n");
		warn ("\nrun check.php to check PHP requirements.\n\n");
		return (FALSE);
	}

	if (!function_exists('imagecopyresampled'))
	{
		warn ("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
	}
	return (TRUE);
}

function debug($string)
{
	global $weathermap_debugging;

	if ($weathermap_debugging)
	{
		// use Cacti's debug log, if we are running from the poller
		if (function_exists('debug_log_insert') && (!function_exists('show_editor_startpage')))
		{ cacti_log(rtrim($string), true, "WEATHERMAP"); }
		else
		{
			$stderr=fopen('php://stderr', 'w');
			fwrite($stderr, $string);
			fclose ($stderr);
		}
	}
}

function warn($string)
{
	// use Cacti's debug log, if we are running from the poller
	if (function_exists('cacti_log') && (!function_exists('show_editor_startpage')))
	{ cacti_log(rtrim($string), true, "WEATHERMAP"); }
	else
	{
		$stderr=fopen('php://stderr', 'w');
		fwrite($stderr, $string);
		fclose ($stderr);
	}
}

function js_escape($str, $wrap=TRUE)
{
	$str=str_replace('\\', '\\\\', $str);
	$str=str_replace("'", "\\'", $str);

	if($wrap) $str="'" . $str . "'";

	return ($str);
}

function mysprintf($format,$value,$kilo=1000)
{
	$output = "";

	debug("mysprintf: $format $value\n");
	if(preg_match("/%(\d*\.?\d*)k/",$format,$matches))
	{
		$spec = $matches[1];
		$places = 2;
		if($spec !='')
		{
			preg_match("/(\d*)\.?(\d*)/",$spec,$matches);
			if($matches[2] != '') $places=$matches[2];
			// we don't really need the justification (pre-.) part...
		}	
		debug("KMGT formatting $value with $spec.\n");
		$result = nice_scalar($value, $kilo, $places);
		$output = preg_replace("/%".$spec."k/",$format,$result);
	}
	else
	{
		debug("Falling through to standard sprintf\n");
		$output = sprintf($format,$value);
	}
	return $output;
}

// wrapper around imagecolorallocate to try and re-use palette slots where possible
function myimagecolorallocate($image, $red, $green, $blue)
{
	// it's possible that we're being called early - just return straight away, in that case
	if(!isset($image)) return(-1);
	
	$existing=imagecolorexact($image, $red, $green, $blue);

	if ($existing > -1)
		return $existing;

	return (imagecolorallocate($image, $red, $green, $blue));
}

function render_colour($col)
{
	if (($col[0] < 0) && ($col[1] < 0) && ($col[1] < 0)) { return 'none'; }
	else { return sprintf("%d %d %d", $col[0], $col[1], $col[2]); }
}

// take the same set of points that imagepolygon does, but don't close the shape
function imagepolyline($image, $points, $npoints, $color)
{
	for ($i=0; $i < ($npoints - 1);
	$i++) { imageline($image, $points[$i * 2], $points[$i * 2 + 1], $points[$i * 2 + 2], $points[$i * 2 + 3],
		$color); }
}

function imagecreatefromfile($filename)
{
	$bgimage=NULL;
	$formats = imagetypes();
	if (is_readable($filename))
	{
		list($width, $height, $type, $attr) = getimagesize($filename);
		switch($type)
		{
		case IMAGETYPE_GIF:
			if(imagetypes() & IMG_GIF)
			{
				$bgimage=imagecreatefromgif($filename);
			}
			else
			{
				warn("Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n");    
			}
			break;

		case IMAGETYPE_JPEG:
			if(imagetypes() & IMG_JPEG)
			{
				$bgimage=imagecreatefromjpeg($filename);
			}
			else
			{
				warn("Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n");    
			}
			break;

		case IMAGETYPE_PNG:
			if(imagetypes() & IMG_PNG)
			{
				$bgimage=imagecreatefrompng($filename);
			}
			else
			{
				warn("Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n");    
			}
			break;

		default:
			warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
			break;
		}
	}
	else
	{
		warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");    
	}
	return $bgimage;
}

// rotate a list of points around cx,cy by an angle in radians, IN PLACE
function RotateAboutPoint(&$points, $cx,$cy, $angle=0)
{
	$npoints = count($points)/2;
	
	for($i=0;$i<$npoints;$i++)
	{
		$ox = $points[$i*2] - $cx;
		$oy = $points[$i*2+1] - $cy;
		$rx = $ox * cos($angle) - $oy*sin($angle);
		$ry = $oy * cos($angle) + $ox*sin($angle);
		
		$points[$i*2] = $rx + $cx;
		$points[$i*2+1] = $ry + $cy;
	}
}

// calculate the points for a span of the curve. We pass in the distance so far, and the array index, so that
// the chunk of array generated by this function can be array_merged with existing points from before.
// Considering how many array functions there are, PHP has horrible list support
// Each point is a 3-tuple - x,y,distance - which is used later to figure out where the 25%, 50% marks are on the curve
function calculate_catmull_rom_span($startn, $startdistance, $numsteps, $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
{
	$Ap_x=-$x0 + 3 * $x1 - 3 * $x2 + $x3;
	$Bp_x=2 * $x0 - 5 * $x1 + 4 * $x2 - $x3;
	$Cp_x=-$x0 + $x2;
	$Dp_x=2 * $x1;

	$Ap_y=-$y0 + 3 * $y1 - 3 * $y2 + $y3;
	$Bp_y=2 * $y0 - 5 * $y1 + 4 * $y2 - $y3;
	$Cp_y=-$y0 + $y2;
	$Dp_y=2 * $y1;

	$d=2;
	$n=$startn;
	$distance=$startdistance;

	$lx=$x0;
	$ly=$y0;
		
	$allpoints[]=array
		(
			$x0,
			$y0,
			$distance
		);

	for ($i=0; $i <= $numsteps; $i++)
	{
		$t=$i / $numsteps;
		$t2=$t * $t;
		$t3=$t2 * $t;
		$x=(($Ap_x * $t3) + ($Bp_x * $t2) + ($Cp_x * $t) + $Dp_x) / $d;
		$y=(($Ap_y * $t3) + ($Bp_y * $t2) + ($Cp_y * $t) + $Dp_y) / $d;

		if ($i > 0)
		{
			$step=sqrt((($x - $lx) * ($x - $lx)) + (($y - $ly) * ($y - $ly)));
			$distance=$distance + $step;
			$allpoints[$n]=array
				(
					$x,
					$y,
					$distance
				);

			$n++;
		}

		$lx=$x;
		$ly=$y;
	}

	return array($allpoints, $distance, $n);
}

function find_distance_coords(&$pointarray,$distance)
{
	// We find the nearest lower point for each distance,
	// then linearly interpolate to get a more accurate point
	// this saves having quite so many points-per-curve
	
	$index=find_distance($pointarray, $distance);

	$ratio=($distance - $pointarray[$index][2]) / ($pointarray[$index + 1][2] - $pointarray[$index][2]);
	$x = $pointarray[$index][0] + $ratio * ($pointarray[$index + 1][0] - $pointarray[$index][0]);
	$y = $pointarray[$index][1] + $ratio * ($pointarray[$index + 1][1] - $pointarray[$index][1]);
	
	return(array($x,$y,$index));
}

function find_distance_coords_angle(&$pointarray,$distance)
{
	// This is the point we need
	list($x,$y,$index) = find_distance_coords($pointarray,$distance);
	
	// now to find one either side of it, to get a line to find the angle of
	$left = $index;
	$right = $left+1;
	$max = count($pointarray)-1;
	// if we're right up against the last point, then step backwards one
	if($right>=$max)
	{
		$left--;
		$right--;
	}
	# if($left<=0) { $left = 0; }

	$x1 = $pointarray[$left][0];
	$y1 = $pointarray[$left][1];
	
	$x2 = $pointarray[$right][0];
	$y2 = $pointarray[$right][1];

	$dx = $x2 - $x1;
	$dy = $y2 - $y1;
		
	$angle = rad2deg(atan2(-$dy,$dx));
	
	return(array($x,$y,$index,$angle));
}

// return the index of the point either at (unlikely) or just before the target distance
// we will linearly interpolate afterwards to get a true point - pointarray is an array of 3-tuples produced by the function above
function find_distance(&$pointarray, $distance)
{
	$left=0;
	$right=count($pointarray) - 1;

	if ($left == $right)
		return ($left);

	// if the distance is zero, there's no need to search (and it doesn't work anyway)
	 if($distance==0) return($left);

	// if it's a point past the end of the line, then just return the end of the line
	// Weathermap should *never* ask for this, anyway
	if ($pointarray[$right][2] < $distance) { return ($right); }

	// if somehow we have a 0-length curve, then don't try and search, just give up
	// in a somewhat predictable manner
	if ($pointarray[$left][2] == $pointarray[$right][2]) { return ($left); }

	while ($left <= $right)
	{
		$mid=floor(($left + $right) / 2);

		if (($pointarray[$mid][2] < $distance) && ($pointarray[$mid + 1][2] >= $distance)) { return $mid; }

		if ($distance <= $pointarray[$mid][2]) { $right=$mid - 1; }
		else { $left=$mid + 1; }
	}

	print "FELL THROUGH\n";
	die ("Howie's crappy binary search is wrong after all.\n");
}

// Give a list of key points, calculate a curve through them
// return value is an array of triples (x,y,distance)
function calc_curve(&$in_xarray, &$in_yarray,$pointsperspan = 12)
{
	// search through the point list, for consecutive duplicate points
	// (most common case will be a straight link with both NODEs at the same place, I think)
	// strip those out, because they'll break the binary search/centre-point stuff

	$last_x=NULL;
	$last_y=NULL;

	for ($i=0; $i < count($in_xarray); $i++)
	{
		if (($in_xarray[$i] == $last_x) && ($in_yarray[$i] == $last_y)) { debug
			("Dumping useless duplicate point on curve\n"); }
		else
		{
			$xarray[]=$in_xarray[$i];
			$yarray[]=$in_yarray[$i];
		}

		$last_x=$in_xarray[$i];
		$last_y=$in_yarray[$i];
	}

	// only proceed if we still have at least two points!
	if(count($xarray) <= 1)
	{
		warn ("Arrow not drawn, as it's 1-dimensional.\n");
		return (array(NULL, NULL, NULL, NULL));
	}

	// duplicate the first and last points, so that all points are drawn
	// (C-R normally would draw from x[1] to x[n-1]
	array_unshift($xarray, $xarray[0]);
	array_unshift($yarray, $yarray[0]);

	$x=array_pop($xarray);
	$y=array_pop($yarray);
	array_push($xarray, $x);
	array_push($xarray, $x);
	array_push($yarray, $y);
	array_push($yarray, $y);

	$npoints=count($xarray);

	$curvepoints=array
		(
			);

	// add in the very first point manually (the calc function skips this one to avoid duplicates, which mess up the distance stuff)
	$curvepoints[]=array
		(
			$xarray[0],
			$yarray[0],
			0
		);

	$np=0;
	$distance=0;

	for ($i=0; $i < ($npoints - 3); $i++)
	{
		list($newpoints,
			$distance,
			$np)=calculate_catmull_rom_span($np,     $distance,  $pointsperspan,  $xarray[$i],
			$yarray[$i],     $xarray[$i + 1], $yarray[$i + 1], $xarray[$i + 2],
			$yarray[$i + 2], $xarray[$i + 3], $yarray[$i + 3]);
		$curvepoints=$curvepoints + $newpoints;
	}

	return ($curvepoints);
}

function calc_arrowsize($width,&$map,$linkname)
{
	$arrowlengthfactor=4;
	$arrowwidthfactor=2;

	if ($map->links[$linkname]->arrowstyle == 'compact')
	{
		$arrowlengthfactor=1;
		$arrowwidthfactor=1;
	}

	if (preg_match('/(\d+) (\d+)/', $map->links[$linkname]->arrowstyle, $matches))
	{
		$arrowlengthfactor=$matches[1];
		$arrowwidthfactor=$matches[2];
	}

	$arrowsize = $width * $arrowlengthfactor;
	$arrowwidth = $width * $arrowwidthfactor;
	
	return( array($arrowsize,$arrowwidth) );
}

// top-level function that takes a two lists to define some points, and draws a weathermap link
// - this takes care of all the extras, like arrowheads, and where to put the bandwidth labels
//    curvepoints is an array of the points the curve passes through
//    width is the link width (the actual width is twice this)
//    outlinecolour is a GD colour reference
//    fillcolours is an array of two more colour references, one for the out, and one for the in spans
function draw_curve($image, &$curvepoints, $width, $outlinecolour, $comment_colour, $fillcolours, $linkname, &$map,
	$q2_percent=50)
{
	// now we have a 'spine' - all the central points for this curve.
	// time to flesh it out to the right width, and figure out where to draw arrows and bandwidth boxes...
	$unidirectional = FALSE;
	
	// get the full length of the curve from the last point
	$totaldistance = $curvepoints[count($curvepoints)-1][2];
	// find where the in and out arrows will join (normally halfway point)
	$halfway = $totaldistance * ($q2_percent/100);
	
	$dirs = array(OUT,IN);

	// for a unidirectional map, we just ignore the second half (direction = -1)
	if($unidirectional)
	{
		$halfway = $totaldistance;
		$dirs = array(OUT);
	}
	
	// loop increment, start point, width, labelpos, fillcolour, outlinecolour, commentpos
	$arrowsettings[OUT] = array(+1, 0, $width, 0, $fillcolours[OUT], $outlinecolour, 5);
	$arrowsettings[IN] = array(-1, count($curvepoints) - 1, $width, 0, $fillcolours[IN], $outlinecolour, 95);

	// we calculate the arrow size up here, so that we can decide on the
	// minimum length for a link. The arrowheads are the limiting factor.
	list($arrowsize,$arrowwidth) = calc_arrowsize($width,$map,$linkname);
	
	// the 2.7 here is empirical. It ought to be 2 in theory.
	// in practice, a link this short is useless anyway, especially with bwlabels.
	$minimumlength = 2.7*$arrowsize;

	# warn("$linkname: Total: $totaldistance $arrowsize $arrowwidth $minimumlength\n");
	if($totaldistance <= $minimumlength)
	{
		warn("Skipping drawing very short link ($linkname). Impossible to draw! Try changing WIDTH or ARROWSTYLE? [WMWARN01]\n");
		return;
	}

	
	list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);

	// loop over direction here
	// direction is 1.0 for the first half (forwards through the pointlist), and -1.0 for the second half (backwards from the end)
	//  - used as a multiplier on anything that looks forwards or backwards through the list

	foreach ($dirs as $dir)
	{
		$direction = $arrowsettings[$dir][0];
		// this is the last index before the arrowhead starts
		list($pre_mid_x,$pre_mid_y,$pre_midindex) = find_distance_coords($curvepoints,$halfway - $direction * $arrowsize);
		
		$there_points=array();
		$back_points=array();
		$arrowpoints=array();

		# if ($direction < 0) { $start=count($curvepoints) - 1; }
		# else { $start=0; }
		$start = $arrowsettings[$dir][1];

		for ($i=$start; $i != $pre_midindex; $i+=$direction)
		{
			// for each point on the spine, produce two points normal to it's direction,
			// each is $width away from the spine, but we build up the two lists in the opposite order,
			// so that when they are joined together, we get one continuous line

			$dx=$curvepoints[$i + $direction][0] - $curvepoints[$i][0];
			$dy=$curvepoints[$i + $direction][1] - $curvepoints[$i][1];
			$l=sqrt(($dx * $dx) + ($dy * $dy));
			$nx=$dy / $l;
			$ny=-$dx / $l;

			$there_points[]=$curvepoints[$i][0] + $direction * $width * $nx;
			$there_points[]=$curvepoints[$i][1] + $direction * $width * $ny;

			$back_points[]=$curvepoints[$i][0] - $direction * $width * $nx;
			$back_points[]=$curvepoints[$i][1] - $direction * $width * $ny;
		}

		// all the normal line is done, now lets add an arrowhead on

		$adx=($halfway_x - $pre_mid_x);
		$ady=($halfway_y - $pre_mid_y);
		$l=sqrt(($adx * $adx) + ($ady * $ady));

		$anx=$ady / $l;
		$any=-$adx / $l;

		$there_points[]=$pre_mid_x + $direction * $width * $anx;
		$there_points[]=$pre_mid_y + $direction * $width * $any;

		$there_points[]=$pre_mid_x + $direction * $arrowwidth * $anx;
		$there_points[]=$pre_mid_y + $direction * $arrowwidth * $any;

		$there_points[]=$halfway_x;
		$there_points[]=$halfway_y;

		$there_points[]=$pre_mid_x - $direction * $arrowwidth * $anx;
		$there_points[]=$pre_mid_y - $direction * $arrowwidth * $any;

		$there_points[]=$pre_mid_x - $direction * $width * $anx;
		$there_points[]=$pre_mid_y - $direction * $width * $any;

		// all points done, now combine the lists, and produce the final result.

		$y=array_pop($back_points);
		$x=array_pop($back_points);
		do
		{
			$there_points[]=$x;
			$there_points[]=$y;
			$y=array_pop($back_points);
			$x=array_pop($back_points);
		} while (!is_null($y));

		$arrayindex=0;

		if ($direction < 0) $arrayindex=1;

		if (!is_null($fillcolours[$arrayindex]))
			{ imagefilledpolygon($image, $there_points, count($there_points) / 2, $arrowsettings[$dir][4]); }
		
		$areaname = "LINK:" . $linkname. ":$dir";
		$map->imap->addArea("Polygon", $areaname, '', $there_points);
		debug ("Adding Poly imagemap for $areaname\n");

		if (!is_null($outlinecolour))
			imagepolygon($image, $there_points, count($there_points) / 2, $arrowsettings[$dir][5]);
	}
}

function unformat_number($instring, $kilo = 1000)
{
	$matches=0;
	$number=0;

	if (preg_match("/([0-9\.]+)(M|G|K|T|m|u)/", $instring, $matches))
	{
		$number=floatval($matches[1]);

		if ($matches[2] == 'K') { $number=$number * $kilo; }
		if ($matches[2] == 'M') { $number=$number * $kilo * $kilo; }
		if ($matches[2] == 'G') { $number=$number * $kilo * $kilo * $kilo; }
		if ($matches[2] == 'T') { $number=$number * $kilo * $kilo * $kilo * $kilo; }
		// new, for absolute datastyle. Think seconds.
		if ($matches[2] == 'm') { $number=$number / $kilo; }
		if ($matches[2] == 'u') { $number=$number / ($kilo * $kilo); }
	}
	else { $number=floatval($instring); }

	return ($number);
}

// given a compass-point, and a width & height, return a tuple of the x,y offsets
function calc_offset($offsetstring, $width, $height)
{
	if(preg_match("/^([-+]?\d+):([-+]?\d+)$/",$offsetstring,$matches))
	{
		debug("Numeric Offset found\n");
		return(array($matches[1],$matches[2]));
	}
	else
	{
		switch (strtoupper($offsetstring))
		{
		case 'N':
			return (array(0, -$height / 2));

			break;

		case 'S':
			return (array(0, $height / 2));

			break;

		case 'E':
			return (array(+$width / 2, 0));

			break;

		case 'W':
			return (array(-$width / 2, 0));

			break;

		case 'NW':
			return (array(-$width / 2, -$height / 2));

			break;

		case 'NE':
			return (array($width / 2, -$height / 2));

			break;

		case 'SW':
			return (array(-$width / 2, $height / 2));

			break;

		case 'SE':
			return (array($width / 2, $height / 2));

			break;

		default:
			return (array(0, 0));

			break;
		}
	}
}

// These next two are based on perl's Number::Format module
// by William R. Ward, chopped down to just what I needed

function format_number($number, $precision = 2, $trailing_zeroes = 0)
{
	$sign=1;

	if ($number < 0)
	{
		$number=abs($number);
		$sign=-1;
	}

	$number=round($number, $precision);
	$integer=intval($number);

	if (strlen($integer) < strlen($number)) { $decimal=substr($number, strlen($integer) + 1); }

	if (!isset($decimal)) { $decimal=''; }

	$integer=$sign * $integer;

	if ($decimal == '') { return ($integer); }
	else { return ($integer . "." . $decimal); }
}

function nice_bandwidth($number, $kilo = 1000,$decimals=1,$below_one=TRUE)
{
	$suffix='';

	if ($number == 0)
		return '0';

	$milli = 1/$kilo;
	$micro = 1/$milli;
	$nano = 1/$micro;
	$mega=$kilo * $kilo;
	$giga=$mega * $kilo;
	$tera=$giga * $kilo;

	if ($number > $tera)
	{
		$number/=$tera;
		$suffix="T";
	}
	elseif ($number > $giga)
	{
		$number/=$giga;
		$suffix="G";
	}
	elseif ($number > $mega)
	{
		$number/=$mega;
		$suffix="M";
	}
	elseif ($number > $kilo)
	{
		$number/=$kilo;
		$suffix="K";
	}
        elseif ($number > 1)
        {
                $number = $number;
                $suffix="";
        }
	elseif (($below_one==TRUE) && ($number > $milli))
	{
		$number/=$milli;
		$suffix="m";
	}
	elseif (($below_one==TRUE) && ($number > $micro))
	{
		$number/=$micro;
		$suffix="u";
	}
	elseif (($below_one==TRUE) && ($number > $nano))
	{
		$number/=$nano;
		$suffix="n";
	}

	$result=format_number($number, $decimals) . $suffix;
	return ($result);
}

function nice_scalar($number, $kilo = 1000, $decimals=1)
{
	$suffix='';

	if ($number == 0)
		return '0';

	$mega=$kilo * $kilo;
	$giga=$mega * $kilo;
	$tera=$giga * $kilo;

	if ($number > $tera)
	{
		$number/=$tera;
		$suffix="T";
	}
	elseif ($number > $giga)
	{
		$number/=$giga;
		$suffix="G";
	}
	elseif ($number > $mega)
	{
		$number/=$mega;
		$suffix="M";
	}
	elseif ($number > $kilo)
	{
		$number/=$kilo;
		$suffix="K";
	}
        elseif ($number > 1)
        {
                $number = $number;
                $suffix="";
        }
	elseif ($number < (1 / ($kilo)))
	{
		$number=$number * $mega;
		$suffix="u";
	}
	elseif ($number < 1)
	{
		$number=$number * $kilo;
		$suffix="m";
	}

	$result=format_number($number, $decimals) . $suffix;
	return ($result);
}


// ***********************************************

// we use enough points in various places to make it worth a small class to save some variable-pairs.
class Point
{
	var $x, $y;
	function Point($x=0,$y=0)
	{
		$this->x = $x;
		$this->y = $y;
	}
}

// similarly for 2D vectors
class Vector
{
	var $dx, $dy;
	
	function Vector($dx=0,$dy=0)
	{
		$this->dx = $dx;
		$this->dy = $dy;
	}
	
	function normalise()
	{
		$len = $this->length();
		$this->dx = $this->dx/$len;
		$this->dy = $this->dy/$len;
	}
	
	function length()
	{
		return( sqrt(($this->dx)*($this->dx) + ($this->dy)*($this->dy)) );
	}
}

class Colour
{
	var $r,$g,$b;
	
	// take in an existing value and create a Colour object for it
	function Colour()
	{
		if(func_num_args() == 3) # a set of 3 colours
		{
			$this->r = func_get_arg(0); # r
			$this->g = func_get_arg(1); # g
			$this->b = func_get_arg(2); # b
			#print "3 args";
			#print $this->as_string()."--";
		}
		
		if( (func_num_args() == 1) && gettype(func_get_arg(0))=='array' ) # an array of 3 colours
		{
			#print "1 args";
			$ary = func_get_arg(0);
			$this->r = $ary[0];
			$this->g = $ary[1];
			$this->b = $ary[2];
		}
	}
	
	// Is this a transparent/none colour?
	function is_none()
	{
		if($this->r == -1 && $this->r == -1 && $this->r == -1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// allocate a colour in the appropriate image context
	// - things like scale colours are used in multiple images now (the scale, several nodes, the main map...)
	function gdallocate($image_ref)
	{
		return(myimagecolorallocate($image_ref, $this->r, $this->g, $this->b));
	}
	
	// based on an idea from: http://www.bennadel.com/index.cfm?dax=blog:902.view
	function contrast()
	{
		if( (($this->r + $this->g + $this->b) > 500)
		 || ($this->g > 140)
		)
		{
			return( array(0,0,0) );
		}
		else
		{
			return( array(255,255,255) );
		}
	}
	
	// make a printable version, for debugging
	// - optionally take a format string, so we can use it for other things (like WriteConfig, or hex in stylesheets)
	function as_string($format = "RGB(%d,%d,%d)")
	{
		return (sprintf($format, $this->r, $this->g, $this->b));
	}
}

// ***********************************************

// template class for data sources. All data sources extend this class.
// I really wish PHP4 would just die overnight
class WeatherMapDataSource
{
	// Initialize - called after config has been read (so SETs are processed)
	// but just before ReadData. Used to allow plugins to verify their dependencies
	// (if any) and bow out gracefully. Return FALSE to signal that the plugin is not
	// in a fit state to run at the moment.
	function Init(&$map) { return TRUE; }

	// called with the TARGET string. Returns TRUE or FALSE, depending on whether it wants to handle this TARGET
	// called by map->ReadData()
	function Recognise( $targetstring ) { return FALSE; }

	// the actual ReadData
	//   returns an array of two values (in,out). -1,-1 if it couldn't get valid data
	//   configline is passed in, to allow for better error messages
	//   itemtype and itemname may be used as part of the target (e.g. for TSV source line)
	function ReadData($targetstring, $configline, $itemtype, $itemname, $map) { return (array(-1,-1)); }
}

// template classes for the pre- and post-processor plugins
class WeatherMapPreProcessor
{
	function run($map) { return FALSE; }
}

class WeatherMapPostProcessor
{
	function run($map) { return FALSE; }
}

// ***********************************************

// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
class WeatherMapBase
{
	var $notes = array();
	var $hints = array();
	var $inherit_fieldlist;

	function add_note($name,$value)
	{
		$this->notes[$name] = $value;
	}

	function get_note($name)
	{
		if(isset($this->notes[$name]))
		{
			return($this->notes[$name]);
		}
		else
		{
			return(NULL);
		}
	}

	function add_hint($name,$value)
	{
		$this->hints[$name] = $value;
		# warn("Adding hint $name to ".$this->my_type()."/".$this->name."\n");
	}


	function get_hint($name)
	{
		if(isset($this->hints[$name]))
		{
			return($this->hints[$name]);
		}
		else
		{
			return(NULL);
		}
	}
}

// The 'things on the map' class. More common code (mainly variables, actually)
class WeatherMapItem extends WeatherMapBase
{
	var $owner;

	var $configline;
	var $infourl;
	var $overliburl;
	var $overlibwidth, $overlibheight;
	var $overlibcaption;
	var $my_default;
	var $config_override;	# used by the editor to allow text-editing

	function my_type() {  return "ITEM"; }
}


class WeatherMapNode extends WeatherMapItem
{
	var $owner;
	var $x,
		$y;
	var $original_x, $original_y,$relative_resolved;
	var $width,
		$height;
	var $label, $proclabel,
		$labelfont;
	var $name;
	var $infourl;
	var $notes;
	var $overliburl;
	var $overlibwidth,
		$overlibheight;
	var $overlibcaption;
	var $maphtml;
	var $selected = 0;
	var $iconfile, $iconscalew, $iconscaleh;
	var $targets = array();
	var $bandwidth_in,         $bandwidth_out;
	var $inpercent, $outpercent;
	var $max_bandwidth_in,     $max_bandwidth_out;
	var $max_bandwidth_in_cfg, $max_bandwidth_out_cfg;
	var $labeloffset, $labeloffsetx, $labeloffsety;
		
	var $inherit_fieldlist;

	var $labelbgcolour;
	var $labeloutlinecolour;
	var $labelfontcolour;
	var $labelfontshadowcolour;
	var $cachefile;
	var $usescale;
	var $inscalekey,$outscalekey;
	# var $incolour,$outcolour;
	var $scalevar;
	var $notestext;
	var $image;
	var $centre_x, $centre_y;
	var $relative_to;

	function WeatherMapNode()
	{
		$this->inherit_fieldlist=array
			(
				'my_default' => NULL,
				'label' => '',
				'proclabel' => '',
				'usescale' => 'DEFAULT',
				'scalevar' => 'in',
				'labelfont' => 3,
				'relative_to' => '',
				'relative_resolved' => FALSE,
				'x' => 0,
				'y' => 0,
				'inscalekey'=>'', 'outscalekey'=>'',
				#'incolour'=>-1,'outcolour'=>-1,
				'original_x' => 0,
				'original_y' => 0,
				'inpercent'=>0,
				'outpercent'=>0,
				'iconfile' => '',
				'iconscalew' => 0,
				'iconscaleh' => 0,
				'targets' => array(),
				'infourl' => '',
				'notestext' => '',
				'notes' => array(),
				'hints' => array(),
				'overliburl' => '',
				'overlibwidth' => 0,
				'overlibheight' => 0,
				'overlibcaption' => '',
				'labeloutlinecolour' => array(0, 0, 0),
				'labelbgcolour' => array(255, 255, 255),
				'labelfontcolour' => array(0, 0, 0),
				'labelfontshadowcolour' => array(-1, -1, -1),
				
				'labeloffset' => '',
				'labeloffsetx' => 0,
				'labeloffsety' => 0,
				'max_bandwidth_in' => 100,
				'max_bandwidth_out' => 100,
				'max_bandwidth_in_cfg' => '100',
				'max_bandwidth_out_cfg' => '100'
			);

		$this->width = 0;
		$this->height = 0;
		$this->centre_x = 0;
		$this->centre_y = 0;
		$this->image = NULL;
	}

	function my_type() {  return "NODE"; }

	// make a mini-image, containing this node and nothing else
	// figure out where the real NODE centre is, relative to the top-left corner.
	function pre_render($im, &$map)
	{
		// apparently, some versions of the gd extension will crash
		// if we continue...
		if($this->label == '' && $this->iconfile=='') return;

		// start these off with sensible values, so that bbox
		// calculations are easier.
		$icon_x1 = $this->x; $icon_x2 = $this->x;
		$icon_y1 = $this->y; $icon_y2 = $this->y;
		$label_x1 = $this->x; $label_x2 = $this->x;
		$label_y1 = $this->y; $label_y2 = $this->y;
		$boxwidth = 0; $boxheight = 0;
		$icon_w = 0;
		$icon_h = 0;

		$col = new Colour(-1,-1,-1);
		# print $col->as_string();
		   
		// if a target is specified, and you haven't forced no background, then the background will
		// come from the SCALE in USESCALE
		if( !empty($this->targets) && $this->usescale != 'none' )
		{
			$pc = 0;
			
			if($this->scalevar == 'in')
			{
				$pc = $this->inpercent;
				
			}
			if($this->scalevar == 'out')
			{
				$pc = $this->outpercent;
				
			}
			
			// debug("Choosing NODE BGCOLOR for ".$this->name." based on $pc %\n");

			    list($col,$node_scalekey) = $map->NewColourFromPercent($pc, $this->usescale,$this->name);
			    // $map->nodes[$this->name]->scalekey = $node_scalekey;
		}
		elseif($this->labelbgcolour != array(-1,-1,-1))
		{
			// $col=myimagecolorallocate($node_im, $this->labelbgcolour[0], $this->labelbgcolour[1], $this->labelbgcolour[2]);
			$col = new Colour($this->labelbgcolour);
		}

		# print $col->as_string();
		

		// figure out a bounding rectangle for the label
		if ($this->label != '')
		{
			$padding = 4.0;
			$padfactor = 1.0;

			$this->proclabel = $map->ProcessString($this->label,$this);

			list($strwidth, $strheight) = $map->myimagestringsize($this->labelfont, $this->proclabel);

			$boxwidth = ($strwidth * $padfactor) + $padding;
			$boxheight = ($strheight * $padfactor) + $padding;

			debug ("Node->pre_render: Label Metrics are: $strwidth x $strheight -> $boxwidth x $boxheight\n");

			$label_x1 = $this->x - ($boxwidth / 2);
			$label_y1 = $this->y - ($boxheight / 2);

			$label_x2 = $this->x + ($boxwidth / 2);
			$label_y2 = $this->y + ($boxheight / 2);

			$txt_x = $this->x - ($strwidth / 2);
			$txt_y = $this->y + ($strheight / 2);

			# $this->width = $boxwidth;
			# $this->height = $boxheight;
			$map->nodes[$this->name]->width = $boxwidth;
			$map->nodes[$this->name]->height = $boxheight;

			# print "TEXT at $txt_x , $txt_y\n";

		}                

		// figure out a bounding rectangle for the icon
		if ($this->iconfile != '')
		{
			$icon_im = NULL;
			$icon_w = 0;
			$icon_h = 0;
			
			if($this->iconfile == 'inpie' || $this->iconfile == 'nink' || $this->iconfile == 'box' || $this->iconfile == 'outpie' || $this->iconfile == 'round')
			{
				// this is an artificial icon - we don't load a file for it
				
				// XXX - add the actual DRAWING CODE!
								
				$icon_im = imagecreatetruecolor($this->iconscalew,$this->iconscaleh);
				imageSaveAlpha($icon_im, TRUE);
		
				$nothing=imagecolorallocatealpha($icon_im,128,0,0,127);
				imagefill($icon_im, 0, 0, $nothing);
				
				$ink = imagecolorallocate($icon_im,0,0,0);
				// $fill = imagecolorallocate($icon_im,255,255,255);
				if($this->iconfile=='box')
				{
					imagefilledrectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, $col->gdallocate($icon_im));
					if ($this->labeloutlinecolour != array(-1,-1,-1))
					{
						$ink=myimagecolorallocate($icon_im,$this->labeloutlinecolour[0],
							$this->labeloutlinecolour[1], $this->labeloutlinecolour[2]);
						# imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $ink);
						imagerectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, $ink);
					}
					
				}
				
				if($this->iconfile=='round')
				{
					$rx = $this->iconscalew/2-1;
					$ry = $this->iconscaleh/2-1;
					imagefilledellipse($icon_im,$rx,$ry,$rx*2,$ry*2,$col->gdallocate($icon_im));
					if ($this->labeloutlinecolour != array(-1,-1,-1))
					{
						$ink=myimagecolorallocate($icon_im,$this->labeloutlinecolour[0],
							$this->labeloutlinecolour[1], $this->labeloutlinecolour[2]);
						# imagerectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, $ink);
						imageellipse($icon_im,$rx,$ry,$rx*2,$ry*2,$ink);
					}					
				}
				
				if($this->iconfile=='inpie') { warn('inpie not implemented yet [WMWARN99]'); }
				if($this->iconfile=='outpie') { warn('outpie not implemented yet [WMWARN99]'); }
				
			}
			else
			{
				$this->iconfile = $map->ProcessString($this->iconfile ,$this);
				if (is_readable($this->iconfile))
				{
					imagealphablending($im, true);
					// draw the supplied icon, instead of the labelled box
	
					$icon_im = imagecreatefromfile($this->iconfile);
					# $icon_im = imagecreatefrompng($this->iconfile);
	
					if ($icon_im)
					{
						$icon_w = imagesx($icon_im);
						$icon_h = imagesy($icon_im);
	
						if(($this->iconscalew * $this->iconscaleh) > 0)
						{
							imagealphablending($icon_im, true);
	
							debug("SCALING ICON here\n");
							if($icon_w > $icon_h)
							{
								$scalefactor = $icon_w/$this->iconscalew;
							}
							else
							{
								$scalefactor = $icon_h/$this->iconscaleh;
							}
							$new_width = $icon_w / $scalefactor;
							$new_height = $icon_h / $scalefactor;
							$scaled = imagecreatetruecolor($new_width, $new_height);
							imagealphablending($scaled,false);
							imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w, $icon_h);
							imagedestroy($icon_im);
							$icon_im = $scaled;
							
						}
					}
					else { warn ("Couldn't open PNG ICON: " . $this->iconfile . " - is it a PNG?\n"); }
				}
				else
				{
					warn ("ICON " . $this->iconfile . " does not exist, or is not readable. Check path and permissions.\n");
				}
			}

			if($icon_im)
			{
				$icon_w = imagesx($icon_im);
				$icon_h = imagesy($icon_im);
							
				$icon_x1 = $this->x - $icon_w / 2;
				$icon_y1 = $this->y - $icon_h / 2;
				$icon_x2 = $this->x + $icon_w / 2;
				$icon_y2 = $this->y + $icon_h / 2;
				
				$map->nodes[$this->name]->width = imagesx($icon_im);
				$map->nodes[$this->name]->height = imagesy($icon_im);
	
				$map->imap->addArea("Rectangle", "NODE:" . $this->name . ':0', '', array($icon_x1, $icon_y1, $icon_x2, $icon_y2));
			}
			
		}

		

		// do any offset calculations

		if ( ($this->labeloffset != '') && (($this->iconfile != '')) )
		{
			$this->labeloffsetx = 0;
			$this->labeloffsety = 0;

			list($dx, $dy) = calc_offset($this->labeloffset,
				($icon_w + $boxwidth -1),
				($icon_h + $boxheight)
			);

			$this->labeloffsetx = $dx;
			$this->labeloffsety = $dy;

		}

		$label_x1 += $this->labeloffsetx;
		$label_x2 += $this->labeloffsetx;
		$label_y1 += $this->labeloffsety;
		$label_y2 += $this->labeloffsety;

		if($this->label != '')
		{
			$map->imap->addArea("Rectangle", "NODE:" . $this->name .':1', '', array($label_x1, $label_y1, $label_x2, $label_y2));
		}

		// work out the bounding box of the whole thing

		$bbox_x1 = min($label_x1,$icon_x1);
		$bbox_x2 = max($label_x2,$icon_x2)+1;
		$bbox_y1 = min($label_y1,$icon_y1);
		$bbox_y2 = max($label_y2,$icon_y2)+1;

		#           imagerectangle($im,$bbox_x1,$bbox_y1,$bbox_x2,$bbox_y2,$map->selected);
		#         imagerectangle($im,$label_x1,$label_y1,$label_x2,$label_y2,$map->black);
		#       imagerectangle($im,$icon_x1,$icon_y1,$icon_x2,$icon_y2,$map->black);

		// create TWO imagemap entries - one for the label and one for the icon
		// (so we can have close-spaced icons better)              


		$temp_width = $bbox_x2-$bbox_x1;
		$temp_height = $bbox_y2-$bbox_y1;
		// create an image of that size and draw into it
		$node_im=imagecreatetruecolor($temp_width,$temp_height );
		// ImageAlphaBlending($node_im, FALSE); 
		imageSaveAlpha($node_im, TRUE);

		$nothing=imagecolorallocatealpha($node_im,128,0,0,127);
		imagefill($node_im, 0, 0, $nothing);
		
		#$col = $col->gdallocate($node_im);

		// imagefilledrectangle($node_im,0,0,$temp_width,$temp_height,  $nothing);

		$label_x1 -= $bbox_x1;
		$label_x2 -= $bbox_x1;
		$label_y1 -= $bbox_y1;
		$label_y2 -= $bbox_y1;

		$icon_x1 -= $bbox_x1;
		$icon_x2 -= $bbox_x1;
		$icon_y1 -= $bbox_y1;
		$icon_y2 -= $bbox_y1;


		// Draw the icon, if any
		if(isset($icon_im))
		{
			imagecopy($node_im, $icon_im, $icon_x1, $icon_y1, 0, 0, imagesx($icon_im), imagesy($icon_im));
			imagedestroy($icon_im);
		}  

		// Draw the label, if any
		if ($this->label != '')
		{
			$txt_x -= $bbox_x1;
			$txt_x += $this->labeloffsetx;
			$txt_y -= $bbox_y1;
			$txt_y += $this->labeloffsety;

			#       print "FINAL TEXT at $txt_x , $txt_y\n";

			// if there's an icon, then you can choose to have no background
			
			

			if(! $col->is_none() )
			{
			    imagefilledrectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $col->gdallocate($node_im));
			}

			if ($this->selected)
			{
				imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $map->selected);
				// would be nice if it was thicker, too...
				imagerectangle($node_im, $label_x1 + 1, $label_y1 + 1, $label_x2 - 1, $label_y2 - 1, $map->selected);
			}
			else
			{
				if ($this->labeloutlinecolour != array(-1,-1,-1))
				{
					$col=myimagecolorallocate($node_im,$this->labeloutlinecolour[0],
						$this->labeloutlinecolour[1], $this->labeloutlinecolour[2]);
					imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $col);
				}
			}
			#}

			if ($this->labelfontshadowcolour != array(-1,-1,-1))
			{
				$col=myimagecolorallocate($im, $this->labelfontshadowcolour[0], $this->labelfontshadowcolour[1],
					$this->labelfontshadowcolour[2]);
				$map->myimagestring($node_im, $this->labelfont, $txt_x + 1, $txt_y + 1, $this->proclabel, $col);
			}

			$col=myimagecolorallocate($node_im, $this->labelfontcolour[0], $this->labelfontcolour[1],
				$this->labelfontcolour[2]);
			$map->myimagestring($node_im, $this->labelfont, $txt_x, $txt_y, $this->proclabel, $col);
		}

		# imagerectangle($node_im,$label_x1,$label_y1,$label_x2,$label_y2,$map->black);
		# imagerectangle($node_im,$icon_x1,$icon_y1,$icon_x2,$icon_y2,$map->black);              

		$map->nodes[$this->name]->centre_x = $this->x - $bbox_x1;
		$map->nodes[$this->name]->centre_y = $this->y - $bbox_y1;

		if(1==0)
		{

			imageellipse($node_im, $this->centre_x, $this->centre_y, 8, 8, $map->selected);

			foreach (array("N","S","E","W","NE","NW","SE","SW") as $corner)
			{
				list($dx, $dy)=calc_offset($corner, $this->width, $this->height);
				imageellipse($node_im, $this->centre_x + $dx, $this->centre_y + $dy, 5, 5, $map->selected);    
			}
		}

		# $this->image = $node_im;
		$map->nodes[$this->name]->image = $node_im;
	}

	function update_cache($cachedir,$mapname)
	{
		$cachename = $cachedir."/node_".md5($mapname."/".$this->name).".png";
		// save this image to a cache, for the editor
		imagepng($this->image,$cachename);
	}        

	// draw the node, using the pre_render() output
	function NewDraw($im, &$map)
	{
		// take the offset we figured out earlier, and just blit
		// the image on. Who says "blit" anymore?

		// it's possible that there is no image, so better check.
		if(isset($this->image))
		{
			imagealphablending($im, true);
			imagecopy ( $im, $this->image, $this->x - $this->centre_x, $this->y - $this->centre_y, 0, 0, imagesx($this->image), imagesy($this->image) );
		}

	}

	// take the pre-rendered node and write it to a file so that
	// the editor can get at it.
	function WriteToCache()
	{
	}
	
	function DrawNINK($im, &$map, $size=32)
	{
		$quarter = $size/4;
	
		$x = $this->x;
		$y = $this->y;
	
		$col1 = imagecolorallocate($im,255,0,0);
		$col2 = imagecolorallocate($im,0,255,0);
		$outline = imagecolorallocate($im,255,255,255);
		$font = 2;
	
		imagefilledarc($im,$x,$y,$size,$size,270,90,$col1,IMG_ARC_PIE);
		imagefilledarc($im,$x,$y,$size,$size,90,270,$col2,IMG_ARC_PIE);
	
		imagefilledarc($im,$x,$y+$quarter,$quarter*2,$quarter*2,0,360,$col1,IMG_ARC_PIE);
		imagefilledarc($im,$x,$y-$quarter,$quarter*2,$quarter*2,0,360,$col2,IMG_ARC_PIE);
	
		// draw in the text shadows first, if needed
		if ($this->labelfontshadowcolour != array(-1,-1,-1))
		{
			$col=myimagecolorallocate($im, $this->labelfontshadowcolour[0], $this->labelfontshadowcolour[1],
				$this->labelfontshadowcolour[2]);
				
			imagestring($im, $font, 1 + $x - imagefontwidth($font)*2, 1 + $y-$quarter-imagefontheight($font)/2,"2.5M",$col);
			imagestring($im, $font, 1 + $x - imagefontwidth($font)*2, 1 + $y+$quarter-imagefontheight($font)/2,"786M",$col);
		}
	
		imagestring($im, $font, $x - imagefontwidth($font)*2, $y-$quarter-imagefontheight($font)/2,"2.5M",$outline);
		imagestring($im, $font, $x - imagefontwidth($font)*2, $y+$quarter-imagefontheight($font)/2,"786M",$outline);
	
	}

	function calc_size()
	{
		$this->width=0;
		$this->height=0;

		// calculate the size of the NODE box, so we can make links end at corners.
		if ($this->label != '')
		{
			$padding=0;
			$font=$this->labelfont;

			list($strwidth, $strheight)=$this->owner->myimagestringsize($font, $this->label);

			$boxwidth=$strwidth * 1.1;
			$boxheight=$strheight * 1.1;

			$this->width=$boxwidth;
			$this->height=$boxheight;
		}

		// if there's an icon, then that's what the corners relate to
		if ($this->iconfile != '')
		{
			# $temp_im=imagecreatefrompng($this->iconfile);
			$temp_im = imagecreatefromfile($this->iconfile);

			if ($temp_im)
			{
				$this->width=imagesx($temp_im);
				$this->height=imagesy($temp_im);
			}

			imagedestroy ($temp_im);
		}

		debug ("PRECALC $this->name: $this->width x $this->height\n");
	}

	function Reset(&$newowner)
	{
		$this->owner=$newowner;

		if (isset($this->owner->defaultnode) && $this->name != 'DEFAULT') {
			// use the defaults from DEFAULT
			$this->CopyFrom($this->owner->defaultnode); 
			$this->my_default = $this->owner->defaultnode;
		}
		else
		{
			// use the default defaults
			foreach (array_keys($this->inherit_fieldlist)as $fld)
			{ $this->$fld = $this->inherit_fieldlist[$fld]; }
		}
		# warn($this->name.": ".var_dump($this->hints)."\n");
		# warn("DEF: ".var_dump($this->owner->defaultnode->hints)."\n");
		#if($this->name == 'North')
		#{
	#		warn("In Reset, North says: ".$this->nodes['North']->hints['sigdigits']."\n");
	#	}
	}

	function CopyFrom(&$source)
	{
		foreach (array_keys($this->inherit_fieldlist)as $fld) { $this->$fld=$source->$fld; }
	}

	function WriteConfig()
	{
		$output='';
		
		// This allows the editor to wholesale-replace a single node's configuration
		// at write-time - it should include the leading NODE xyz line (to allow for renaming)
		if($this->config_override != '')
		{
			$output  = $this->config_override."\n";
		}
		else
		{
			$comparison=($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['label'] : $this->owner->defaultnode->label);
	
			if ($this->label != $comparison) { $output.="\tLABEL " . $this->label . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['infourl'] : $this->owner->defaultnode->infourl);
	
			if ($this->infourl != $comparison) { $output.="\tINFOURL " . $this->infourl . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['notestext'] : $this->owner->defaultnode->notestext);
	
			if ($this->notestext != $comparison) { $output.="\tNOTES " . $this->notestext . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overliburl'] : $this->owner->defaultnode->overliburl);
	
			if ($this->overliburl != $comparison) { $output.="\tOVERLIBGRAPH " . $this->overliburl . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['iconfile'] : $this->owner->defaultnode->iconfile);
			if ($this->iconfile != $comparison) { 
				$output.="\tICON ";
				if($this->iconscalew > 0) {
					$output .= $this->iconscalew." ".$this->iconscaleh." ";
				}
				$output .= $this->iconfile . "\n"; 
			}
	
			$comparison=($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['labelfont'] : $this->owner->defaultnode->labelfont);
	
			if ($this->labelfont != $comparison) { $output.="\tLABELFONT " . $this->labelfont . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['labeloffset'] : $this->owner->defaultnode->labeloffset);
	
			if ($this->labeloffset != $comparison) { $output.="\tLABELOFFSET " . $this->labeloffset . "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['targets'] : $this->owner->defaultnode->targets);
	
			if ($this->targets != $comparison)
			{
				$output.="\tTARGET";
	
				foreach ($this->targets as $target) { $output.=" " . $target[4]; }
	
				$output.="\n";
			}
	
			$comparison = ($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['usescale'] : $this->owner->defaultnode->usescale);
			$comparison2 = ($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['scalevar'] : $this->owner->defaultnode->scalevar);
	
			if ( ($this->usescale != $comparison) || ($this->scalevar != $comparison2) )
			{ $output.="\tUSESCALE " . $this->usescale . " " . $this->scalevar . "\n"; }
	
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibcaption'] : $this->owner->defaultnode->overlibcaption);
	
			if ($this->overlibcaption != $comparison) { $output.="\tOVERLIBCAPTION " . $this->overlibcaption . "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibwidth'] : $this->owner->defaultnode->overlibwidth);
	
			if ($this->overlibwidth != $comparison) { $output.="\tOVERLIBWIDTH " . $this->overlibwidth . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibheight'] : $this->owner->defaultnode->overlibheight);
	
			if ($this->overlibheight != $comparison) { $output.="\tOVERLIBHEIGHT " . $this->overlibheight . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['labelbgcolour'] : $this->owner->defaultnode->labelbgcolour);
	
			if ($this->labelbgcolour != $comparison) { $output.="\tLABELBGCOLOR " . render_colour(
				$this->labelbgcolour)
				. "\n"; }
	
			$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['labelfontcolour']
			: $this->owner->defaultnode->labelfontcolour);
	
			if ($this->labelfontcolour != $comparison) { $output.="\tLABELFONTCOLOR " . render_colour(
				$this->labelfontcolour)
				. "\n"; }
	
			$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['labeloutlinecolour']
			: $this->owner->defaultnode->labeloutlinecolour);
	
			if ($this->labeloutlinecolour != $comparison) { $output.="\tLABELOUTLINECOLOR " . render_colour(
				$this->labeloutlinecolour) . "\n";
			}
	
			$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['labelfontshadowcolour']
				: $this->owner->defaultnode->labelfontshadowcolour);
	
			if ($this->labelfontshadowcolour != $comparison)
			{ $output.="\tLABELFONTSHADOWCOLOR " . render_colour($this->labelfontshadowcolour) . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['labeloffsetx'] : $this->owner->defaultnode->labeloffsetx);
			$comparison2=($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['labeloffsety'] : $this->owner->defaultnode->labeloffsety);
	
			if (($this->labeloffsetx != $comparison) || ($this->labeloffsety != $comparison2))
			{ $output.="\tLABELOFFSET " . $this->labeloffsetx . " " . $this->labeloffsety . "\n"; }
	
			$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['x'] : $this->owner->defaultnode->x);
			$comparison2=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['y'] : $this->owner->defaultnode->y);
	
			if (($this->x != $comparison) || ($this->y != $comparison2))
			{
				if($this->relative_to == '')
				{ $output.="\tPOSITION " . $this->x . " " . $this->y . "\n"; }
				else
				{ $output.="\tPOSITION " . $this->relative_to . " " .  $this->original_x . " " . $this->original_y . "\n"; }
			}
	
			if (($this->max_bandwidth_in != $this->owner->defaultnode->max_bandwidth_in)
				|| ($this->max_bandwidth_out != $this->owner->defaultnode->max_bandwidth_out)
					|| ($this->name == 'DEFAULT'))
			{
				if ($this->max_bandwidth_in == $this->max_bandwidth_out)
				{ $output.="\tMAXVALUE " . $this->max_bandwidth_in_cfg . "\n"; }
				else { $output
				.="\tMAXVALUE " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n"; }
			}
			
			foreach ($this->hints as $hintname=>$hint)
			{
			  // all hints for DEFAULT node are for writing
			  // only changed ones, or unique ones, otherwise
			      if( 
			    ($this->name == 'DEFAULT')
			  ||
				    (isset($this->owner->defaultnode->hints[$hintname]) 
				    &&
				    $this->owner->defaultnode->hints[$hintname] != $hint)
				  ||
				    (!isset($this->owner->defaultnode->hints[$hintname]))
				)
			      {		      
			    $output .= "\tSET $hintname $hint\n";
			      }
			}
			if ($output != '')
			{
				$output = "NODE " . $this->name . "\n$output\n";
			}
		}
		return ($output);
	}

	function asJS()
	{
		$js='';
		$js.="Nodes[" . js_escape($this->name) . "] = {";
		$js.="x:" . $this->x . ", ";
		$js.="y:" . $this->y . ", ";
		$js.="label:" . js_escape($this->label) . ", ";
		$js.="name:" . js_escape($this->name) . ", ";
		$js.="infourl:" . js_escape($this->infourl) . ", ";
		$js.="overlibcaption:" . js_escape($this->overlibcaption) . ", ";
		$js.="overliburl:" . js_escape($this->overliburl) . ", ";
		$js.="overlibwidth:" . $this->overlibheight . ", ";
		$js.="overlibheight:" . $this->overlibwidth . ", ";
		$js.="iconfile:" . js_escape($this->iconfile);
		$js.="};\n";
		return $js;
	}

	function asJSON($complete=TRUE)
	{
		$js = '';
		$js .= "" . js_escape($this->name) . ": {";
		$js .= "x:" . ($this->x - $this->centre_x). ", ";
		$js .= "y:" . ($this->y - $this->centre_y) . ", ";
		$js .= "cx:" . $this->centre_x. ", ";
		$js .= "cy:" . $this->centre_y . ", ";
		$js .= "name:" . js_escape($this->name) . ", ";
		if($complete)
		{
			$js .= "label:" . js_escape($this->label) . ", ";
			$js .= "infourl:" . js_escape($this->infourl) . ", ";
			$js .= "overliburl:" . js_escape($this->overliburl) . ", ";
			$js .= "overlibcaption:" . js_escape($this->overlibcaption) . ", ";
	
			$js .= "overlibwidth:" . $this->overlibheight . ", ";
			$js .= "overlibheight:" . $this->overlibwidth . ", ";
			$js .= "iconfile:" . js_escape($this->iconfile). ", ";
		}
		$js .= "iconcachefile:" . js_escape($this->cachefile);
		$js .= "},\n";
		return $js;
	}

	// Set the bandwidth for this link. Convert from KMGT as necessary
	function SetBandwidth($inbw, $outbw)
	{

		$kilo = $this->owner->kilo;
		$this->max_bandwidth_in=unformat_number($inbw, $kilo);
		$this->max_bandwidth_out=unformat_number($outbw, $kilo);
		$this->max_bandwidth_in_cfg=$inbw;
		$this->max_bandwidth_out_cfg=$outbw;
		debug (sprintf("Setting bandwidth (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $inbw, $this->max_bandwidth_in, $outbw, $this->max_bandwidth_out, $kilo)); 
	}

	function Draw($im, &$map)
	{
		$strwidth=0;
		$strheight=0;

		// we do this little bit first, so that the label-offset stuff can know it
		if ($this->label != '')
		{
			$padding=0;
			$font=$this->labelfont;

			list($strwidth, $strheight)=$map->myimagestringsize($font, $this->label);

			$boxwidth=$strwidth * 1.0;
			$boxheight=$strheight * 1.0;

			debug ("Node->Draw: Metrics are: $font $strwidth x $strheight -> $boxwidth x $boxheight\n");
		}

		if ($this->iconfile != '')
		{
			if (is_readable($this->iconfile))
			{
				imagealphablending($im, true);
				// draw the supplied icon, instead of the labelled box
				$temp_im=imagecreatefrompng($this->iconfile);

				if ($temp_im)
				{
					$w=imagesx($temp_im);
					$h=imagesy($temp_im);
					$x1=$this->x - $w / 2;
					$y1=$this->y - $h / 2;
					$x2=$this->x + $w / 2;
					$y2=$this->y + $h / 2;

					imagecopy($im, $temp_im, $x1, $y1, 0, 0, $w, $h);
					$map->imap->addArea("Rectangle", "NODE:" . $this->name.':2', '', array($x1, $y1, $x2, $y2));
					imagedestroy ($temp_im);

					if ($this->labeloffset != '')
					{
						$this->labeloffsetx=0;
						$this->labeloffsety=0;

						list($dx, $dy)=calc_offset($this->labeloffset, ($w + $strwidth), ($h + $strheight));

						$this->labeloffsetx=$dx;
						$this->labeloffsety=$dy;
					}
				}
				else { warn ("Couldn't open PNG ICON: " . $this->iconfile . " - is it a PNG?\n"); }
			}
			else { warn ("ICON " . $this->iconfile
				. " does not exist, or is not readble. Check path and permissions.\n"); }
		}

		if ($this->label != '')
		{
			$x=$this->x + $this->labeloffsetx;
			$y=$this->y + $this->labeloffsety;

			$x1 = $x - ($boxwidth / 2) - 2;
			$x2 = $x + ($boxwidth / 2) + 2;
			$y1 = $y - ($boxheight / 2) - 2;
			$y2 = $y + ($boxheight / 2) + 2;

			$txt_x = $x - $strwidth / 2;
			$txt_y = $y + $strheight / 2;

			if ($this->iconfile == '')
			{
				if ($this->labelbgcolour != array
					(
						-1,
						-1,
						-1
					))
				{
					$col=myimagecolorallocate($im, $this->labelbgcolour[0], $this->labelbgcolour[1],
						$this->labelbgcolour[2]);
					imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);
				}

				if ($this->selected)
				{
					imagerectangle($im, $x1, $y1, $x2, $y2, $map->selected);
					// would be nice if it was thicker, too...
					imagerectangle($im, $x1 - 1, $y1 - 1, $x2 + 1, $y2 + 1, $map->selected);
				}
				else
				{
					if ($this->labeloutlinecolour != array
						(
							-1,
							-1,
							-1
						))
					{
						$col=myimagecolorallocate($im,                          $this->labeloutlinecolour[0],
							$this->labeloutlinecolour[1], $this->labeloutlinecolour[2]);
						imagerectangle($im, $x1, $y1, $x2, $y2, $col);
					}
				}
			}

			if ($this->labelfontshadowcolour != array
				(
					-1,
					-1,
					-1
				))
			{
				$col=myimagecolorallocate($im, $this->labelfontshadowcolour[0], $this->labelfontshadowcolour[1],
					$this->labelfontshadowcolour[2]);
				$map->myimagestring($im, $font, $txt_x + 1, $txt_y + 1, $this->label, $col);
			}

			$col=myimagecolorallocate($im, $this->labelfontcolour[0], $this->labelfontcolour[1],
				$this->labelfontcolour[2]);
			$map->myimagestring($im, $font, $txt_x, $txt_y, $this->label, $col);

			$map->imap->addArea("Rectangle", "NODE:" . $this->name. ':3', '', array($x1, $y1, $x2, $y2));
		}
	}
}

;

class WeatherMapLink extends WeatherMapItem
{
	var $owner,                $name;
	var $maphtml;
	var $a,                    $b; // the ends - references to nodes
	var $width,                $arrowstyle;
	var $bwfont,               $labelstyle, $labelboxstyle;
	var $overliburl,           $infourl;
	var $notes;
	var $overlibcaption;
	var $overlibwidth,         $overlibheight;
	var $bandwidth_in,         $bandwidth_out;
	var $max_bandwidth_in,     $max_bandwidth_out;
	var $max_bandwidth_in_cfg, $max_bandwidth_out_cfg;
	var $targets = array();
	var $a_offset,             $b_offset;
	var $in_ds,                $out_ds;
	var $selected;
	var $inpercent,            $outpercent;
	var $inherit_fieldlist;
	var $vialist = array();
	var $usescale; 
	var $outlinecolour;
	var $bwoutlinecolour;
	var $bwboxcolour;
	var $commentfont,$notestext;
	var $inscalekey,$outscalekey;
	# var $incolour,$outcolour;
	var $commentfontcolour;
	var $bwfontcolour;
	# var $incomment, $outcomment;
	var $comments = array();
	var $curvepoints;
	var $labeloffset_in, $labeloffset_out;
	var $commentoffset_in, $commentoffset_out;

	function WeatherMapLink() { $this->inherit_fieldlist=array
		(
			'my_default' => NULL,
			'width' => 7,
			'commentfont' => 1,
			'bwfont' => 2,
			'labeloffset_out' => 25,
			'labeloffset_in' => 75,
			'commentoffset_out' => 5,
			'commentoffset_in' => 95,
			'arrowstyle' => 'classic',
			'usescale' => 'DEFAULT',
			'targets' => array(),
			'infourl' => '',
			'notestext' => '',
			'notes' => array(),
			'hints' => array(),
			'comments' => array('',''),
			'overliburl' => '',
			'labelstyle' => 'percent',
			'labelboxstyle' => 'classic',
			'overlibwidth' => 0,
			'overlibheight' => 0,
			'outlinecolour' => array(0, 0, 0),
			'bwoutlinecolour' => array(0, 0, 0),
			'bwfontcolour' => array(0, 0, 0),
			'bwboxcolour' => array(255, 255, 255),
			'commentfontcolour' => array(192,192,192),
			'inpercent'=>0, 'outpercent'=>0,
			'inscalekey'=>'', 'outscalekey'=>'',
			# 'incolour'=>-1,'outcolour'=>-1,
			'a_offset' => 'C',
			'b_offset' => 'C',
			#'incomment' => '',
			#'outcomment' => '',
			'overlibcaption' => '',
			'max_bandwidth_in' => 100000000,
			'max_bandwidth_out' => 100000000,
			'max_bandwidth_in_cfg' => '100M',
			'max_bandwidth_out_cfg' => '100M'
		);
	// $this->a_offset = 'C';
	// $this->b_offset = 'C';
	//  $this->targets = array();
	}

	function Reset(&$newowner)
	{
		$this->owner=$newowner;

		if (isset($this->owner->defaultlink) && $this->name != 'DEFAULT') {
			// use the defaults from DEFAULT
			$this->CopyFrom($this->owner->defaultlink); 
			$this->my_default = $this->owner->defaultlink;
		}
		else
		{
			foreach (array_keys($this->inherit_fieldlist)as
				$fld) { $this->$fld=$this->inherit_fieldlist[$fld]; }
		}
	}

	function my_type() {  return "LINK"; }

	function CopyFrom($source)
	{
		foreach (array_keys($this->inherit_fieldlist)as $fld) { $this->$fld = $source->$fld; }
	}

	// Set the bandwidth for this link. Convert from KMGT as necessary
	function SetBandwidth($inbw, $outbw)
	{
		$kilo = $this->owner->kilo;
                $this->max_bandwidth_in=unformat_number($inbw, $kilo);
                $this->max_bandwidth_out=unformat_number($outbw, $kilo);
                $this->max_bandwidth_in_cfg=$inbw;  
                $this->max_bandwidth_out_cfg=$outbw;
                debug (sprintf("Setting bandwidth (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $inbw, $this->max_bandwidth_in, $outbw,
 $this->max_bandwidth_out, $kilo));
	}

	function DrawComments($image,$col,$width)
	{
		$curvepoints =& $this->curvepoints;
		$last = count($curvepoints)-1;
		$totaldistance = $curvepoints[$last][2];
		
		$start[OUT] = 0;
		$commentpos[OUT] = $this->commentoffset_out;
		$commentpos[IN] = $this->commentoffset_in;
		$start[IN] = $last;
		
		foreach (array(OUT,IN) as $dir)
		{
			// Time to deal with Link Comments, if any
			$comment = $this->owner->ProcessString($this->comments[$dir], $this);
	
			if($comment != '')
			{
				// XXX - redundant extra variable
				// $startindex = $start[$dir];
				$extra_percent = $commentpos[$dir];
				
				$font = $this->commentfont;
				// nudge pushes the comment out along the link arrow a little bit
				// (otherwise there are more problems with text disappearing underneath links)
				# $nudgealong = 0; $nudgeout=0;
				$nudgealong = intval($this->get_hint("comment_nudgealong"));
				$nudgeout = intval($this->get_hint("comment_nudgeout"));
	
				$extra = ($totaldistance * ($extra_percent/100));
				# $comment_index = find_distance($curvepoints,$extra);
				
				list($x,$y,$comment_index,$angle) = find_distance_coords_angle($curvepoints,$extra);
				if($comment_index!=0)
				{
				$dx = $x - $curvepoints[$comment_index][0];
				$dy = $y - $curvepoints[$comment_index][1];
				}
				else
				{
				$dx = $curvepoints[$comment_index+1][0] - $x;
				$dy = $curvepoints[$comment_index+1][1] - $y;
				}
								
				// find the normal to our link, so we can get outside the arrow
				
				$l=sqrt(($dx * $dx) + ($dy * $dy));
				$dx = $dx/$l; 	$dy = $dy/$l;
				$nx = $dy;  $ny = -$dx;
				$flipped=FALSE;
				
				// if the text will be upside-down, rotate it, flip it, and right-justify it
				// not quite as catchy as Missy's version
				if(abs($angle)>90)
				{
					# $col = $map->selected;
					$angle -= 180;
					if($angle < -180) $angle +=360;
					$edge_x = $x + $nudgealong*$dx - $nx * ($width + 4 + $nudgeout);
					$edge_y = $y + $nudgealong*$dy - $ny * ($width + 4 + $nudgeout);
					# $comment .= "@";
					$flipped = TRUE;
				}
				else
				{
					$edge_x = $x + $nudgealong*$dx + $nx * ($width + 4 + $nudgeout);
					$edge_y = $y + $nudgealong*$dy + $ny * ($width + 4 + $nudgeout);
				}
				
				list($textlength, $textheight) = $this->owner->myimagestringsize($font, $comment);
				
				if( !$flipped && ($extra + $textlength) > $totaldistance)
				{					
					$edge_x -= $dx * $textlength;
					$edge_y -= $dy * $textlength;
					# $comment .= "#";
				}
				
				if( $flipped && ($extra - $textlength) < 0)
				{					
					$edge_x += $dx * $textlength;
					$edge_y += $dy * $textlength;
					# $comment .= "%";
				}
				
				// FINALLY, draw the text!
				# imagefttext($image, $fontsize, $angle, $edge_x, $edge_y, $col, $font,$comment);
				$this->owner->myimagestring($image, $font, $edge_x, $edge_y, $comment, $col, $angle);
				#imagearc($image,$x,$y,10,10,0, 360,$this->owner->selected);
				#imagearc($image,$edge_x,$edge_y,10,10,0, 360,$this->owner->selected);
			}
		}
	}

	function Draw($im, &$map)
	{
		// Get the positions of the end-points
		$x1=$map->nodes[$this->a->name]->x;
	        $y1=$map->nodes[$this->a->name]->y;

		$x2=$map->nodes[$this->b->name]->x;
		$y2=$map->nodes[$this->b->name]->y;
		
		// Adjust them if there's an offset requested
		#$a_height=$map->nodes[$this->a->name]->height;
		#$a_width=$map->nodes[$this->a->name]->width;

#		$b_height=$map->nodes[$this->b->name]->height;
#		$b_width=$map->nodes[$this->b->name]->width;

		list($dx, $dy)=calc_offset($this->a_offset, $map->nodes[$this->a->name]->width, $map->nodes[$this->a->name]->height);
		$x1+=$dx;
		$y1+=$dy;

		list($dx, $dy)=calc_offset($this->b_offset, $map->nodes[$this->b->name]->width, $map->nodes[$this->b->name]->height);
		$x2+=$dx;
		$y2+=$dy;

		$outline_colour=NULL;
		$comment_colour=NULL;

		if ($this->outlinecolour != array(-1,-1,-1))
		{
				$outline_colour=myimagecolorallocate(
					$im, $this->outlinecolour[0], $this->outlinecolour[1],
					$this->outlinecolour[2]);
		}

		if ($this->commentfontcolour != array(-1,-1,-1))
		{
				$comment_colour=myimagecolorallocate(
					$im, $this->commentfontcolour[0], $this->commentfontcolour[1],
					$this->commentfontcolour[2]);
		}

		$xpoints = array ( );
		$ypoints = array ( );

		$xpoints[]=$x1;
		$ypoints[]=$y1;

		# warn("There are VIAs.\n");
		foreach ($this->vialist as $via)
		{
			# imagearc($im, $via[0],$via[1],20,20,0,360,$map->selected);
			$xpoints[]=$via[0];
			$ypoints[]=$via[1];
		}

		$xpoints[]=$x2;
		$ypoints[]=$y2;

		list($link_in_colour,$link_in_scalekey) = $map->ColourFromPercent($im, $this->inpercent,$this->usescale,$this->name);
		list($link_out_colour,$link_out_scalekey) = $map->ColourFromPercent($im, $this->outpercent,$this->usescale,$this->name);
		
	//	$map->links[$this->name]->inscalekey = $link_in_scalekey;
	//	$map->links[$this->name]->outscalekey = $link_out_scalekey;
		
		$link_width=$this->width;
		// these will replace the one above, ultimately.
		$link_in_width=$this->width;
		$link_out_width=$this->width;
			
		// for bulging animations
		if ( ($map->widthmod) || ($map->get_hint('link_bulge') == 1))
		{
			// a few 0.1s and +1s to fix div-by-zero, and invisible links
			$link_width = (($link_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			// these too
			$link_in_width = (($link_in_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			$link_out_width = (($link_out_width * $this->outpercent * 1.5 + 0.1) / 100) + 1;
		}

		
		// Calculate the spine points - the actual curve	
		$this->curvepoints = calc_curve($xpoints, $ypoints);
				
		draw_curve($im, $this->curvepoints,
			$link_width, $outline_colour, $comment_colour, array($link_in_colour, $link_out_colour),
			$this->name, $map);

		$this->DrawComments($im,$comment_colour,$link_width*1.1);

		$curvelength = $this->curvepoints[count($this->curvepoints)-1][2];
		// figure out where the labels should be, and what the angle of the curve is at that point
		list($q1_x,$q1_y,$junk,$q1_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_out/100)*$curvelength);
		list($q3_x,$q3_y,$junk,$q3_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_in/100)*$curvelength);

		# imageline($im, $q1_x+20*cos(deg2rad($q1_angle)),$q1_y-20*sin(deg2rad($q1_angle)), $q1_x-20*cos(deg2rad($q1_angle)), $q1_y+20*sin(deg2rad($q1_angle)), $this->owner->selected );
		# imageline($im, $q3_x+20*cos(deg2rad($q3_angle)),$q3_y-20*sin(deg2rad($q3_angle)), $q3_x-20*cos(deg2rad($q3_angle)), $q3_y+20*sin(deg2rad($q3_angle)), $this->owner->selected );

		# warn("$q1_angle $q3_angle\n");

		if (!is_null($q1_x))
		{
			$outbound=array
				(
					$q1_x,
					$q1_y,
					0,
					0,
					$this->outpercent,
					$this->bandwidth_out,
					$q1_angle,
					OUT
				);

			$inbound=array
				(
					$q3_x,
					$q3_y,
					0,
					0,
					$this->inpercent,
					$this->bandwidth_in,
					$q3_angle,
					IN
				);

			if ($map->sizedebug)
			{
				$outbound[5]=$this->max_bandwidth_out;
				$inbound[5]=$this->max_bandwidth_in;
			}

			foreach (array($inbound, $outbound)as $task)
			{
				$thelabel="";

				if ($this->labelstyle != 'none')
				{
					debug("Bandwidth is ".$task[5]."\n");
					if ($this->labelstyle == 'bits') { $thelabel=nice_bandwidth($task[5], $this->owner->kilo); }
					elseif ($this->labelstyle == 'unformatted') { $thelabel=$task[5]; }
					elseif ($this->labelstyle == 'percent') { $thelabel=format_number($task[4]) . "%"; }

					$padding = intval($this->get_hint('bwlabel_padding'));		

					if($this->labelboxstyle == 'angled')
					{
						$map->DrawLabelRotated($im, $task[0],            $task[1],$task[6],           $thelabel, $this->bwfont, $padding,
							$this->name,  $this->bwfontcolour, $this->bwboxcolour, $this->bwoutlinecolour,$map, $task[7]);
					}
					else
					{
						$map->DrawLabel($im, $task[0],            $task[1],           $thelabel, $this->bwfont, $padding,
							$this->name,  $this->bwfontcolour, $this->bwboxcolour, $this->bwoutlinecolour,$map, $task[7]);
					}
					
				}
			}
		}
	}

	function WriteConfig()
	{
		$output='';

		if($this->config_override != '')
		{
			$output  = $this->config_override."\n";
		}
		else
		{
			$comparison=($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['infourl'] : $this->owner->defaultlink->infourl);
	
			if ($this->infourl != $comparison) { $output.="\tINFOURL " . $this->infourl . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['notestext'] : $this->owner->defaultlink->notestext);
	
			if ($this->notestext != $comparison) { $output.="\tNOTES " . $this->notestext . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overliburl'] : $this->owner->defaultlink->overliburl);
	
			if ($this->overliburl != $comparison) { $output.="\tOVERLIBGRAPH " . $this->overliburl . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibcaption'] : $this->owner->defaultlink->overlibcaption);
	
			if ($this->overlibcaption != $comparison) { $output.="\tOVERLIBCAPTION " . $this->overlibcaption . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibwidth'] : $this->owner->defaultlink->overlibwidth);
	
			if ($this->overlibwidth != $comparison) { $output.="\tOVERLIBWIDTH " . $this->overlibwidth . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['overlibheight'] : $this->owner->defaultlink->overlibheight);
	
			if ($this->overlibheight != $comparison) { $output.="\tOVERLIBHEIGHT " . $this->overlibheight . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['arrowstyle'] : $this->owner->defaultlink->arrowstyle);
	
			if ($this->arrowstyle != $comparison) { $output.="\tARROWSTYLE " . $this->arrowstyle . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? ($this->inherit_fieldlist['labelstyle']) : ($this->owner->defaultlink->labelstyle));
			if ($this->labelstyle != $comparison) { $output.="\tBWLABEL " . $this->labelstyle . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? ($this->inherit_fieldlist['labelboxstyle']) : ($this->owner->defaultlink->labelboxstyle));
			if ($this->labelboxstyle != $comparison) { $output.="\tBWSTYLE " . $this->labelboxstyle . "\n"; }
	
			$comparison = ($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['labeloffset_in'] : $this->owner->defaultlink->labeloffset_in);
			$comparison2 = ($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['labeloffset_out'] : $this->owner->defaultlink->labeloffset_out);
	
			if ( ($this->labeloffset_in != $comparison) || ($this->labeloffset_out != $comparison2) )
			{ $output.="\tBWLABELPOS " . $this->labeloffset_in . " " . $this->labeloffset_out . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? ($this->inherit_fieldlist['commentoffset_in'].":".$this->inherit_fieldlist['commentoffset_out']) : ($this->owner->defaultlink->commentoffset_in.":".$this->owner->defaultlink->commentoffset_out));
			$mine = $this->commentoffset_in.":".$this->commentoffset_out;
			if ($mine != $comparison) { $output.="\tCOMMENTPOS " . $this->commentoffset_in." ".$this->commentoffset_out. "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['targets'] : $this->owner->defaultlink->targets);
	
			if ($this->targets != $comparison)
			{
				$output.="\tTARGET";
	
				foreach ($this->targets as $target) { $output.=" " . $target[4]; }
	
				$output.="\n";
			}
	
			$comparison=($this->name == 'DEFAULT'
				? $this->inherit_fieldlist['usescale'] : $this->owner->defaultlink->usescale);
			if ($this->usescale != $comparison) { $output.="\tUSESCALE " . $this->usescale . "\n"; }
	
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['comments'][IN] : $this->owner->defaultlink->comments[IN]);
			if ($this->comments[IN] != $comparison) { $output.="\tINCOMMENT " . $this->comments[IN] . "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['comments'][OUT] : $this->owner->defaultlink->comments[OUT]);
			if ($this->comments[OUT] != $comparison) { $output.="\tOUTCOMMENT " . $this->comments[OUT] . "\n"; }
	
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['usescale'] : $this->owner->defaultlink->usescale);
			if ($this->usescale != $comparison) { $output.="\tUSESCALE " . $this->usescale . "\n"; }
	
	
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['bwfont'] : $this->owner->defaultlink->bwfont);
	
			if ($this->bwfont != $comparison) { $output.="\tBWFONT " . $this->bwfont . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['commentfont'] : $this->owner->defaultlink->commentfont);
	
			if ($this->commentfont != $comparison) { $output.="\tCOMMENTFONT " . $this->commentfont . "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['width'] : $this->owner->defaultlink->width);
	
			if ($this->width != $comparison) { $output.="\tWIDTH " . $this->width . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['outlinecolour'] : $this->owner->defaultlink->outlinecolour);
	
			if ($this->outlinecolour != $comparison) { $output.="\tOUTLINECOLOR " . render_colour(
				$this->outlinecolour)
				. "\n"; }
	
			$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['bwoutlinecolour']
			: $this->owner->defaultlink->bwoutlinecolour);
	
			if ($this->bwoutlinecolour != $comparison) { $output.="\tBWOUTLINECOLOR " . render_colour(
				$this->bwoutlinecolour)
				. "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['bwfontcolour'] : $this->owner->defaultlink->bwfontcolour);
	
			if ($this->bwfontcolour != $comparison) { $output.="\tBWFONTCOLOR " . render_colour(
				$this->bwfontcolour) . "\n"; }
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['commentfontcolour'] : $this->owner->defaultlink->commentfontcolour);
	
			if ($this->commentfontcolour != $comparison) { $output.="\tCOMMENTFONTCOLOR " . render_colour(
				$this->commentfontcolour) . "\n"; }
	
	
			$comparison=($this->name == 'DEFAULT'
			? $this->inherit_fieldlist['bwboxcolour'] : $this->owner->defaultlink->bwboxcolour);
	
			if ($this->bwboxcolour != $comparison) { $output.="\tBWBOXCOLOR " . render_colour(
				$this->bwboxcolour) . "\n"; }
	
			if (isset($this->a) && isset($this->b))
			{
				$output.="\tNODES " . $this->a->name;
	
				if ($this->a_offset != 'C')
					$output.=":" . $this->a_offset;
	
				$output.=" " . $this->b->name;
	
				if ($this->b_offset != 'C')
					$output.=":" . $this->b_offset;
	
				$output.="\n";
			}
	
			if (count($this->vialist) > 0)
			{
				foreach ($this->vialist as $via)
					$output.=sprintf("\tVIA %d %d\n", $via[0], $via[1]);
			}
	
			if (($this->max_bandwidth_in != $this->owner->defaultlink->max_bandwidth_in)
				|| ($this->max_bandwidth_out != $this->owner->defaultlink->max_bandwidth_out)
					|| ($this->name == 'DEFAULT'))
			{
				if ($this->max_bandwidth_in == $this->max_bandwidth_out)
				{ $output.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . "\n"; }
				else { $output
				.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n"; }
			}
	
	
			foreach ($this->hints as $hintname=>$hint)
			{
			  // all hints for DEFAULT node are for writing
			  // only changed ones, or unique ones, otherwise
			      if( 
			    ($this->name == 'DEFAULT')
			  ||
				    (isset($this->owner->defaultlink->hints[$hintname]) 
				    &&
				    $this->owner->defaultlink->hints[$hintname] != $hint)
				  ||
				    (!isset($this->owner->defaultlink->hints[$hintname]))
				)
			      {		      
			    $output .= "\tSET $hintname $hint\n";
			      }
			}
	
			if ($output != '')
			{
				$output = "LINK " . $this->name . "\n".$output."\n";
			}
		}
		return($output);
	}

	function asJS()
	{
		$js='';
		$js.="Links[" . js_escape($this->name) . "] = {";

		if ($this->name != 'DEFAULT')
		{
			$js.="a:'" . $this->a->name . "', ";
			$js.="b:'" . $this->b->name . "', ";
		}

		$js.="width:'" . $this->width . "', ";
		$js.="target:";

		$tgt='';

		foreach ($this->targets as $target) { $tgt.=$target[4] . ' '; }

		$js.=js_escape(trim($tgt));
		$js.=",";

		$js.="bw_in:" . js_escape($this->max_bandwidth_in_cfg) . ", ";
		$js.="bw_out:" . js_escape($this->max_bandwidth_out_cfg) . ", ";

		$js.="name:" . js_escape($this->name) . ", ";
		$js.="overlibwidth:'" . $this->overlibheight . "', ";
		$js.="overlibheight:'" . $this->overlibwidth . "', ";
		$js.="overlibcaption:" . js_escape($this->overlibcaption) . ", ";

		$js.="infourl:" . js_escape($this->infourl) . ", ";
		$js.="overliburl:" . js_escape($this->overliburl);
		$js.="};\n";
		return $js;
	}

	function asJSON($complete=TRUE)
	{
		$js='';
		$js.="" . js_escape($this->name) . ": {";

		if ($this->name != 'DEFAULT')
		{
			$js.="a:'" . $this->a->name . "', ";
			$js.="b:'" . $this->b->name . "', ";
		}

		if($complete)
		{
			$js.="infourl:" . js_escape($this->infourl) . ", ";
			$js.="overliburl:" . js_escape($this->overliburl). ", ";
			$js.="width:'" . $this->width . "', ";
			$js.="target:";
	
			$tgt='';
	
			foreach ($this->targets as $target) { $tgt.=$target[4] . ' '; }
	
			$js.=js_escape(trim($tgt));
			$js.=",";
	
			$js.="bw_in:" . js_escape($this->max_bandwidth_in_cfg) . ", ";
			$js.="bw_out:" . js_escape($this->max_bandwidth_out_cfg) . ", ";
	
			$js.="name:" . js_escape($this->name) . ", ";
			$js.="overlibwidth:'" . $this->overlibheight . "', ";
			$js.="overlibheight:'" . $this->overlibwidth . "', ";
			$js.="overlibcaption:" . js_escape($this->overlibcaption) . ", ";
		}
		$vias = "via: [";
		foreach ($this->vialist as $via)
				$vias .= sprintf("[%d,%d],", $via[0], $via[1]);
		$vias .= "],";
		$vias = str_replace("],]","]]",$vias);
		$js .= $vias;

		$js.="},\n";
		return $js;
	}
}

;

class WeatherMap extends WeatherMapBase
{
	var $nodes = array(); // an array of WeatherMapNodes
	var $links = array(); // an array of WeatherMapLinks
	var $texts = array(); // an array containing all the extraneous text bits
	var $used_images = array(); // an array of image filenames referred to (used by editor)
	var $background;
	var $htmlstyle;
	var $imap;
	var $colours;
	var $configfile;
	var $imagefile,
		$imageuri;
	var $rrdtool;
	var $title,
		$titlefont;
	var $kilo;
	var $sizedebug,
		$widthmod,
		$debugging;
	var $linkfont,
		$nodefont,
		$keyfont,
		$timefont;
	// var $bg_r, $bg_g, $bg_b;
	var $timex,
		$timey;
	var $width,
		$height;
	var $keyx,
		$keyy, $keyimage;
	var $titlex,
		$titley;
	var $keytext,
		$stamptext, $datestamp;
	var $htmloutputfile,
		$imageoutputfile;
	var $defaultlink,
		$defaultnode;
	var $need_size_precalc;
	var $keystyle,$keysize;
	var $rrdtool_check;
	var $inherit_fieldlist;
	var $context;
	var $cachefolder,$mapcache,$cachefile_version;
	var $name;
	var $black,
		$white,
		$grey,
		$selected;

	var $datasourceclasses;
	var $preprocessclasses;
	var $postprocessclasses;
	var $activedatasourceclasses;

	function WeatherMap()
	{
		$this->inherit_fieldlist=array
			(
				'width' => 800,
				'height' => 600,
				'kilo' => 1000,
				'numscales' => array('DEFAULT' => 0),
				'datasourceclasses' => array(),
				'preprocessclasses' => array(),
				'postprocessclasses' => array(),
				'context' => '',
				'dumpconfig' => FALSE,
				'rrdtool_check' => '',
				'background' => '',
				'imageoutputfile' => '',
				'htmloutputfile' => '',
				'labelstyle' => 'percent', // redundant?
				'htmlstyle' => 'static',
				'keystyle' => array('DEFAULT' => 'classic'),
				'title' => 'Network Weathermap',
				'keytext' => array('DEFAULT' => 'Traffic Load'),
				'keyx' => array('DEFAULT' => -1),
				'keyy' => array('DEFAULT' => -1),
				'keyimage' => array(),
				'keysize' => array('DEFAULT' => 400),
				'stamptext' => 'Created: %b %d %Y %H:%M:%S',
				'keyfont' => 4,
				'titlefont' => 2,
				'timefont' => 2,				
				'timex' => 0,
				'timey' => 0,
				'titlex' => -1,
				'titley' => -1,
				'cachefolder' => 'cached',
				'mapcache' => '',
				'sizedebug' => FALSE,
				'debugging' => FALSE,
				'widthmod' => FALSE,
				'name' => 'MAP'
			);

		$this->Reset();
	}

	function Reset()
	{
		foreach (array_keys($this->inherit_fieldlist)as $fld) { $this->$fld=$this->inherit_fieldlist[$fld]; }

		// these two are used for default settings
		$this->defaultlink=new WeatherMapLink;
		$this->defaultlink->name="DEFAULT";
		$this->defaultlink->Reset($this);

		$this->defaultnode=new WeatherMapNode;
		$this->defaultnode->name="DEFAULT";
		$this->defaultnode->Reset($this);

		$this->need_size_precalc=FALSE;

		$this->nodes=array
			(
				); // an array of WeatherMapNodes

		$this->links=array
			(
				); // an array of WeatherMapLinks

		$this->imap=new HTML_ImageMap('weathermap');
		$this->colours=array
			(
				);

		debug ("Adding default map colour set.\n");
		$defaults=array
			(
				'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0),
				'KEYBG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255),
				'BG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255),
				'TITLE' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0),
				'TIME' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0)
			);

		foreach ($defaults as $key => $def) { $this->colours['DEFAULT'][$key]=$def; }

		$this->configfile='';
		$this->imagefile='';
		$this->imageuri='';

		// $this->bg_r = 255;
		// $this->bg_g = 255;
		// $this->bg_b = 255;

		$this->fonts=array();

		// Adding these makes the editor's job a little easier, mainly
		for($i=1; $i<=5; $i++)
		{
			$this->fonts[$i]->type="GD builtin";
			$this->fonts[$i]->file='';
			$this->fonts[$i]->size=0;
		}

		$this->LoadPlugins('data', 'lib' . DIRECTORY_SEPARATOR . 'datasources');
		$this->LoadPlugins('pre', 'lib' . DIRECTORY_SEPARATOR . 'pre');
		$this->LoadPlugins('post', 'lib' . DIRECTORY_SEPARATOR . 'post');

		debug("WeatherMap class Reset() complete\n");
	}

	function myimagestring($image, $fontnumber, $x, $y, $string, $colour, $angle=0)
	{
		// if it's supposed to be a special font, and it hasn't been defined, then fall through
		if ($fontnumber > 5 && !isset($this->fonts[$fontnumber]))
		{
			warn ("Using a non-existent special font ($fontnumber) - falling back to internal GD fonts [WMWARN03]\n");
			if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
			$fontnumber=5;
		}

		if (($fontnumber > 0) && ($fontnumber < 6))
		{
			imagestring($image, $fontnumber, $x, $y - imagefontheight($fontnumber), $string, $colour);
			if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
		}
		else
		{
			// look up what font is defined for this slot number
			if ($this->fonts[$fontnumber]->type == 'truetype')
			{
				imagettftext($image, $this->fonts[$fontnumber]->size, $angle, $x, $y,
					$colour, $this->fonts[$fontnumber]->file, $string);
			}

			if ($this->fonts[$fontnumber]->type == 'gd')
			{
				imagestring($image, $this->fonts[$fontnumber]->gdnumber,
					$x,      $y - imagefontheight($this->fonts[$fontnumber]->gdnumber),
					$string, $colour);
				if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN04]\n");
			}
		}
	}

	function myimagestringsize($fontnumber, $string)
	{
		if (($fontnumber > 0) && ($fontnumber < 6))
		{ return array(imagefontwidth($fontnumber) * strlen($string), imagefontheight($fontnumber)); }
		else
		{
			// look up what font is defined for this slot number
			if (!isset($this->fonts[$fontnumber]))
			{
				warn ("Using a non-existent special font ($fontnumber) - falling back to internal GD fonts\n");
				$fontnumber=5;
				return array(imagefontwidth($fontnumber) * strlen($string), imagefontheight($fontnumber));
			}
			else
			{
				if ($this->fonts[$fontnumber]->type == 'truetype')
				{
					$bounds=imagettfbbox($this->fonts[$fontnumber]->size, 0, $this->fonts[$fontnumber]->file,
						$string);
					return (array($bounds[4] - $bounds[0], $bounds[1] - $bounds[5]));
				}

				if ($this->fonts[$fontnumber]->type == 'gd')
				{ return array(imagefontwidth($this->fonts[$fontnumber]->gdnumber) * strlen($string),
					imagefontheight($this->fonts[$fontnumber]->gdnumber)); }
			}
		}
	}

	function ProcessString($input,&$context, $include_notes=TRUE)
	{
		$output = $input;

		# debug("ProcessString: input is $input\n");

		# while( preg_match("/(\{[^}]+\})/",$input,$matches) )
		while( preg_match("/(\{(?:node|map|link)[^}]+\})/",$input,$matches) )
		{
			$value = "[UNKNOWN]";
			$format = "";
			$key = $matches[1];
		#	debug("ProcessString: working on ".$key."\n");

			if ( preg_match("/\{(node|map|link):([^}]+)\}/",$key,$matches) )
			{
				$type = $matches[1];
				$args = $matches[2];
		#		debug("ProcessString: type is ".$type.", arguments are ".$args."\n");

				if($type == 'map')
				{
					$the_item = $this;
					if(preg_match("/map:([^:]+):*([^:]*)/",$args,$matches))
					{
						$args = $matches[1];
						$format = $matches[2];
					}
				}

				if(($type == 'link') || ($type == 'node'))
				{
					if(preg_match("/([^:]+):([^:]+):*([^:]*)/",$args,$matches))
					{
						$itemname = $matches[1];
						$args = $matches[2];
						$format = $matches[3];

		#				debug("ProcessString: item is $itemname, and args are now $args\n");

						$the_item = NULL;
						if( ($itemname == "this") && ($type == strtolower($context->my_type())) )
						{
							$the_item = $context;
						}
						elseif( ($itemname == "parent") && ($type == strtolower($context->my_type())) && ($type=='node') && ($context->relative_to != '') )
						{
							$the_item = $this->nodes[$context->relative_to]; 
						}
						else
						{
							if( ($type == 'link') && isset($this->links[$itemname]) )
							{
								$the_item = $this->links[$itemname];
							}
							if( ($type == 'node') && isset($this->nodes[$itemname]) )
							{
								$the_item = $this->nodes[$itemname];
							}
						}
					}
				}

				if(is_null($the_item))
				{
					warn("ProcessString: $key refers to unknown item [WMWARN05]\n");
				}
				else
				{
				#	warn($the_item->name.": ".var_dump($the_item->hints)."\n");
					debug("ProcessString: Found appropriate item: ".get_class($the_item)." ".$the_item->name."\n");				
					
					# warn($the_item->name."/hints: ".var_dump($the_item->hints)."\n");
					# warn($the_item->name."/notes: ".var_dump($the_item->notes)."\n");
					
					// SET and notes have precedent over internal properties
					// this is my laziness - it saves me having a list of reserved words
					// which are currently used for internal props. You can just 'overwrite' any of them.
					if(isset($the_item->hints[$args]))
					{
						$value = $the_item->hints[$args];
						debug("ProcessString: used hint\n");
					}
					// for some things, we don't want to allow notes to be considered.
					// mainly - TARGET (which can define command-lines), shouldn't be
					// able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
					elseif($include_notes && isset($the_item->notes[$args]))
					{
						$value = $the_item->notes[$args];
						debug("ProcessString: used note\n");
						
					}					
					elseif(isset($the_item->$args))
					{
						$value = $the_item->$args;
						debug("ProcessString: used internal property\n");
					}				
				}
			}

			// format, and sanitise the value string here, before returning it

			debug("ProcessString: replacing ".$key." with $value\n");

			# if($format != '') $value = sprintf($format,$value);
			if($format != '') 
			{

		#		debug("Formatting with mysprintf($format,$value)\n");
				$value = mysprintf($format,$value);
			}

		#	debug("ProcessString: formatted to $value\n");
			$input = str_replace($key,'',$input);
			$output = str_replace($key,$value,$output);
		}
		#debug("ProcessString: output is $output\n");
		return ($output);
}

function RandomData()
{
	foreach ($this->links as $link)
	{
		$this->links[$link->name]->bandwidth_in=rand(0, $link->max_bandwidth_in);
		$this->links[$link->name]->bandwidth_out=rand(0, $link->max_bandwidth_out);
	}
}

function LoadPlugins( $type="data", $dir="lib/datasources" )
{
	debug("Beginning to load $type plugins from $dir\n");
	# $this->datasourceclasses = array();
	$dh=@opendir($dir);

	if(!$dh) {	// try to find it with the script, if the relative path fails
		$srcdir = substr($_SERVER['argv'][0], 0, strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
		$dh = opendir($srcdir.DIRECTORY_SEPARATOR.$dir);
		if ($dh) $dir = $srcdir.DIRECTORY_SEPARATOR.$dir;
	}

	if ($dh)
	{
		while ($file=readdir($dh))
		{
			$realfile = $dir . DIRECTORY_SEPARATOR . $file;

			if( is_file($realfile) && preg_match( '/\.php$/', $realfile ) )
			{
				debug("Loading $type Plugin class from $file\n");

				include_once( $realfile );
				$class = preg_replace( "/\.php$/", "", $file );
				if($type == 'data') 
				{
					$this->datasourceclasses [$class]= $class;
					$this->activedatasourceclasses[$class]=1;
				}
				if($type == 'pre') $this->preprocessclasses [$class]= $class;
				if($type == 'post') $this->postprocessclasses [$class]= $class;

				debug("Loaded $type Plugin class $class from $file\n");
			}
			else
			{
				debug("Skipping $file\n");
			}
		}
	}
	else
	{
		warn("Couldn't open $type Plugin directory ($dir). Things will probably go wrong. [WMWARN06]\n");
	}
}

function ReadData()
{

	debug("Running Init() for Data Source Plugins...\n");
	foreach ($this->datasourceclasses as $ds_class)
	{
		debug("Running $ds_class"."->Init()\n");
		$ret = call_user_func(array($ds_class, 'Init'), $this);
		if(! $ret)
		{   
			debug("Removing $ds_class from Data Source list, since Init() failed\n");
			$this->activedatasourceclasses[$ds_class]=0;
			# unset($this->datasourceclasses[$ds_class]);
		}
	}
	debug("Finished Initialising Plugins...\n");

	debug ("================== ReadData: Updating link data for all links and nodes\n");

	if ($this->sizedebug == 0)
	{

		$allitems = array(&$this->links, &$this->nodes);
		reset($allitems);

		while( list($kk,) = each($allitems))
		{ 
			unset($objects);
			# $objects = &$this->links;
			$objects = &$allitems[$kk];

			reset($objects); 
			while (list($k,) = each($objects))
			{
				unset($myobj);
				$myobj = &$objects[$k];

				$type = $myobj->my_type();
				
				$total_in=0;
				$total_out=0;
				$name=$myobj->name;
				debug ("\n\nReadData for $type $name: \n");

				if (count($myobj->targets)>0)
				{
					foreach ($myobj->targets as $target)
					{
						debug ("ReadData: New Target: $target[0]\n");
						
						

						$in = 0;
						$out = 0;
						if ($target[4] != '')
						{
							// processstring won't use notes (only hints) for this string
							$targetstring = $this->ProcessString($target[4], $myobj, FALSE);
							if($target[4] != $targetstring) debug("Targetstring is now $targetstring\n");
						
							// if the targetstring starts with a -, then we're taking this value OFF the aggregate
							$multiply = 1;
							if(preg_match("/^-(.*)/",$target[4],$matches))
							{
								$targetstring = $matches[1];
								$multiply = -1;
							}
						
							$matched = FALSE;
							$matched_by = '';
							foreach ($this->datasourceclasses as $ds_class)
							{
								if(!$matched)
								{
									$recognised = call_user_func(array($ds_class, 'Recognise'), $targetstring);
									
									if( $recognised )
									{
										if($this->activedatasourceclasses[$ds_class])
										{
											debug("ReadData: Matched for $ds_class. Calling ${ds_class}->ReadData()\n");

											// line number is in $target[3]
											# list($in,$out,$datatime) =  call_user_func( array($ds_class, 'ReadData'), $targetstring, $this, $myobj );
											list($in,$out,$datatime) =  call_user_func_array( array($ds_class, 'ReadData'), array($targetstring, &$this, &$myobj));
										}
										else
										{
											warn("ReadData: $type $name, target: $targetstring on config line $target[3] was recognised as a valid TARGET by a plugin that is unable to run ($ds_class) [WMWARN07]\n");
										}
										$matched = TRUE;
										$matched_by = $ds_class;
									}
								}
							}

							if(! $matched)
							{
								// **
								warn("ReadData: $type $name, target: $target[4] on config line $target[3] was not recognised as a valid TARGET [WMWARN08]\n");
							}

							if (($in < 0) || ($out < 0))
							{
								$in=0;
								$out=0;
								// **
								warn
									("ReadData: $type $name, target: $targetstring on config line $target[3] had no valid data, according to $matched_by\n");
							}

							$total_in=$total_in + $multiply*$in;
							$total_out=$total_out + $multiply*$out;
						}
					}
				}
				else
				{
					debug("ReadData: No targets for $type $name\n");
				}

				# $this->links[$name]->bandwidth_in=$total_in;
				# $this->links[$name]->bandwidth_out=$total_out;
				$myobj->bandwidth_in = $total_in;
				$myobj->bandwidth_out = $total_out;
				
				$myobj->outpercent = (($total_out) / ($myobj->max_bandwidth_out)) * 100;
				$myobj->inpercent = (($total_in) / ($myobj->max_bandwidth_in)) * 100;		
			
				list($incol,$inscalekey) = $this->ColourFromPercent(NULL, $myobj->inpercent,$myobj->usescale,$myobj->name);
				list($outcol,$outscalekey) = $this->ColourFromPercent(NULL, $myobj->outpercent,$myobj->usescale,$myobj->name);
				
				// $myobj->incolour = $incol;
				$myobj->inscalekey = $inscalekey;
				// $myobj->outcolour = $outcol;
				$myobj->outscalekey = $outscalekey;
				
				debug ("ReadData: Setting $total_in,$total_out\n");
				unset($myobj);
			}
		}
		debug ("\nReadData Completed.\n--------------\n");		
	}
}

// nodename is a vestigal parameter, from the days when nodes where just big labels
function DrawLabel($im, $x, $y, $text, $font, $padding, $linkname, $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
{
	list($strwidth, $strheight)=$this->myimagestringsize($font, $text);

	$extra=3;

	$x1=$x - ($strwidth / 2) - $padding - $extra;
	$x2=$x + ($strwidth / 2) + $padding + $extra;
	$y1=$y - ($strheight / 2) - $padding - $extra;
	$y2=$y + ($strheight / 2) + $padding + $extra;

	if ($bgcolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$bgcol=myimagecolorallocate($im, $bgcolour[0], $bgcolour[1], $bgcolour[2]);
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgcol);
	}

	if ($outlinecolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$outlinecol=myimagecolorallocate($im, $outlinecolour[0], $outlinecolour[1], $outlinecolour[2]);
		imagerectangle($im, $x1, $y1, $x2, $y2, $outlinecol);
	}

	$textcol=myimagecolorallocate($im, $textcolour[0], $textcolour[1], $textcolour[2]);
	$this->myimagestring($im, $font, $x - $strwidth / 2, $y + $strheight / 2 + 1, $text, $textcol);

	$this->imap->addArea("Rectangle", "LINK:".$linkname.':'.($direction+2), '', array($x1, $y1, $x2, $y2));

}

// nodename is a vestigal parameter, from the days when nodes where just big labels
function DrawLabelRotated($im, $x, $y, $angle, $text, $font, $padding, $linkname, $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
{
	list($strwidth, $strheight)=$this->myimagestringsize($font, $text);

	if(abs($angle)>90)
	{
		$angle -= 180;
		if($angle < -180) $angle +=360;
	}

	$rangle = -deg2rad($angle);

	$extra=3;

	$x1= $x - ($strwidth / 2) - $padding - $extra;
	$x2= $x + ($strwidth / 2) + $padding + $extra;
	$y1= $y - ($strheight / 2) - $padding - $extra;
	$y2= $y + ($strheight / 2) + $padding + $extra;
	
	// a box. the last point is the start point for the text.
	$points = array($x1,$y1, $x1,$y2, $x2,$y2, $x2,$y1,   $x-$strwidth/2, $y+$strheight/2 + 1);
	$npoints = count($points)/2;
		
	RotateAboutPoint($points, $x,$y, $rangle);
	
	if ($bgcolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$bgcol=myimagecolorallocate($im, $bgcolour[0], $bgcolour[1], $bgcolour[2]);
		# imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgcol);
		imagefilledpolygon($im,$points,4,$bgcol);
	}

	if ($outlinecolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$outlinecol=myimagecolorallocate($im, $outlinecolour[0], $outlinecolour[1], $outlinecolour[2]);
		# imagerectangle($im, $x1, $y1, $x2, $y2, $outlinecol);
		imagepolygon($im,$points,4,$outlinecol);
	}

	$textcol=myimagecolorallocate($im, $textcolour[0], $textcolour[1], $textcolour[2]);
	$this->myimagestring($im, $font, $points[8], $points[9], $text, $textcol,$angle);

	$areaname = "LINK:".$linkname.':'.($direction+2);
	$map->imap->addArea("Polygon", $areaname, '', $points);
	debug ("Adding Poly imagemap for $areaname\n");

}

function ColourFromPercent($image, $percent,$scalename="DEFAULT",$name="")
{
	$col = NULL;
	
	if(isset($this->colours[$scalename]))
	{
		$colours=$this->colours[$scalename];

		if ($percent > 100)
		{
			warn ("ColourFromPercent: Clipped $name $percent% to 100%\n");
			$percent=100;
		}

		foreach ($colours as $key => $colour)
		{
			if (($percent >= $colour['bottom']) and ($percent <= $colour['top']))
			{
				// we get called early now, so might not need to actually allocate a colour
				if(isset($image))
				{
					if (isset($colour['red2']))
					{
						if($colour["bottom"] == $colour["top"])
						{
							$ratio = 0;
						}
						else
						{
							$ratio=($percent - $colour["bottom"]) / ($colour["top"] - $colour["bottom"]);
						}
	
						$r=$colour["red1"] + ($colour["red2"] - $colour["red1"]) * $ratio;
						$g=$colour["green1"] + ($colour["green2"] - $colour["green1"]) * $ratio;
						$b=$colour["blue1"] + ($colour["blue2"] - $colour["blue1"]) * $ratio;
	
						$col = myimagecolorallocate($image, $r, $g, $b);
					}
					else {
						$r=$colour["red1"];
						$g=$colour["green1"];
						$b=$colour["blue1"];
	
						$col = myimagecolorallocate($image, $r, $g, $b);
						# $col = $colour['gdref1'];
					}
				}
				
				return(array($col,$key));
			}
		}
	}
	else
	{
		if($scalename != 'none')
		{
			warn("ColourFromPercent: Attempted to use non-existent scale: $scalename for $name [WMWARN09]\n");
		}
	}

	// you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
	if ($percent == 0) { return array($this->grey,''); }

	// and you'll only get white for a link with no colour assigned
	return array($this->white,'');
}


function NewColourFromPercent($percent,$scalename="DEFAULT",$name="")
{
	$col = new Colour(0,0,0);
	
	if(isset($this->colours[$scalename]))
	{
		$colours=$this->colours[$scalename];

		if ($percent > 100)
		{
			warn ("NewColourFromPercent: Clipped $name $percent% to 100%\n");
			$percent=100;
		}

		foreach ($colours as $key => $colour)
		{
			if (($percent >= $colour['bottom']) and ($percent <= $colour['top']))
			{
				if (isset($colour['red2']))
				{
					if($colour["bottom"] == $colour["top"])
					{
						$ratio = 0;
					}
					else
					{
						$ratio=($percent - $colour["bottom"]) / ($colour["top"] - $colour["bottom"]);
					}

					$r=$colour["red1"] + ($colour["red2"] - $colour["red1"]) * $ratio;
					$g=$colour["green1"] + ($colour["green2"] - $colour["green1"]) * $ratio;
					$b=$colour["blue1"] + ($colour["blue2"] - $colour["blue1"]) * $ratio;
				}
				else {
					$r=$colour["red1"];
					$g=$colour["green1"];
					$b=$colour["blue1"];

					# $col = new Colour($r, $g, $b);
					# $col = $colour['gdref1'];
				}
				$col = new Colour($r, $g, $b);
								
				return(array($col,$key));
			}
		}
	}
	else
	{
		if($scalename != 'none')
		{
			warn("ColourFromPercent: Attempted to use non-existent scale: $scalename for $name [WMWARN09]\n");
		}
	}

	// you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
	if ($percent == 0) { return array(new Colour(192,255,192),''); }

	// and you'll only get white for a link with no colour assigned
	return array(new Colour(255,255,255),'');
}


function coloursort($a, $b)
{
	if ($a['bottom'] == $b['bottom'])
	{
		if($a['top'] < $b['top']) { return -1; };
		if($a['top'] > $b['top']) { return 1; };
		return 0;
	}

	if ($a['bottom'] < $b['bottom']) { return -1; }

	return 1;
} 

function DrawLegend_Horizontal($im,$scalename="DEFAULT",$width=400)
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$font=$this->keyfont;

	# $x=$this->keyx[$scalename];
	# $y=$this->keyy[$scalename];
	$x = 0;
	$y = 0;

	# $width = 400;
	$scalefactor = $width/100;

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "100%");
	$box_left = $x;
	# $box_left = 0;
	$scale_left = $box_left + 4 + $scalefactor/2;
	$box_right = $scale_left + $width + $tilewidth + 4 + $scalefactor/2;
	$scale_right = $scale_left + $width;

	$box_top = $y;
	# $box_top = 0;
	$scale_top = $box_top + $tileheight + 6;
	$scale_bottom = $scale_top + $tileheight * 1.5;
	$box_bottom = $scale_bottom + $tileheight * 2 + 6;

	$scale_im = imagecreatetruecolor($box_right+1, $box_bottom+1);
	$scale_ref = 'gdref_legend_'.$scalename;
	$this->AllocateScaleColours($scale_im,$scale_ref);

	imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYBG'][$scale_ref]);
	imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

	$this->myimagestring($scale_im, $font, $scale_left, $scale_bottom + $tileheight * 2 + 2 , $title,
		$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

	for($p=0;$p<=100;$p++)
	{
		$dx = $p*$scalefactor;

		if( ($p % 25) == 0)
		{
			imageline($scale_im, $scale_left + $dx, $scale_top - $tileheight,
				$scale_left + $dx, $scale_bottom + $tileheight,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
			$labelstring=sprintf("%d%%", $p);
			$this->myimagestring($scale_im, $font, $scale_left + $dx + 2, $scale_top - 2, $labelstring,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
		}

		list($col,$junk) = $this->ColourFromPercent($scale_im, $p,$scalename);
		imagefilledrectangle($scale_im, $scale_left + $dx - $scalefactor/2, $scale_top,
			$scale_left + $dx + $scalefactor/2, $scale_bottom,
			$col);
	}

	imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
	$this->keyimage[$scalename] = $scale_im;

	$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
		array($box_left, $box_top, $box_right, $box_bottom));
}

function DrawLegend_Vertical($im,$scalename="DEFAULT",$height=400)
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$font=$this->keyfont;

	$x=$this->keyx[$scalename];
	$y=$this->keyy[$scalename];

	# $height = 400;
	$scalefactor = $height/100;

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "100%");

	# $box_left = $x;
	# $box_top = $y;
	$box_left = 0;
	$box_top = 0;

	$scale_left = $box_left+$scalefactor*2 +4 ;
	$scale_right = $scale_left + $tileheight*2;
	$box_right = $scale_right + $tilewidth + $scalefactor*2 + 4;

	list($titlewidth,$titleheight) = $this->myimagestringsize($font,$title);
	if( ($box_left + $titlewidth + $scalefactor*3) > $box_right)
	{
		$box_right = $box_left + $scalefactor*4 + $titlewidth;
	}

	$scale_top = $box_top + 4 + $scalefactor + $tileheight*2;
	$scale_bottom = $scale_top + $height;
	$box_bottom = $scale_bottom + $scalefactor + $tileheight/2 + 4;

	$scale_im = imagecreatetruecolor($box_right+1, $box_bottom+1);
	$scale_ref = 'gdref_legend_'.$scalename;
	$this->AllocateScaleColours($scale_im,$scale_ref);

	imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYBG']['gdref1']);
	imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYTEXT']['gdref1']);

	$this->myimagestring($scale_im, $font, $scale_left-$scalefactor, $scale_top - $tileheight , $title,
		$this->colours['DEFAULT']['KEYTEXT']['gdref1']);

	for($p=0;$p<=100;$p++)
	{
		$dy = $p*$scalefactor;
		$dx = $dy;

		if( ($p % 25) == 0)
		{
			imageline($scale_im, $scale_left - $scalefactor, $scale_top + $dy,
				$scale_right + $scalefactor, $scale_top + $dy,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
			$labelstring=sprintf("%d%%", $p);
			$this->myimagestring($scale_im, $font, $scale_right + $scalefactor*2 , $scale_top + $dy + $tileheight/2,
				$labelstring,  $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
		}

		list($col,$junk) = $this->ColourFromPercent($scale_im, $p,$scalename);
		imagefilledrectangle($scale_im, $scale_left, $scale_top + $dy - $scalefactor/2,
			$scale_right, $scale_top + $dy + $scalefactor/2,
			$col);
	}

	imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
	$this->keyimage[$scalename] = $scale_im;

	$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
		array($box_left, $box_top, $box_right, $box_bottom));
}

function DrawLegend_Classic($im,$scalename="DEFAULT")
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$hide_zero = intval($this->get_hint("key_hidezero_".$scalename));
	$hide_percent = intval($this->get_hint("key_hidepercent_".$scalename));

	// did we actually hide anything?
	$hid_zero = FALSE;
	if( ($hide_zero == 1) && isset($colours['0_0']) )
	{
		$nscales--;
		$hid_zero = TRUE;
	}

	$font=$this->keyfont;

	$x=$this->keyx[$scalename];
	$y=$this->keyy[$scalename];

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "MMMM");
	$tileheight=$tileheight * 1.1;
	$tilespacing=$tileheight + 2;

	if (($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0))
	{

		# $minwidth = imagefontwidth($font) * strlen('XX 100%-100%')+10;
		# $boxwidth = imagefontwidth($font) * strlen($title) + 10;
		list($minwidth, $junk)=$this->myimagestringsize($font, 'MMMM 100%-100%');
		list($boxwidth, $junk)=$this->myimagestringsize($font, $title);

		$minwidth+=10;
		$boxwidth+=10;

		if ($boxwidth < $minwidth) { $boxwidth=$minwidth; }

		$boxheight=$tilespacing * ($nscales + 1) + 10;

		$boxx=$x; $boxy=$y;
		$boxx=0;
		$boxy=0;
		
		// allow for X11-style negative positioning
		if ($boxx < 0) { $boxx+=$this->width; }

		if ($boxy < 0) { $boxy+=$this->height; }

		$scale_im = imagecreatetruecolor($boxwidth+1, $boxheight+1);
		$scale_ref = 'gdref_legend_'.$scalename;
		$this->AllocateScaleColours($scale_im,$scale_ref);

		imagefilledrectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight,
			$this->colours['DEFAULT']['KEYBG'][$scale_ref]);
		imagerectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight,
			$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
		$this->myimagestring($scale_im, $font, $boxx + 4, $boxy + 4 + $tileheight, $title,
			$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

		usort($colours, array("Weathermap", "coloursort"));

		$i=1;

		foreach ($colours as $colour)
		{
			if ($colour['bottom'] >= 0)  
			{					
				#  debug("$i: drawing\n");
				if( ($hide_zero == 0) || $colour['key'] != '0_0')	
				{
					$y=$boxy + $tilespacing * $i + 8;
					$x=$boxx + 6;
	
					$fudgefactor = 0;
					if( $hid_zero && $colour['bottom']==0 )
					{
						// calculate a small offset that can be added, which will hide the zero-value in a
						// gradient, but not make the scale incorrect. A quarter of a pixel should do it.
						$fudgefactor = ($colour['top'] - $colour['bottom'])/($tilewidth*4);
						# warn("FUDGING $fudgefactor\n");
					}					
	
					if (isset($colour['red2']))
					{
						for ($n=0; $n <= $tilewidth; $n++)
						{
							$percent
								=  $fudgefactor + $colour['bottom'] + ($n / $tilewidth) * ($colour['top'] - $colour['bottom']);
							list($col,$junk) = $this->ColourFromPercent($scale_im, $percent,$scalename);
							imagefilledrectangle($scale_im, $x + $n, $y, $x + $n, $y + $tileheight,
								$col);
						}
					}
					else
					{
						// pick a percentage in the middle...
						$percent=($colour['bottom'] + $colour['top']) / 2;
						list($col,$junk) = $this->ColourFromPercent($scale_im, $percent,$scalename);
						imagefilledrectangle($scale_im, $x, $y, $x + $tilewidth, $y + $tileheight,
							$col);
					}
	
					$labelstring=sprintf("%s-%s", $colour['bottom'], $colour['top']);
					if($hide_percent==0) { $labelstring.="%"; }
					$this->myimagestring($scale_im, $font, $x + 4 + $tilewidth, $y + $tileheight, $labelstring,
						$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
					$i++;
				}
				imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
				$this->keyimage[$scalename] = $scale_im;

			}
		}

		$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
			array($this->keyx[$scalename], $this->keyy[$scalename], $this->keyx[$scalename] + $boxwidth, $this->keyy[$scalename] + $boxheight));
		# $this->imap->setProp("href","#","LEGEND");
		# $this->imap->setProp("extrahtml","onclick=\"position_legend();\"","LEGEND");

	}
}

function DrawTimestamp($im, $font, $colour)
{
	// add a timestamp to the corner, so we can tell if it's all being updated
	# $datestring = "Created: ".date("M d Y H:i:s",time());
	# $this->datestamp=strftime($this->stamptext, time());

	list($boxwidth, $boxheight)=$this->myimagestringsize($font, $this->datestamp);

	$x=$this->width - $boxwidth;
	$y=$boxheight;

	if (($this->timex > 0) && ($this->timey > 0))
	{
		$x=$this->timex;
		$y=$this->timey;
	}

	$this->myimagestring($im, $font, $x, $y, $this->datestamp, $colour);

	$this->imap->addArea("Rectangle", "TIMESTAMP", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
}

function DrawTitle($im, $font, $colour)
{   
	$string = $this->ProcessString($this->title,$this);

	list($boxwidth, $boxheight)=$this->myimagestringsize($font, $string);

	$x=10;
	$y=$this->titley - $boxheight;

	if (($this->titlex >= 0) && ($this->titley >= 0))
	{
		$x=$this->titlex;
		$y=$this->titley;
	}

	$this->myimagestring($im, $font, $x, $y, $string, $colour);

	$this->imap->addArea("Rectangle", "TITLE", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
}

function ReadConfig($filename)
{
	$curnode=null;
	$curlink=null;
	$matches=0;
	$nodesseen=0;
	$linksseen=0;
	$scalesseen=0;

	$last_seen="---";
	$fd=fopen($filename, "r");

	if ($fd)
	{
		$linecount = 0;

		while (!feof($fd))
		{
			$buffer=fgets($fd, 4096);
			// strip out any Windows line-endings that have gotten in here
			$buffer=str_replace("\r", "", $buffer);
			$linematched=0;
			$linecount++;

			if (preg_match("/^\s*#/", $buffer)) {
				// this is a comment line
			}
			else
			{
				// for any other config elements that are shared between nodes and links, they can use this
				unset($curobj);
				$curobj = NULL;
				if($last_seen == "LINK") $curobj = &$curlink;
				if($last_seen == "NODE") $curobj = &$curnode;

				if (preg_match("/^\s*(LINK|NODE)\s+(\S+)\s*$/i", $buffer, $matches))
				{
					// first, save the previous item, before starting work on the new one
					if ($last_seen == "NODE")
					{
						if ($curnode->name == 'DEFAULT')
						{
							$this->defaultnode = $curnode;					
							debug ("Saving Default Node: " . $curnode->name . "\n");
						}
						else
						{
							$this->nodes[$curnode->name]=$curnode;
							debug ("Saving Node: " . $curnode->name . "\n");
						}
					}

					if ($last_seen == "LINK")
					{
						if ($curlink->name == 'DEFAULT')
						{
							$this->defaultlink=$curlink;
							debug ("Saving Default Link: " . $curlink->name . "\n");
						}
						else
						{
							if (isset($curlink->a) && isset($curlink->b))
							{
								$this->links[$curlink->name]=$curlink;
								debug ("Saving Link: " . $curlink->name . "\n");
							}
							else { warn
								("Dropping LINK " . $curlink->name . " - it hasn't got 2 NODES! [WMWARN28]\n"); }
						}
					}

					if ($matches[1] == 'LINK')
					{
						if ($matches[2] == 'DEFAULT')
						{
							if ($linksseen > 0) { warn
								("LINK DEFAULT is not the first LINK. Defaults will not apply to earlier LINKs. [WMWARN26]\n");
							}
							unset($curlink);
							$curlink = $this->defaultlink;
						}
						else
						{
							unset($curlink);
							
							if(isset($this->links[$matches[2]]))
							{
								warn("Duplicate link name ".$matches[2]." at line $linecount - only the last one defined is used. [WMWARN25]\n");
							}
							
							$curlink=new WeatherMapLink;
							$curlink->name=$matches[2];
							$curlink->Reset($this);
							$linksseen++;
						}

						$last_seen="LINK";
						$curlink->configline = $linecount;
						$linematched++;
					}

					if ($matches[1] == 'NODE')
					{
						if ($matches[2] == 'DEFAULT')
						{
							if ($nodesseen > 0) { warn
								("NODE DEFAULT is not the first NODE. Defaults will not apply to earlier NODEs. [WMWARN27]\n");
							}

							unset($curnode);
							$curnode = $this->defaultnode;
						}
						else
						{
							unset($curnode);
							
							if(isset($this->nodes[$matches[2]]))
							{
								warn("Duplicate node name ".$matches[2]." at line $linecount - only the last one defined is used. [WMWARN24]\n");
							}
							
							$curnode=new WeatherMapNode;
							$curnode->name=$matches[2];
							$curnode->Reset($this);
							$nodesseen++;
						}

						$curnode->configline = $linecount;
						$last_seen="NODE";
						$linematched++;
					}
				}



				if (preg_match("/^\s*POSITION\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'NODE')
					{
						$curnode->x=$matches[1];
						$curnode->y=$matches[2];
						$linematched++;
					}
				}

				if (preg_match("/^\s*POSITION\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'NODE')
					{
						$curnode->relative_to = $matches[1];
						$curnode->relative_resolved = FALSE;
						$curnode->x = $matches[2];
						$curnode->y = $matches[3];
						$curnode->original_x = $matches[2];
						$curnode->original_y = $matches[3];
						$linematched++;
					}
				}

				if (preg_match("/^\s*LABEL\s+(.*)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'NODE')
					{
						$curnode->label=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*NODES\s+(\S+)\s+(\S+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'LINK')
					{
						$valid_nodes=2;

						foreach (array(1, 2)as $i)
						{
							$endoffset[$i]='C';
							$nodenames[$i]=$matches[$i];

							if (preg_match("/:(NE|SE|NW|SW|N|S|E|W)$/i", $matches[$i], $submatches))
							{
								$endoffset[$i]=$submatches[1];
								$nodenames[$i]=preg_replace("/:(NE|SE|NW|SW|N|S|E|W)$/i", '', $matches[$i]);
								$this->need_size_precalc=TRUE;
							}

							if (preg_match("/:([-+]?\d+):([-+]?\d+)$/i", $matches[$i], $submatches))
							{
								$xoff = $submatches[1];
								$yoff = $submatches[2];
								$endoffset[$i]=$xoff.":".$yoff;
								$nodenames[$i]=preg_replace("/:$xoff:$yoff$/i", '', $matches[$i]);
								$this->need_size_precalc=TRUE;
							}

							if (!array_key_exists($nodenames[$i], $this->nodes))
							{
								warn ("Unknown node '" . $nodenames[$i]
									. "' on line $linecount of config\n");
								$valid_nodes--;
							}
						}

						// TODO - really, this should kill the whole link, and reset for the next one
						if ($valid_nodes == 2)
						{
							$curlink->a=$this->nodes[$nodenames[1]];
							$curlink->b=$this->nodes[$nodenames[2]];
							$curlink->a_offset=$endoffset[1];
							$curlink->b_offset=$endoffset[2];
						}
						else {
							// this'll stop the current link being added
							$last_seen="broken"; }

							$linematched++;
					}
				}

				if (preg_match("/^\s*TARGET\s+(.*)\s*$/i", $buffer, $matches))
				{
					$linematched++;

					$targets=preg_split('/\s+/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
					// wipe any existing targets, otherwise things in the DEFAULT accumulate with the new ones
					$curobj->targets = array();
					foreach ($targets as $target)
					{
						// we store the original TARGET string, and line number, along with the breakdown, to make nicer error messages later
						$newtarget=array($target,'','',$linecount,$target);
						if ($curobj)
						{
							$curobj->targets[]=$newtarget;
						}
					}
				}

				if (preg_match("/^\s*WIDTH\s+(\d+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'LINK')
					{
						$curlink->width=$matches[1];
						$linematched++;
					}
					else // we're talking about the global WIDTH
					{
						$this->width=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*HEIGHT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->height=$matches[1];
					$linematched++;
				}

				if ( ($last_seen == 'LINK') && (preg_match("/^\s*INCOMMENT\s+(.*)\s*$/i", $buffer, $matches)))
				{
					# $curlink->incomment = $matches[1];
					$curlink->comments[IN] = $matches[1];
					$linematched++;
				}

				if ( ($last_seen == 'LINK') && (preg_match("/^\s*OUTCOMMENT\s+(.*)\s*$/i", $buffer, $matches)))
				{
					# $curlink->outcomment = $matches[1];
					$curlink->comments[OUT] = $matches[1];
					$linematched++;
				}


				if ( ($last_seen == 'LINK') && (preg_match("/^\s*(BANDWIDTH|MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i", $buffer, $matches)))
				{
					$curlink->SetBandwidth($matches[2], $matches[2]);
					$linematched++;
				}
				
				if ( ($last_seen == 'LINK') && (preg_match("/^\s*(MAXVALUE|BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i", $buffer,
					$matches)))
				{
					$curlink->SetBandwidth($matches[2], $matches[3]);
					$linematched++;
				}

				if ( ($last_seen == 'NODE') && (preg_match("/^\s*MAXVALUE\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i", $buffer,
					$matches)))
				{
					$curnode->SetBandwidth($matches[1], $matches[2]);
					$linematched++;
				}

				if ( ($last_seen == 'NODE') && (preg_match("/^\s*MAXVALUE\s+(\d+\.?\d*[KMGT]?)\s*$/i", $buffer,
					$matches)))
				{
					$curnode->SetBandwidth($matches[1], $matches[1]);
					$linematched++;
				}

				if (preg_match("/^\s*ICON\s+(\S+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'NODE')
					{
						if($matches[1]=='none')
						{
							$curnode->iconfile='';
						}
						else
						{
							$curnode->iconfile=$matches[1];
							$this->used_images[] = $matches[1];
						}
						$linematched++;
					}
				}

				if (preg_match("/^\s*ICON\s+(\d+)\s+(\d+)\s+(\S+)\s*$/i", $buffer, $matches))
				{
					if ($last_seen == 'NODE')
					{
						// allow some special names - these produce "artificial" icons
						if($matches[3]=='nink' || $matches[3]=='box' || $matches[3]=='round' || $matches[3]=='inpie' || $matches[3]=='outpie' )
						{
							// special icons aren't added to used_images, so they won't appear in picklist for editor
							// (the editor doesn't do icon scaling, and these *require* a scale)
							$curnode->iconfile=$matches[3];
							$this->used_images[] = $matches[3];						
						}
						else
						{						
							$curnode->iconfile=$matches[3];
							$this->used_images[] = $matches[3];						
						}
						$curnode->iconscalew = $matches[1];
						$curnode->iconscaleh = $matches[2];
						$linematched++;
					}
				}

				if (preg_match("/^\s*SET\s+(\S+)\s+(.*)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						// THIS IS NOT UPDATING THE 'REAL' DEFAULT NODE
						$curobj->add_hint($matches[1],$matches[2]);
						$linematched++;
						# warn("POST-SET ".$curobj->name."::".var_dump($curobj->hints)."::\n");
						# warn("DEFAULT FIRST SAYS ".$this->defaultnode->hints['sigdigits']."\n");
					}
					else
					{
						// it's a global thing, for the map
						$this->add_hint($matches[1],$matches[2]);
						$linematched++;
					}
				}

				if (preg_match("/^\s*NOTES\s+(.*)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->notestext=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*INFOURL\s+(\S+)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->infourl=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*OVERLIBGRAPH\s+(\S+)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->overliburl=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*OVERLIBCAPTION\s+(.+)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->overlibcaption=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*OVERLIBHEIGHT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->overlibheight=$matches[1];
						$linematched++;
					}
				}

				if (preg_match("/^\s*OVERLIBWIDTH\s+(\d+)\s*$/i", $buffer, $matches))
				{
					if($curobj)
					{
						$curobj->overlibwidth=$matches[1];
						$linematched++;
					}
				}

				if ($last_seen == 'NODE' && preg_match("/^\s*LABELFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$curnode->labelfont=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'NODE' && preg_match(
					"/^\s*LABELOFFSET\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $buffer,
					$matches))
				{
					$curnode->labeloffsetx=$matches[1];
					$curnode->labeloffsety=$matches[2];

					$linematched++;
				}

				if ($last_seen == 'NODE' && preg_match(
					"/^\s*LABELOFFSET\s+(C|NE|SE|NW|SW|N|S|E|W)\s*$/i", $buffer,
					$matches))
				{
					$curnode->labeloffset=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match("/^\s*VIA\s+(\d+)\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$curlink->vialist[]=array
						(
							$matches[1],
							$matches[2]
						);

					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match("/^\s*BWFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$curlink->bwfont=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match("/^\s*COMMENTFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$curlink->commentfont=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match(
					"/^\s*BWLABEL\s+(bits|percent|unformatted|none)\s*$/i", $buffer,
					$matches))
				{
					$curlink->labelstyle=strtolower($matches[1]);
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match(
					"/^\s*BWSTYLE\s+(classic|angled)\s*$/i", $buffer,
					$matches))
				{
					$curlink->labelboxstyle=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match(
					"/^\s*BWLABELPOS\s+(\d+)\s(\d+)\s*$/i", $buffer,
					$matches))
				{
					$curlink->labeloffset_in = $matches[1];
					$curlink->labeloffset_out = $matches[2];
					$linematched++;
				}
				
				if ($last_seen == 'LINK' && preg_match(
					"/^\s*COMMENTPOS\s+(\d+)\s(\d+)\s*$/i", $buffer,
					$matches))
				{
					$curlink->commentoffset_in = $matches[1];
					$curlink->commentoffset_out = $matches[2];
					$linematched++;
				}

				if( ($last_seen == 'NODE') && preg_match("/^\s*USESCALE\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?\s*$/i",$buffer,$matches))
				{
					$svar = '';
					if(isset($matches[2]))
					{
						$svar = trim($matches[2]);
					}
					
					if($matches[1] == 'none')
					{
						$curnode->usescale = $matches[1];
						if($svar != '')
						{
							$curnode->scalevar = $svar;
						}
					}
					else
					{
						$curnode->usescale = $matches[1];
						if($svar != '')
						{
							$curnode->scalevar = $svar;
						}
					}
					$linematched++;
				}

				if( ($last_seen == 'LINK') && preg_match("/^\s*USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s*$/i",$buffer,$matches))
				{
					$curlink->usescale = $matches[1];

					$linematched++;
				}

				// one REGEXP to rule them all:
				if(preg_match("/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?\s*$/i",
					$buffer, $matches))
				{
					// The default scale name is DEFAULT
					if($matches[1]=='') $matches[1] = 'DEFAULT';
					else $matches[1] = trim($matches[1]);

					$key=$matches[2] . '_' . $matches[3];

					$this->colours[$matches[1]][$key]['key']=$key;
					$this->colours[$matches[1]][$key]['bottom'] = (float)($matches[2]);
					$this->colours[$matches[1]][$key]['top'] = (float)($matches[3]);

					$this->colours[$matches[1]][$key]['red1'] = (int)($matches[4]);
					$this->colours[$matches[1]][$key]['green1'] = (int)($matches[5]);
					$this->colours[$matches[1]][$key]['blue1'] = (int)($matches[6]);

					// this is the second colour, if there is one
					if(isset($matches[7]))
					{
						$this->colours[$matches[1]][$key]['red2'] = (int) ($matches[7]);
						$this->colours[$matches[1]][$key]['green2'] = (int) ($matches[8]);
						$this->colours[$matches[1]][$key]['blue2'] = (int) ($matches[9]);
					}

					
					if(! isset($this->numscales[$matches[1]]))
					{
						$this->numscales[$matches[1]]=1;
					}
					else
					{
						$this->numscales[$matches[1]]++;
					}
					// we count if we've seen any default scale, otherwise, we have to add
					// one at the end.
					if($matches[1]=='DEFAULT')
					{
						$scalesseen++;
					}

					$linematched++;
				}

				if (preg_match("/^\s*KEYPOS\s+([A-Za-z][A-Za-z0-9_]*\s+)?(-?\d+)\s+(-?\d+)(.*)/i", $buffer, $matches))
				{
					$whichkey = trim($matches[1]);
					if($whichkey == '') $whichkey = 'DEFAULT';
					
					$this->keyx[$whichkey]=$matches[2];
					$this->keyy[$whichkey]=$matches[3];
					$extra=trim($matches[4]);

					if ($extra != '')
						$this->keytext[$whichkey] = $extra;
					if(!isset($this->keytext[$whichkey]))
						$this->keytext[$whichkey] = "DEFAULT TITLE";
					if(!isset($this->keystyle[$whichkey]))
						$this->keystyle[$whichkey] = "classic";
					
					$linematched++;
				}

				if (preg_match("/^\s*TITLEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i", $buffer, $matches))
				{
					$this->titlex=$matches[1];
					$this->titley=$matches[2];
					$extra=trim($matches[3]);

					if ($extra != '')
						$this->title=$extra;

					$linematched++;
				}

				// truetype font definition (actually, we don't really check if it's truetype) - filename + size
				if (preg_match("/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s*$/i", $buffer, $matches))
				{
					if (function_exists("imagettfbbox"))
					{
						// test if this font is valid, before adding it to the font table...
						$bounds=@imagettfbbox($matches[3], 0, $matches[2], "Ignore me");

						if (isset($bounds[0]))
						{
							$this->fonts[$matches[1]]->type="truetype";
							$this->fonts[$matches[1]]->file=$matches[2];
							$this->fonts[$matches[1]]->size=$matches[3];
						}
						else { warn
							("Failed to load ttf font " . $matches[2] . " - at config line $linecount\n [WMWARN30]"); }
					}
					else { warn
						("imagettfbbox() is not a defined function. You don't seem to have FreeType compiled into your gd module. [WMWARN31]\n");
					}

					$linematched++;
				}

				// GD font definition (no size here)
				if (preg_match("/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s*$/i", $buffer, $matches))
				{
					$newfont=imageloadfont($matches[2]);

					if ($newfont)
					{
						$this->fonts[$matches[1]]->type="gd";
						$this->fonts[$matches[1]]->file=$matches[2];
						$this->fonts[$matches[1]]->gdnumber=$newfont;
					}
					else { warn ("Failed to load GD font: " . $matches[2]
						. " ($newfont) at config line $linecount [WMWARN32]\n"); }

					$linematched++;
				}

				if (preg_match("/^\s*KEYFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->keyfont=$matches[1];
					$linematched++;
				}

				if (preg_match("/^\s*TIMEFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->timefont=$matches[1];
					$linematched++;
				}

				if (preg_match("/^\s*TITLEFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->titlefont=$matches[1];
					$linematched++;
				}

				if (preg_match("/^\s*NODEFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->nodefont=$matches[1];
					$this->defaultnode->labelfont=$matches[1];
					warn
						("NODEFONT is deprecated. Use NODE DEFAULT and LABELFONT instead. config line $linecount\n");
					$linematched++;
				}

				if (preg_match("/^\s*LINKFONT\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->linkfont=$matches[1];
					$this->defaultlink->bwfont=$matches[1];
					warn
						("LINKFONT is deprecated. Use LINK DEFAULT and BWFONT instead. config line $linecount\n");
					$linematched++;
				}

				if (preg_match("/^\s*TIMEPOS\s+(\d+)\s+(\d+)(.*)\s*$/i", $buffer, $matches))
				{
					$this->timex=$matches[1];
					$this->timey=$matches[2];
					$extra=trim($matches[3]);

					if ($extra != '')
						$this->stamptext=$extra;

					$linematched++;
				}

				if(preg_match("/^\s*KEYSTYLE\s+([A-Za-z][A-Za-z0-9_]+\s+)?(classic|horizontal|vertical)\s+?(\d+)?\s*$/i",$buffer, $matches))
				{
					$whichkey = trim($matches[1]);
					if($whichkey == '') $whichkey = 'DEFAULT';
					$this->keystyle[$whichkey] = strtolower($matches[2]);
					
					if(isset($matches[3]) && $matches[3] != '')
					{
						$this->keysize[$whichkey] = $matches[3];
					}
					else
					{
						$this->keysize[$whichkey] = $this->keysize['DEFAULT'];
					}
					
					$linematched++;
				}

				if (preg_match("/^\s*BWLABELS\s+(bits|percent|none)\s*$/i", $buffer, $matches))
				{
					# $this->labelstyle = strtolower($matches[1]);
					$this->defaultlink->labelstyle=strtolower($matches[1]);
					warn
						("BWLABELS is deprecated. Use LINK DEFAULT and BWLABEL instead. config line $linecount\n");

					$linematched++;
				}

				if (preg_match("/^\s*KILO\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$this->kilo=$matches[1];
					$this->defaultlink->owner->kilo=$matches[1]; 
					$linematched++;
				}

				if (preg_match("/^\s*BACKGROUND\s+(.+)\s*$/i", $buffer, $matches))
				{
					$this->background=$matches[1];
					$this->used_images[] = $matches[1];
					$linematched++;
				}

				if (preg_match(
					"/^\s*(TIME|TITLE|KEYBG|KEYTEXT|KEYOUTLINE|BG)COLOR\s+(\d+)\s+(\d+)\s+(\d+)\s*$/i",
					$buffer,
					$matches))
				{
					$key=$matches[1];
					# "Found colour line for $key\n";
					$this->colours['DEFAULT'][$key]['red1']=$matches[2];
					$this->colours['DEFAULT'][$key]['green1']=$matches[3];
					$this->colours['DEFAULT'][$key]['blue1']=$matches[4];
					$this->colours['DEFAULT'][$key]['bottom']=-2;
					$this->colours['DEFAULT'][$key]['top']=-1;

					$linematched++;
				}

				if (($last_seen == 'NODE') && (preg_match(
					"/^\s*(LABELFONT|LABELFONTSHADOW|LABELBG|LABELOUTLINE)COLOR\s+(\d+)\s+(\d+)\s+(\d+)\s*$/i",
					$buffer,
					$matches)))
				{
					$key=$matches[1];
					#   print "Found NODE colour line for $key\n";
					$field=strtolower($matches[1]) . 'colour';
					$curnode->$field=array
						(
							$matches[2],
							$matches[3],
							$matches[4]
						);

					$linematched++;
				}

				if (($last_seen == 'LINK') && (preg_match(
					"/^\s*(COMMENTFONT|BWBOX|BWFONT|BWOUTLINE|OUTLINE)COLOR\s+(\d+)\s+(\d+)\s+(\d+)\s*$/i",
					$buffer,
					$matches)))
				{
					$key=$matches[1];
					# print "Found LINK colour line for $key\n";
					$field=strtolower($matches[1]) . 'colour';
					$curlink->$field=array
						(
							$matches[2],
							$matches[3],
							$matches[4]
						);

					$linematched++;
				}

				if (($last_seen == 'NODE') && (preg_match(
					"/^\s*(LABELFONTSHADOW|LABELBG|LABELOUTLINE)COLOR\s+none\s*$/i",
					$buffer,
					$matches)))
				{
					$key=$matches[1];
					#  print "Found NODE non-colour line for $key\n";
					$field=strtolower($matches[1]) . 'colour';
					$curnode->$field=array
						(
							-1,
							-1,
							-1
						);

					$linematched++;
				}

				if (($last_seen == 'LINK') && (preg_match(
					"/^\s*(BWBOX|BWOUTLINE|OUTLINE)COLOR\s+none\s*$/i", $buffer,
					$matches)))
				{
					$key=$matches[1];
					#  print "Found LINK non-colour line for $key\n";
					$field=strtolower($matches[1]) . 'colour';
					$curlink->$field=array
						(
							-1,
							-1,
							-1
						);

					$linematched++;
				}

				if (preg_match("/^\s*HTMLSTYLE\s+(static|overlib)\s*$/i", $buffer, $matches))
				{
					$this->htmlstyle=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match(
					"/^\s*ARROWSTYLE\s+(classic|compact)\s*$/i", $buffer, $matches))
				{
					$curlink->arrowstyle=$matches[1];
					$linematched++;
				}

				if ($last_seen == 'LINK' && preg_match(
					"/^\s*ARROWSTYLE\s+(\d+)\s+(\d+)\s*$/i", $buffer, $matches))
				{
					$curlink->arrowstyle=$matches[1] . ' ' . $matches[2];
					$linematched++;
				}

				if ($last_seen == '---' && preg_match(
					"/^\s*ARROWSTYLE\s+(classic|compact)\s*$/i", $buffer, $matches))
				{
					warn ("Global ARROWSTYLE is deprecated. Use LINK DEFAULT and ARROWSTYLE instead.\n");
					$this->defaultlink->arrowstyle=$matches[1];
					$linematched++;
				}

				if (preg_match("/^\s*TITLE\s+(.*)\s*$/i", $buffer, $matches))
				{
					$this->title=$matches[1];
					$linematched++;
				}

				if (preg_match("/^\s*HTMLOUTPUTFILE\s+(.*)\s*$/i", $buffer, $matches))
				{
					$this->htmloutputfile=trim($matches[1]);
					debug("SET HTMLOUTPUTFILE to $matches[1]\n");
					$linematched++;
				}

				if (preg_match("/^\s*IMAGEOUTPUTFILE\s+(.*)\s*$/i", $buffer, $matches))
				{
					$this->imageoutputfile=trim($matches[1]);
					debug("SET IMAGEOUTPUTFILE to $matches[1]\n");
					$linematched++;
				}

				if ($linematched == 0 && trim($buffer) != '') { warn
					("Unrecognised config on line $linecount: $buffer"); }

				if ($linematched > 1) { warn
				("Same line ($linecount) interpreted twice. This is a program error. Please report to Howie with your config!\nThe line was: $buffer");
				}
			} // if blankline
		}     // while

		if ($last_seen == "NODE")
		{
			# $this->nodes[$curnode->name] = $curnode;
			if ($curnode->name == 'DEFAULT')
			{
				$this->defaultnode=$curnode;
				debug ("Saving Default Node: " . $curnode->name . "\n");
			}
			else
			{
				$this->nodes[$curnode->name]=$curnode;
				debug ("Saving Node: " . $curnode->name . "\n");
			}
		}

		if ($last_seen == "LINK")
		{
			# $this->links[$curlink->name] = $curlink;
			if ($curlink->name == 'DEFAULT')
			{
				$this->defaultlink=$curlink;
				debug ("Saving Default Link: " . $curlink->name . "\n");
			}
			else
			{
				if (isset($curlink->a) && isset($curlink->b))
				{
					$this->links[$curlink->name]=$curlink;
					debug ("Saving Link: " . $curlink->name . "\n");
				}
				else { warn ("Dropping LINK " . $curlink->name . " - it hasn't got 2 NODES!"); }
			}
		}
	} // if $fd
	else
	{
		warn ("Couldn't open config file $filename for reading\n");
		return (FALSE);
	}

	fclose ($fd);

	// load some default colouring, otherwise it all goes wrong
	if ($scalesseen == 0)
	{
		debug ("Adding default SCALE set.\n");
		$defaults=array
			(
				'1_10' => array('bottom' => 1, 'top' => 10, 'red1' => 140, 'green1' => 0, 'blue1' => 255),
				'10_25' => array('bottom' => 10, 'top' => 25, 'red1' => 32, 'green1' => 32, 'blue1' => 255),
				'25_40' => array('bottom' => 25, 'top' => 40, 'red1' => 0, 'green1' => 192, 'blue1' => 255),
				'40_55' => array('bottom' => 40, 'top' => 55, 'red1' => 0, 'green1' => 240, 'blue1' => 0),
				'55_70' => array('bottom' => 55, 'top' => 70, 'red1' => 240, 'green1' => 240, 'blue1' => 0),
				'70_85' => array('bottom' => 70, 'top' => 85, 'red1' => 255, 'green1' => 192, 'blue1' => 0),
				'85_100' => array('bottom' => 85, 'top' => 100, 'red1' => 255, 'green1' => 0, 'blue1' => 0)
			);

		foreach ($defaults as $key => $def)
		{
			$this->colours['DEFAULT'][$key]=$def;
			$scalesseen++;
		}
	}
	else { debug ("Already have $scalesseen scales, no defaults added.\n"); }

	$this->numscales['DEFAULT']=$scalesseen;
	$this->configfile="$filename";

	// calculate any relative positions here - that way, nothing else
	// really needs to know about them

	// safety net for cyclic dependencies
	$i=100;
	do
	{
		$skipped = 0; $set=0;
		foreach ($this->nodes as $node)
		{
			if( ($node->relative_to != '') && (!$node->relative_resolved))
			{				
				debug("Resolving relative position for NODE ".$node->name." to ".$node->relative_to."\n");
				if(array_key_exists($node->relative_to,$this->nodes))
				{
					// check if we are relative to another node which is in turn relative to something
					// we need to resolve that one before we can resolve this one!
					if(  ($this->nodes[$node->relative_to]->relative_to != '') && (!$this->nodes[$node->relative_to]->relative_resolved) )
					{
						debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
						$skipped++;
					}
					else
					{
						// save the relative coords, so that WriteConfig can work
						// resolve the relative stuff
						
						$newpos_x = $this->nodes[$node->relative_to]->x + $this->nodes[$node->name]->x;
						$newpos_y = $this->nodes[$node->relative_to]->y + $this->nodes[$node->name]->y;
						debug("->$newpos_x,$newpos_y\n");
						$this->nodes[$node->name]->x = $newpos_x;
						$this->nodes[$node->name]->y = $newpos_y;				
						$this->nodes[$node->name]->relative_resolved=TRUE;
						$set++;
					}
				}
				else
				{
					warn("NODE ".$node->name." has a relative position to an unknown node! [WMWARN10]\n");
				}
			}
		}
		debug("Cycle $i - set $set and Skipped $skipped for unresolved dependencies\n");
		$i--;
	} while( ($set>0) && ($i!=0)  );
	
	if($skipped>0)	
	{ 
		warn("There are Circular dependencies in relative POSITION lines for $skipped nodes. [WMWARN11]\n");
	}

	# warn("---\n\nDEFAULT NODE AGAIN::".var_dump($this->defaultnode->hints)."::\n");
	#warn("DEFAULT NOW SAYS ".$this->defaultnode->hints['sigdigits']."\n");
	#warn("North NOW SAYS ".$this->nodes['North']->hints['sigdigits']."\n");


	debug("Running Pre-Processing Plugins...\n");
	foreach ($this->preprocessclasses as $pre_class)
	{
		debug("Running $pre_class"."->run()\n");
		# call_user_func(array($pre_class, 'run'), $this);
		call_user_func_array(array($pre_class, 'run'), array(&$this));

	}
	debug("Finished Pre-Processing Plugins...\n");

	return (TRUE);
}

function WriteConfig($filename)
{
	global $WEATHERMAP_VERSION;

	$fd=fopen($filename, "w");
	$output="";

	if ($fd)
	{
		$output.="# Automatically generated by php-weathermap v$WEATHERMAP_VERSION\n\n";

		if ($this->background != '') { $output.="BACKGROUND " . $this->background . "\n"; }
		else
		{
			$output.="WIDTH " . $this->width . "\n";
			$output.="HEIGHT " . $this->height . "\n";
		}

		if ($this->htmlstyle != $this->inherit_fieldlist['htmlstyle'])
		{ $output.="HTMLSTYLE " . $this->htmlstyle . "\n"; }

	#	if( $this->keystyle != $this->inherit_fieldlist['keystyle']) { $output .= "KEYSTYLE ".$this->keystyle."\n"; }
		if ($this->kilo != $this->inherit_fieldlist['kilo']) { $output.="KILO " . $this->kilo . "\n"; }

		$output.="\n";

		if (count($this->fonts) > 0)
		{
			foreach ($this->fonts as $fontnumber => $font)
			{
				if ($font->type == 'truetype')
					$output.=sprintf("FONTDEFINE %d %s %d\n", $fontnumber, $font->file, $font->size);

				if ($font->type == 'gd')
					$output.=sprintf("FONTDEFINE %d %s\n", $fontnumber, $font->file);
			}

			$output.="\n";
		}

		if ($this->keyfont != $this->inherit_fieldlist['keyfont'])
		{ $output.="KEYFONT " . $this->keyfont . "\n"; }

		if ($this->timefont != $this->inherit_fieldlist['timefont'])
		{ $output.="TIMEFONT " . $this->timefont . "\n"; }

		if ($this->titlefont != $this->inherit_fieldlist['titlefont'])
		{ $output.="TITLEFONT " . $this->titlefont . "\n"; }

		if (trim($this->title) != $this->inherit_fieldlist['title'])
		{ $output.="TITLE " . $this->title . "\n"; }

		if (trim($this->htmloutputfile) != $this->inherit_fieldlist['htmloutputfile'])
		{ $output.="HTMLOUTPUTFILE " . $this->htmloutputfile . "\n"; }

		if (trim($this->imageoutputfile) != $this->inherit_fieldlist['imageoutputfile'])
		{ $output.="IMAGEOUTPUTFILE " . $this->imageoutputfile . "\n"; }

		if (($this->timex != $this->inherit_fieldlist['timex'])
			|| ($this->timey != $this->inherit_fieldlist['timey'])
			|| ($this->stamptext != $this->inherit_fieldlist['stamptext']))
				$output.="TIMEPOS " . $this->timex . " " . $this->timey . " " . $this->stamptext . "\n";

		if (($this->titlex != $this->inherit_fieldlist['titlex'])
			|| ($this->titley != $this->inherit_fieldlist['titley']))
				$output.="TITLEPOS " . $this->titlex . " " . $this->titley . "\n";

		$output.="\n";

		foreach ($this->colours as $scalename=>$colours)
		{
		  // not all keys will have keypos but if they do, then all three vars should be defined
		if ( (isset($this->keyx[$scalename])) && (isset($this->keyy[$scalename])) && (isset($this->keytext[$scalename]))
		    && (($this->keytext[$scalename] != $this->inherit_fieldlist['keytext'])
			|| ($this->keyx[$scalename] != $this->inherit_fieldlist['keyx'])
			|| ($this->keyy[$scalename] != $this->inherit_fieldlist['keyy'])))
			{
			     // sometimes a scale exists but without defaults. A proper scale object would sort this out...
			     if($this->keyx[$scalename] == '') { $this->keyx[$scalename] = -1; }
			     if($this->keyy[$scalename] == '') { $this->keyy[$scalename] = -1; }

				$output.="KEYPOS " . $scalename." ". $this->keyx[$scalename] . " " . $this->keyy[$scalename] . " " . $this->keytext[$scalename] . "\n";
            }
            
		if ( (isset($this->keystyle[$scalename])) &&  ($this->keystyle[$scalename] != $this->inherit_fieldlist['keystyle']['DEFAULT']) )
		{
			$extra='';
			if ( (isset($this->keysize[$scalename])) &&  ($this->keysize[$scalename] != $this->inherit_fieldlist['keysize']['DEFAULT']) )
			{
				$extra = " ".$this->keysize[$scalename];
			}
			$output.="KEYSTYLE  " . $scalename." ". $this->keystyle[$scalename] . $extra . "\n";
		}

			foreach ($colours as $k => $colour)
			{
				if ($colour['top'] >= 0)
				{
					$top = rtrim(rtrim(sprintf("%f",$colour['top']),"0"),".");
					$bottom= rtrim(rtrim(sprintf("%f",$colour['bottom']),"0"),".");

					if (!isset($colour['red2']))
						$output.=sprintf("SCALE %s %s %s   %d %d %d\n", $scalename,
							$bottom, $top,
							$colour['red1'],            $colour['green1'], $colour['blue1']);
					else
						$output.=sprintf("SCALE %s %s %s   %d %d %d   %d %d %d\n", $scalename,
							$bottom, $top,
							$colour['red1'],
							$colour['green1'],                     $colour['blue1'],
							$colour['red2'],                       $colour['green2'],
							$colour['blue2']);
				}
				else { $output.=sprintf("%sCOLOR %d %d %d\n", $k, $colour['red1'], $colour['green1'],
					$colour['blue1']); }
			}
			$output .= "\n";
		}

		foreach ($this->hints as $hintname=>$hint)
		{
			$output .= "SET $hintname $hint\n";
		}

		$output.="\n# End of global section\n\n# DEFAULT definitions:\n";

		fwrite($fd, $output);

		fwrite($fd,$this->defaultnode->WriteConfig());
		fwrite($fd,$this->defaultlink->WriteConfig());

		fwrite($fd, "\n# End of DEFAULTS section\n\n# Node definitions:\n");

		foreach ($this->nodes as $node)
		{
			fwrite($fd,$node->WriteConfig());
		}

		fwrite($fd, "\n# End of NODE section\n\n# Link definitions:\n");

		foreach ($this->links as $link)
		{
			fwrite($fd,$link->WriteConfig());
		}

		fwrite($fd, "\n# End of LINK section\n\n# That's All Folks!\n");
	}
	else
	{
		warn ("Couldn't open config file $filename for writing");
		return (FALSE);
	}

	return (TRUE);
}

// pre-allocate colour slots for the colours used by the arrows
// this way, it's the pretty icons that suffer if there aren't enough colours, and
// not the actual useful data
// we skip any gradient scales
function AllocateScaleColours($im,$refname='gdref1')
{
	# $colours=$this->colours['DEFAULT'];
	foreach ($this->colours as $scalename=>$colours)
	{
		foreach ($colours as $key => $colour)
		{
			if (!isset($this->colours[$scalename][$key]['red2']))
			{
				$r=$colour['red1'];
				$g=$colour['green1'];
				$b=$colour['blue1'];
				debug ("AllocateScaleColours: $scalename $key ($r,$g,$b)\n");
				$this->colours[$scalename][$key][$refname]=myimagecolorallocate($im, $r, $g, $b);
			}
		}
	}
}

function DrawMap($filename = '', $thumbnailfile = '', $thumbnailmax = 250, $withnodes = TRUE)
{
	$bgimage=NULL;
	$this->cachefile_version = crc32(file_get_contents($this->configfile));

	debug("Running Post-Processing Plugins...\n");
	foreach ($this->postprocessclasses as $post_class)
	{
		debug("Running $post_class"."->run()\n");
		call_user_func_array(array($post_class, 'run'), array(&$this));
		
	}
	debug("Finished Post-Processing Plugins...\n");

	$this->datestamp = strftime($this->stamptext, time());

	// do the basic prep work
	if ($this->background != '')
	{
		if (is_readable($this->background))
		{
			$bgimage=imagecreatefromfile($this->background);

			if (!$bgimage) { warn
				("Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
			}
			else
			{
				$this->width=imagesx($bgimage);
				$this->height=imagesy($bgimage);
			}
		}
		else { warn
			("Your background image file could not be read. Check the filename, and permissions, for "
			. $this->background . "\n"); }
	}

	$image=imagecreatetruecolor($this->width, $this->height);

	# $image = imagecreate($this->width, $this->height);
	if (!$image) { warn
		("Couldn't create output image in memory (" . $this->width . "x" . $this->height . ")."); }
	else
	{
		ImageAlphaBlending($image, true);
		# imageantialias($image,true);

		// by here, we should have a valid image handle

		// save this away, now
		$this->image=$image;

		$this->white=myimagecolorallocate($image, 255, 255, 255);
		$this->black=myimagecolorallocate($image, 0, 0, 0);
		$this->grey=myimagecolorallocate($image, 192, 192, 192);
		$this->selected=myimagecolorallocate($image, 255, 0, 0); // for selections in the editor

		$this->AllocateScaleColours($image);

		// fill with background colour anyway, in case the background image failed to load
		imagefilledrectangle($image, 0, 0, $this->width, $this->height, $this->colours['DEFAULT']['BG']['gdref1']);

		if ($bgimage)
		{
			imagecopy($image, $bgimage, 0, 0, 0, 0, $this->width, $this->height);
			imagedestroy ($bgimage);
		}

		// Now it's time to draw a map

		# foreach ($this->nodes as $node) { $this->nodes[$node->name]->calc_size(); }                    

		foreach ($this->nodes as $node) { $node->pre_render($image, $this); }
		foreach ($this->links as $link) { $link->Draw($image, $this); }

		if($withnodes)
		{
			foreach ($this->nodes as $node) {
				$node->NewDraw($image, $this);
				# debug($node->name.": ".var_dump($node->notes)."\n");
			}
			# debug("DEFAULT: ".var_dump($this->defaultnode->notes)."\n");
		}

		  foreach ($this->colours as $scalename=>$colours)
		{
			debug("Drawing KEY for $scalename if necessary.\n");

			if( (isset($this->numscales[$scalename])) && (isset($this->keyx[$scalename])) && ($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0) )
			{
				if($this->keystyle[$scalename]=='classic') $this->DrawLegend_Classic($image,$scalename);
				if($this->keystyle[$scalename]=='horizontal') $this->DrawLegend_Horizontal($image,$scalename,$this->keysize[$scalename]);
				if($this->keystyle[$scalename]=='vertical') $this->DrawLegend_Vertical($image,$scalename,$this->keysize[$scalename]);
			}
		}

		$this->DrawTimestamp($image, $this->timefont, $this->colours['DEFAULT']['TIME']['gdref1']);
		$this->DrawTitle($image, $this->titlefont, $this->colours['DEFAULT']['TITLE']['gdref1']);

		# $this->DrawNINK($image,300,300,48);

		// Ready to output the results...

		if($filename == 'null')
		{
			// do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
		}
		else
		{
			if ($filename == '') { imagepng ($image); }
			else {
				$result = FALSE;
				$functions = TRUE;
				if(function_exists('imagejpeg') && preg_match("/\.jpg/i",$filename))
				{
					debug("Writing JPEG file to $filename\n");
					$result = imagejpeg($image, $filename);
				}
				elseif(function_exists('imagegif') && preg_match("/\.gif/i",$filename))
				{
					debug("Writing GIF file to $filename\n");
					$result = imagegif($image, $filename);
				}
				elseif(function_exists('imagepng') && preg_match("/\.png/i",$filename))
				{
					debug("Writing PNG file to $filename\n");
					$result = imagepng($image, $filename);
				}
				else
				{
					warn("Failed to write map image. No function existed for the image format you requested. [WMWARN12]\n");
					$functions = FALSE;
				}
				
				if(($result==FALSE) && ($functions==TRUE))
				{
					if(file_exists($filename))
					{
						warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
					}
					else
					{
						warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
					}
				}
			}
		}

		if($this->context == 'editor2')
		{
			$cachefile = $this->cachefolder.DIRECTORY_SEPARATOR.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
			imagepng($image, $cachefile);
			$cacheuri = $this->cachefolder.'/'.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
			$this->mapcache = $cacheuri;
		}

		if (function_exists('imagecopyresampled'))
		{
			// if one is specified, and we can, write a thumbnail too
			if ($thumbnailfile != '')
			{
				$result = FALSE;
				if ($this->width > $this->height) { $factor=($thumbnailmax / $this->width); }
				else { $factor=($thumbnailmax / $this->height); }

				$twidth=$this->width * $factor;
				$theight=$this->height * $factor;

				$imagethumb=imagecreatetruecolor($twidth, $theight);
				imagecopyresampled($imagethumb, $image, 0, 0, 0, 0, $twidth, $theight,
					$this->width, $this->height);
				$result = imagepng($imagethumb, $thumbnailfile);
				imagedestroy($imagethumb);
				
				if(($result==FALSE))
				{
					if(file_exists($filename))
					{
						warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN15]");
					}
					else
					{
						warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN16]");
					}
				}
			}
		}
		else
		{
			warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
		}
		imagedestroy ($image);
	}
}

function CleanUp()
{
	// destroy all the images we created, to prevent memory leaks
	foreach ($this->nodes as $node) { if(isset($node->image)) imagedestroy($node->image); }
	#foreach ($this->nodes as $node) { unset($node); }
	#foreach ($this->links as $link) { unset($link); }

}

function PreloadMapHTML()
{
	if ($this->htmlstyle == "overlib")
	{
		//   onmouseover="return overlib('<img src=graph.png>',DELAY,250,CAPTION,'$caption');"  onmouseout="return nd();"

		$center_x=$this->width / 2;
		$center_y=$this->height / 2;

		foreach ($this->links as $link)
		{
			if ( ($link->overliburl != '') || ($link->notestext != '') )
			{
				# $overlibhtml = "onmouseover=\"return overlib('&lt;img src=".$link->overliburl."&gt;',DELAY,250,CAPTION,'".$link->name."');\"  onmouseout=\"return nd();\"";
				#  $a_x=$link->a->x;
				# $b_x=$link->b->x;
				# $a_y=$link->a->y;
				# $b_y=$link->b->y;
				
				$a_x=$this->nodes[$link->a->name]->x;
				$a_y=$this->nodes[$link->a->name]->y;
			 
				$b_x=$this->nodes[$link->b->name]->x;
				$b_y=$this->nodes[$link->b->name]->y;
				
				$mid_x=($a_x + $b_x) / 2;
				$mid_y=($a_y + $b_y) / 2;

				# debug($link->overlibwidth."---".$link->overlibheight."---\n");

				$left="";
				$above="";

				if ($link->overlibwidth != 0)
				{
					$left="WIDTH," . $link->overlibwidth . ",";

					if ($mid_x > $center_x)
						$left.="LEFT,";
				}

				if ($link->overlibheight != 0)
				{
					$above="HEIGHT," . $link->overlibheight . ",";

					if ($mid_y > $center_y)
						$above.="ABOVE,";
				}

				$caption = ($link->overlibcaption != '' ? $link->overlibcaption : $link->name);
				$caption = $this->ProcessString($caption,$link);

				$overlibhtml = "onmouseover=\"return overlib('";
				if($link->overliburl != '')
				{
					$overlibhtml .= "&lt;img src=" . $this->ProcessString($link->overliburl,$link) . "&gt;";
				}
				if($link->notestext != '')
				{
					# put in a linebreak if there was an image AND notes
					if($link->overliburl != '') $overlibhtml .= '&lt;br /&gt;';
					$note = $this->ProcessString($link->notestext,$link);
					$note = htmlspecialchars($note, ENT_NOQUOTES);
					$note=str_replace("'", "\\&apos;", $note);
					$note=str_replace('"', "&quot;", $note);
					$overlibhtml .= $note;
				}
				$overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption
				. "');\"  onmouseout=\"return nd();\"";

				for($i=0; $i<4; $i++)
				{
					$this->imap->setProp("extrahtml", $overlibhtml, "LINK:" . $link->name.":$i");
				}
			}
		}

		foreach ($this->nodes as $node)
		{
			if ( ($node->overliburl != '') || ($node->notestext != '') )
			{
				# $overlibhtml = "onmouseover=\"return overlib('&lt;img src=".$node->overliburl."&gt;',DELAY,250,CAPTION,'".$node->name."');\"  onmouseout=\"return nd();\"";

				debug ($node->overlibwidth . "---" . $node->overlibheight . "---\n");

				$left="";
				$above="";

				if ($node->overlibwidth != 0)
				{
					$left="WIDTH," . $node->overlibwidth . ",";

					if ($node->x > $center_x)
						$left.="LEFT,";
				}

				if ($node->overlibheight != 0)
				{
					$above="HEIGHT," . $node->overlibheight . ",";

					if ($node->y > $center_y)
						$above.="ABOVE,";
				}

				$caption = ($node->overlibcaption != '' ? $node->overlibcaption : $node->name);
				$caption  = $this->ProcessString($caption,$node);

				$overlibhtml = "onmouseover=\"return overlib('";
				if($node->overliburl != '')
				{
					$overlibhtml .= "&lt;img src=" . $this->ProcessString($node->overliburl,$node) . "&gt;";
				}
				if($node->notestext != '')
				{
					# put in a linebreak if there was an image AND notes
					if($node->overliburl != '') $overlibhtml .= '&lt;br /&gt;';
					$note = $this->ProcessString($node->notestext,$node);
					$note = htmlspecialchars($note, ENT_NOQUOTES);
					$note=str_replace("'", "\\&apos;", $note);
					$note=str_replace('"', "&quot;", $note);
					$overlibhtml .= $note;
				}
				$overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption
				. "');\"  onmouseout=\"return nd();\"";

				# $overlibhtml .= " onclick=\"return overlib('Some Test or other',CAPTION,'MENU',)\"";

				for($i=0; $i<4; $i++)
				{
					$this->imap->setProp("extrahtml", $overlibhtml, "NODE:" . $node->name.":$i");
				}
			}
		}
	}

	if ($this->htmlstyle == 'editor')
	{
		foreach ($this->links as $link) {
			#   $this->imap->setProp("href","#","LINK:".$link->name);
			#  $this->imap->setProp("extrahtml","onclick=\"click_handler('link','".$link->name."');\"","LINK:".$link->name);
		}

		foreach ($this->nodes as $node) {
			#    $this->imap->setProp("href","#","NODE:".$node->name);
			#    $this->imap->setProp("extrahtml","onclick=\"click_handler('node','".$node->name."');\"","NODE:".$node->name);
			# $this->imap->setProp("extrahtml","onclick=\"alert('".$node->name."');\"","NODE:".$node->name);
		}
	}
	else
	{
		foreach ($this->links as $link)
		{
			if ($link->infourl != '') {
				for($i=0; $i<4; $i++)
				{
					$areaname = "LINK:" . $link->name . ":$i";
					$this->imap->setProp("href", $this->ProcessString($link->infourl,$link), $areaname);
				}
			}
		}

		foreach ($this->nodes as $node)
		{			
			if ($node->infourl != '') {
				for($i=0; $i<4; $i++)
				{
					$areaname = "NODE:" . $node->name . ":$i";
					$this->imap->setProp("href", $this->ProcessString($node->infourl,$node), $areaname);
				}
			}
		}
	}
}

function asJS()
{
	$js='';

	$js.="var Links = new Array();\n";
	$js.=$this->defaultlink->asJS();

	foreach ($this->links as $link) { $js.=$link->asJS(); }

	$js.="var Nodes = new Array();\n";
	$js.=$this->defaultnode->asJS();

	foreach ($this->nodes as $node) { $js.=$node->asJS(); }

	return $js;
}

function asJSON()
{
	$json = '';

	$json .= "{ \n";

	$json .= "'map': {  \n";
	foreach (array_keys($this->inherit_fieldlist)as $fld)
	{
		$json .= js_escape($fld).": ";
		$json .= js_escape($this->$fld);
		$json .= ",\n";
	}
	$json = rtrim($json,", \n");
	$json .= "\n},\n";

	$json .= "'nodes': {\n";
	$json .= $this->defaultnode->asJSON();
	foreach ($this->nodes as $node) { $json .= $node->asJSON(); }
	$json = rtrim($json,", \n");
	$json .= "\n},\n";



	$json .= "'links': {\n";
	$json .= $this->defaultlink->asJSON();
	foreach ($this->links as $link) { $json .= $link->asJSON(); }
	$json = rtrim($json,", \n");
	$json .= "\n},\n";

	$json .= "'imap': [\n";
	$json .= $this->imap->subJSON("NODE:");
	// should check if there WERE nodes...
	$json .= ",\n";
	$json .= $this->imap->subJSON("LINK:");
	$json .= "\n]\n";
	$json .= "\n";

	$json .= ", valid: 1}\n";

	return($json);
}

// imagemapname is a parameter, so we can stack up several maps in the Cacti plugin
function MakeHTML($imagemapname = "weathermap_imap")
{
	$this->PreloadMapHTML();

	$html='';

	$html .= '<div class="weathermapimage" style="margin-left: auto; margin-right: auto; width: '.$this->width.'px;" >';
	if ($this->imageuri != '') { $html.=sprintf(
		'<img src="%s" width="%s" height="%s" border="0" usemap="#'
		. $imagemapname . '" alt="network weathermap" />',
		$this->imageuri,
		$this->width,
		$this->height); }
	else { $html.=sprintf(
		'<img src="%s" width="%s" height="%s" border="0" usemap="#' . $imagemapname
		. '" alt="network weathermap" />',
		$this->imagefile,
		$this->width,
		$this->height); }
	$html .= '</div>';

	$html.='<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

	$html.=$this->imap->subHTML("NODE:",true);
	$html.=$this->imap->subHTML("LINK:",true);

	$html.='</map>';

	return ($html);
}

// update any editor cache files.
// if the config file is newer than the cache files, or $agelimit seconds have passed,
// then write new stuff, otherwise just return.
// ALWAYS deletes files in the cache folder older than $agelimit, also!
function CacheUpdate($agelimit=600)
{
	$cachefolder = $this->cachefolder;
	$configchanged = filemtime($this->configfile );
	// make a unique, but safe, prefix for all cachefiles related to this map config
	// we use CRC32 because it makes for a shorter filename, and collisions aren't the end of the world.
	$cacheprefix = dechex(crc32($this->configfile));

	debug("Comparing files in $cachefolder starting with $cacheprefix, with date of $configchanged\n");

	$dh=opendir($cachefolder);

	if ($dh)
	{
		while ($file=readdir($dh))
		{
			$realfile = $cachefolder . DIRECTORY_SEPARATOR . $file;

			if(is_file($realfile) && ( preg_match('/^'.$cacheprefix.'/',$file) ))
				//                                            if (is_file($realfile) )
			{
				debug("$realfile\n");
				if( (filemtime($realfile) < $configchanged) || ((time() - filemtime($realfile)) > $agelimit) ) 
				{
					debug("Cache: deleting $realfile\n");
					unlink($realfile);
				}
			}
		}

		closedir ($dh);

		foreach ($this->nodes as $node)
		{
			if(isset($node->image))
			{
				$nodefile = $cacheprefix."_".dechex(crc32($node->name)).".png";
				$this->nodes[$node->name]->cachefile = $nodefile;
				imagepng($node->image,$cachefolder.DIRECTORY_SEPARATOR.$nodefile);
			}
		}
		
		foreach ($this->keyimage as $key=>$image)
		{
				$scalefile = $cacheprefix."_scale_".dechex(crc32($key)).".png";
				$this->keycache[$key] = $scalefile;
				imagepng($image,$cachefolder.DIRECTORY_SEPARATOR.$scalefile);
		}


		$json = "";
		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_map.json","w");
		foreach (array_keys($this->inherit_fieldlist)as $fld)
		{
			$json .= js_escape($fld).": ";
			$json .= js_escape($this->$fld);
			$json .= ",\n";
		}
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);
		
		

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_nodes.json","w");
		$json = $this->defaultnode->asJSON(TRUE);
		foreach ($this->nodes as $node) { $json .= $node->asJSON(TRUE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_nodes_lite.json","w");
		$json = $this->defaultnode->asJSON(FALSE);
		foreach ($this->nodes as $node) { $json .= $node->asJSON(FALSE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);
		
		

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_links.json","w");
		$json = $this->defaultlink->asJSON(TRUE);
		foreach ($this->links as $link) { $json .= $link->asJSON(TRUE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_links_lite.json","w");
		$json = $this->defaultlink->asJSON(FALSE);
		foreach ($this->links as $link) { $json .= $link->asJSON(FALSE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);
		
		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_imaphtml.json","w");
		$json = $this->imap->subHTML("LINK:");
		fputs($fd,$json);
		fclose($fd);
		

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_imap.json","w");
		$json = '';
		$nodejson = trim($this->imap->subJSON("NODE:"));
		if($nodejson != '')
		{
			$json .= $nodejson;
			// should check if there WERE nodes...
			$json .= ",\n";
		}
		$json .= $this->imap->subJSON("LINK:");
		fputs($fd,$json);
		fclose($fd);

	}
	else { debug("Couldn't read cache folder.\n"); }
}
};
// vim:ts=4:sw=4:
?>
