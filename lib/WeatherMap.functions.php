<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

// Utility functions
// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
function wm_module_checks()
{
	if (!extension_loaded('gd'))
	{
		wm_warn ("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
		wm_warn ("\nrun check.php to check PHP requirements.\n\n");

		return (FALSE);
	}

	if (!function_exists('imagecreatefrompng'))
	{
		wm_warn ("Your GD php module doesn't support PNG format. [WMWARN21]\n");
		wm_warn ("\nrun check.php to check PHP requirements.\n\n");
		return (FALSE);
	}

	if (!function_exists('imagecreatetruecolor'))
	{
		wm_warn ("Your GD php module doesn't support truecolor. [WMWARN22]\n");
		wm_warn ("\nrun check.php to check PHP requirements.\n\n");
		return (FALSE);
	}

	if (!function_exists('imagecopyresampled'))
	{
		wm_warn ("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
	}
	return (TRUE);
}

function wm_debug($string)
{
	global $weathermap_debugging;
	global $weathermap_map;
	global $weathermap_debug_suppress;

	if ($weathermap_debugging)
	{
		$calling_fn = "";
		if(function_exists("debug_backtrace"))
		{
			$bt = debug_backtrace();
			$index = 1;
		# 	$class = (isset($bt[$index]['class']) ? $bt[$index]['class'] : '');
        		$function = (isset($bt[$index]['function']) ? $bt[$index]['function'] : '');
			$index = 0;
			$file = (isset($bt[$index]['file']) ? basename($bt[$index]['file']) : '');
        		$line = (isset($bt[$index]['line']) ? $bt[$index]['line'] : '');

			$calling_fn = " [$function@$file:$line]";

			if(is_array($weathermap_debug_suppress) && in_array(strtolower($function),$weathermap_debug_suppress)) return;
		}

		// use Cacti's debug log, if we are running from the poller
		if (function_exists('debug_log_insert') && (!function_exists('show_editor_startpage')))
		{ cacti_log("DEBUG:$calling_fn " . ($weathermap_map==''?'':$weathermap_map.": ") . rtrim($string), true, "WEATHERMAP"); }
		else
		{
			$stderr=fopen('php://stderr', 'w');
			fwrite($stderr, "DEBUG:$calling_fn " . ($weathermap_map==''?'':$weathermap_map.": ") . $string);
			fclose ($stderr);

			// mostly this is overkill, but it's sometimes useful (mainly in the editor)
			if(1==0)
			{
				$log=fopen('debug.log', 'a');
				fwrite($log, "DEBUG:$calling_fn " . ($weathermap_map==''?'':$weathermap_map.": ") . $string);
				fclose ($log);
			}
		}
	}
}

function wm_warn($string,$notice_only=FALSE)
{
	global $weathermap_map;
	global $weathermap_warncount;
    global $weathermap_error_suppress;
	
	$message = "";
	$code = "";
	
	if(preg_match('/\[(WM\w+)\]/', $string, $matches)) {
        $code = $matches[1];
    }

    if ( (true === is_array($weathermap_error_suppress))
                && ( true === in_array(strtoupper($code), $weathermap_error_suppress))) {
                
                // This error code has been deliberately disabled.
                return;
    }
	
	if(!$notice_only)
	{
		$weathermap_warncount++;
		$message .= "WARNING: ";
	}
	
	$message .= ($weathermap_map==''?'':$weathermap_map.": ") . rtrim($string);
	
	// use Cacti's debug log, if we are running from the poller
	if (function_exists('cacti_log') && (!function_exists('show_editor_startpage')))
	{ cacti_log($message, true, "WEATHERMAP"); }
	else
	{
		$stderr=fopen('php://stderr', 'w');
		fwrite($stderr, $message."\n");
		fclose ($stderr);
	}
}

function js_escape($str, $wrap=TRUE)
{
	$str=str_replace('\\', '\\\\', $str);
	$str=str_replace('"', '\\"', $str);

	if($wrap) $str='"' . $str . '"';

	return ($str);
}

function mysprintf($format, $value, $kilo = 1000)
{
	$output = "";

	wm_debug("mysprintf: $format $value\n");
	if (preg_match('/%(\d*\.?\d*)k/', $format, $matches)) {
		$spec = $matches[1];
		$places = 2;
		if ($spec != '') {
			preg_match('/(\d*)\.?(\d*)/', $spec, $matches);
			if ($matches[2] != '') {
				$places = $matches[2];
			}
			// we don't really need the justification (pre-.) part...
		}
		wm_debug("KMGT formatting $value with $spec.\n");
		$result = nice_scalar($value, $kilo, $places);
		$output = preg_replace("/%" . $spec . "k/", $format, $result);
	} elseif (preg_match('/%(-*)(\d*)([Tt])/', $format, $matches)) {
		$spec = $matches [3];
		$precision = ($matches [2] == '' ? 10 : intval($matches [2]));
		$joinchar = " ";
		if ($matches [1] == "-") {
			$joinchar = " ";
		}
		// special formatting for time_t (t) and SNMP TimeTicks (T)
		if ($spec == "T") {
			$value = $value / 100;
		}
		$results = array();
		$periods = array(
			"y" => 24 * 60 * 60 * 365,
			"d" => 24 * 60 * 60,
			"h" => 60 * 60,
			"m" => 60,
			"s" => 1
		);
		foreach ($periods as $periodsuffix => $timeperiod) {
			$slot = floor($value / $timeperiod);
			$value = $value - $slot * $timeperiod;
			if ($slot > 0) {
				$results [] = sprintf("%d%s", $slot, $periodsuffix);
			}
		}
		if (sizeof($results) == 0) {
			$results [] = "0s";
		}
		$output = implode($joinchar, array_slice($results, 0, $precision));
	} else {
		wm_debug("Falling through to standard sprintf\n");
		$output = sprintf($format, $value);
	}
	return $output;
}

// ParseString is based on code from:
// http://www.webscriptexpert.com/Php/Space-Separated%20Tag%20Parser/

function wm_parse_string($input)
{
    $output = array();            // Array of Output
    $cPhraseQuote = null;   // Record of the quote that opened the current phrase
    $sPhrase = null;                // Temp storage for the current phrase we are building
   
    // Define some constants
    $sTokens = " \t";    // Space, Tab
    $sQuotes = "'\"";                // Single and Double Quotes
   
    // Start the State Machine
    do
    {
        // Get the next token, which may be the first
        $sToken = isset($sToken)? strtok($sTokens) : strtok($input, $sTokens);
       
        // Are there more tokens?
        if ($sToken === false)
        {
                // Ensure that the last phrase is marked as ended
                $cPhraseQuote = null;
        }
        else
        {              
                // Are we within a phrase or not?
                if ($cPhraseQuote !== null)
                {
                        // Will the current token end the phrase?
                        if (substr($sToken, -1, 1) === $cPhraseQuote)
                        {
                                // Trim the last character and add to the current phrase, with a single leading space if necessary
                                if (strlen($sToken) > 1) $sPhrase .= ((strlen($sPhrase) > 0)? ' ' : null) . substr($sToken, 0, -1);
                                $cPhraseQuote = null;
                        }
                        else
                        {
                                // If not, add the token to the phrase, with a single leading space if necessary
                                $sPhrase .= ((strlen($sPhrase) > 0)? ' ' : null) . $sToken;
                        }
                }
                else
                {
                        // Will the current token start a phrase?
                        if (strpos($sQuotes, $sToken[0]) !== false)
                        {
                                // Will the current token end the phrase?
                                if ((strlen($sToken) > 1) && ($sToken[0] === substr($sToken, -1, 1)))
                                {
                                        // The current token begins AND ends the phrase, trim the quotes
                                        $sPhrase = substr($sToken, 1, -1);
                                }
                                else
                                {
                                        // Remove the leading quote
                                        $sPhrase = substr($sToken, 1);
                                        $cPhraseQuote = $sToken[0];
                                }
                        }
                        else
                                $sPhrase = $sToken;
                }
        }
       
        // If, at this point, we are not within a phrase, the prepared phrase is complete and can be added to the array
        if (($cPhraseQuote === null) && ($sPhrase != null))
        {
            $output[] = $sPhrase;
            $sPhrase = null;
        }
    }
    while ($sToken !== false);      // Stop when we receive FALSE from strtok()
    
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

// PHP < 5.3 doesn't support anonymous functions, so here's a little function for screenshotify
function screenshotify_xxx($matches)
{
	return str_repeat('x',strlen($matches[1]));
}


function screenshotify($input)
{
	$output = $input;
	$output = preg_replace ( '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', "127.0.0.1", $output );
	$output = preg_replace_callback ( '/([A-Za-z]{3,})/', "screenshotify_xxx", $output );
	return ($output);
}

function is_copy($arr)
{
	if ($arr['red1'] == -2 && $arr['green1'] == -2 && $arr['blue1'] == -2) {
		return true;
	}
	return false;
}

function is_contrast($arr)
{
	if ($arr['red1'] == -3 && $arr['green1'] == -3 && $arr['blue1'] == -3) {
		return true;
	}
	return false;
}

function is_none($arr)
{
	if ($arr['red1'] == -1 && $arr['green1'] == -1 && $arr['blue1'] == -1) {
		return true;
	}
	return false;
}

function render_colour($col)
{
	if (($col[0] == -1) && ($col[1] == -1) && ($col[1] == -1)) { return 'none'; }
	else if (($col[0] == -2) && ($col[1] == -2) && ($col[1] == -2)) { return 'copy'; }
	else if (($col[0] == -3) && ($col[1] == -3) && ($col[1] == -3)) { return 'contrast'; }
	else { return sprintf("%d %d %d", $col[0], $col[1], $col[2]); }
}

// take the same set of points that imagepolygon does, but don't close the shape
function imagepolyline($image, $points, $npoints, $color)
{
	for ($i=0; $i < ($npoints - 1); $i++) 
	{ 
		imageline($image, $points[$i * 2], $points[$i * 2 + 1], $points[$i * 2 + 2], $points[$i * 2 + 3],
		$color); 
	}
}

// draw a filled round-cornered rectangle
function imagefilledroundedrectangle($image  , $x1  , $y1  , $x2  , $y2  , $radius, $color)
{
	imagefilledrectangle($image, $x1,$y1+$radius, $x2,$y2-$radius, $color);
	imagefilledrectangle($image, $x1+$radius,$y1, $x2-$radius,$y2, $color);
	
	imagefilledarc($image, $x1+$radius, $y1+$radius, $radius*2, $radius*2, 0, 360, $color, IMG_ARC_PIE);
	imagefilledarc($image, $x2-$radius, $y1+$radius, $radius*2, $radius*2, 0, 360, $color, IMG_ARC_PIE);
	
	imagefilledarc($image, $x1+$radius, $y2-$radius, $radius*2, $radius*2, 0, 360, $color, IMG_ARC_PIE);
	imagefilledarc($image, $x2-$radius, $y2-$radius, $radius*2, $radius*2, 0, 360, $color, IMG_ARC_PIE);
	
	# bool imagefilledarc  ( resource $image  , int $cx  , int $cy  , int $width  , int $height  , int $start  , int $end  , int $color  , int $style  )
}

// draw a round-cornered rectangle
function imageroundedrectangle( $image  , $x1  , $y1  , $x2  , $y2  , $radius, $color )
{

	imageline($image, $x1+$radius, $y1, $x2-$radius, $y1, $color);
	imageline($image, $x1+$radius, $y2, $x2-$radius, $y2, $color);
	imageline($image, $x1, $y1+$radius, $x1, $y2-$radius, $color);
	imageline($image, $x2, $y1+$radius, $x2, $y2-$radius, $color);
	
	imagearc($image, $x1+$radius, $y1+$radius, $radius*2, $radius*2, 180, 270, $color);
	imagearc($image, $x2-$radius, $y1+$radius, $radius*2, $radius*2, 270, 360, $color);
	imagearc($image, $x1+$radius, $y2-$radius, $radius*2, $radius*2, 90, 180, $color);
	imagearc($image, $x2-$radius, $y2-$radius, $radius*2, $radius*2, 0, 90, $color);
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
				wm_warn("Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n");    
			}
			break;

		case IMAGETYPE_JPEG:
			if(imagetypes() & IMG_JPEG)
			{
				$bgimage=imagecreatefromjpeg($filename);
			}
			else
			{
				wm_warn("Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n");    
			}
			break;

		case IMAGETYPE_PNG:
			if(imagetypes() & IMG_PNG)
			{
				$bgimage=imagecreatefrompng($filename);
			}
			else
			{
				wm_warn("Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n");    
			}
			break;

		default:
			wm_warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
			break;
		}
	}
	else
	{
		wm_warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");    
	}
	return $bgimage;
}

// taken from here:
// http://www.php.net/manual/en/function.imagefilter.php#62395
// ( with some bugfixes and changes)
// 
// Much nicer colorization than imagefilter does, AND no special requirements.
// Preserves white, black and transparency.
//
function imagecolorize($im, $r, $g, $b)
{
    //We will create a monochromatic palette based on
    //the input color
    //which will go from black to white
    //Input color luminosity: this is equivalent to the
    //position of the input color in the monochromatic
    //palette
    $lum_inp = round(255 * ($r + $g + $b) / 765); //765=255*3

    //We fill the palette entry with the input color at its
    //corresponding position

    $pal[$lum_inp]['r'] = $r;
    $pal[$lum_inp]['g'] = $g;
    $pal[$lum_inp]['b'] = $b;

    //Now we complete the palette, first we'll do it to
    //the black,and then to the white.

    //FROM input to black
    //===================
    //how many colors between black and input
    $steps_to_black = $lum_inp;

    //The step size for each component
    if ($steps_to_black)
    {
        $step_size_red = $r / $steps_to_black;
        $step_size_green = $g / $steps_to_black;
        $step_size_blue = $b / $steps_to_black;
    }

    for ($i = $steps_to_black; $i >= 0; $i--)
    {
        $pal[$steps_to_black - $i]['r'] = $r - round($step_size_red * $i);
        $pal[$steps_to_black - $i]['g'] = $g - round($step_size_green * $i);
        $pal[$steps_to_black - $i]['b'] = $b - round($step_size_blue * $i);
    }

    //From input to white:
    //===================
    //how many colors between input and white
    $steps_to_white = 255 - $lum_inp;

    if ($steps_to_white)
    {
        $step_size_red = (255 - $r) / $steps_to_white;
        $step_size_green = (255 - $g) / $steps_to_white;
        $step_size_blue = (255 - $b) / $steps_to_white;
    }
    else
        $step_size_red = $step_size_green = $step_size_blue = 0;

    //The step size for each component
    for ($i = ($lum_inp + 1); $i <= 255; $i++)
    {
        $pal[$i]['r'] = $r + round($step_size_red * ($i - $lum_inp));
        $pal[$i]['g'] = $g + round($step_size_green * ($i - $lum_inp));
        $pal[$i]['b'] = $b + round($step_size_blue * ($i - $lum_inp));
    }

    //--- End of palette creation

    //Now,let's change the original palette into the one we
    //created
    for ($c = 0; $c < imagecolorstotal($im); $c++)
    {
        $col = imagecolorsforindex($im, $c);
        $lum_src = round(255 * ($col['red'] + $col['green'] + $col['blue']) / 765);
        $col_out = $pal[$lum_src];

   #     printf("%d (%d,%d,%d) -> %d -> (%d,%d,%d)\n", $c,
   #                $col['red'], $col['green'], $col['blue'],
   #                $lum_src,
   #                $col_out['r'], $col_out['g'], $col_out['b']
   #             );

        imagecolorset($im, $c, $col_out['r'], $col_out['g'], $col_out['b']);
    }
   
    return($im);
}

// find the point where a line from x1,y1 through x2,y2 crosses another line through x3,y3 and x4,y4
// (the point might not be between those points, but beyond them)
// - doesn't handle parallel lines. In our case we will never get them.
// - make sure we remove colinear points, or this will not be true!
function line_crossing($x1,$y1,$x2,$y2, $x3,$y3,$x4,$y4)
{
    
    // First, check that the slope isn't infinite.
    // if it is, tweak it to be merely huge
    if($x1 != $x2) { $slope1 = ($y2-$y1)/($x2-$x1); }
    else { $slope1 = 1e10; wm_debug("Slope1 is infinite.\n");}
    
    if($x3 != $x4) { $slope2 = ($y4-$y3)/($x4-$x3); }
    else { $slope2 = 1e10; wm_debug("Slope2 is infinite.\n");}
    
    $a1 = $slope1;
    $a2 = $slope2;
    $b1 = -1;
    $b2 = -1;   
    $c1 = ($y1 - $slope1 * $x1 );
    $c2 = ($y3 - $slope2 * $x3 );
    
    $det_inv = 1/($a1*$b2 - $a2*$b1);
    
    $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
    $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
    
    return(array($xi,$yi));
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
function calc_curve(&$in_xarray, &$in_yarray,$pointsperspan = 32)
{
	// search through the point list, for consecutive duplicate points
	// (most common case will be a straight link with both NODEs at the same place, I think)
	// strip those out, because they'll break the binary search/centre-point stuff

	$last_x=NULL;
	$last_y=NULL;

	for ($i=0; $i < count($in_xarray); $i++)
	{
		if (($in_xarray[$i] == $last_x) && ($in_yarray[$i] == $last_y)) { wm_debug
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
		wm_warn ("Arrow not drawn, as it's 1-dimensional.\n");
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

// Give a list of key points, calculate a "curve" through them
// return value is an array of triples (x,y,distance)
// this is here to mirror the real 'curve' version when we're using angled VIAs
// it means that all the stuff that expects an array of points with distances won't be upset.
function calc_straight(&$in_xarray, &$in_yarray,$pointsperspan = 12)
{
	// search through the point list, for consecutive duplicate points
	// (most common case will be a straight link with both NODEs at the same place, I think)
	// strip those out, because they'll break the binary search/centre-point stuff
	$last_x=NULL;
	$last_y=NULL;

	for ($i=0; $i < count($in_xarray); $i++)
	{
		if (($in_xarray[$i] == $last_x) && ($in_yarray[$i] == $last_y)) { wm_debug
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
		wm_warn ("Arrow not drawn, as it's 1-dimensional.\n");
		return (array(NULL, NULL, NULL, NULL));
	}

	$npoints=count($xarray);

	$curvepoints=array();

	$np=0;
	$distance=0;
	
	for ($i=0; $i < ($npoints -1); $i++)
	{
		// still subdivide the straight line, becuase other stuff makes assumptions about
		// how often there is a point - at least find_distance_coords_angle breaks
		$newdistance = sqrt( pow($xarray[$i+1] - $xarray[$i],2) + pow($yarray[$i+1] - $yarray[$i],2) );
		$dx = ($xarray[$i+1] - $xarray[$i])/$pointsperspan;
		$dy = ($yarray[$i+1] - $yarray[$i])/$pointsperspan;
		$dd = $newdistance/$pointsperspan;
		
		for($j=0; $j< $pointsperspan; $j++)
		{
			$x = $xarray[$i]+$j*$dx;
			$y = $yarray[$i]+$j*$dy;
			$d = $distance + $j*$dd;
			
			$curvepoints[] = array($x,$y,$d);
			$np++;
		}
		$distance += $newdistance;
	}
	$curvepoints[] = array($xarray[$npoints-1],$yarray[$npoints-1],$distance);

#	print_r($curvepoints);
	
	return ($curvepoints);
}

function calc_arrowsize($width,&$map,$linkname)
{
	$arrowlengthfactor=4;
	$arrowwidthfactor=2;

	// this is so I can use it in some test code - sorry!
	if($map !== NULL)
	{
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
	}

	$arrowsize = $width * $arrowlengthfactor;
	$arrowwidth = $width * $arrowwidthfactor;
	
	return( array($arrowsize,$arrowwidth) );
}

function draw_straight($image, &$curvepoints, $widths, $outlinecolour, $fillcolours, $linkname, &$map,
	$q2_percent=50, $unidirectional=FALSE)
{
	$totaldistance = $curvepoints[count($curvepoints)-1][DISTANCE];
		
	if($unidirectional)
	{
		$halfway = $totaldistance;
		$dirs = array(OUT);
		$q2_percent = 100;
		$halfway = $totaldistance * ($q2_percent/100);
		list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);
		
		$spine[OUT] = $curvepoints;
	}
	else
	{
	    // we'll split the spine in half here.
	  #  $q2_percent = 50;
	    $halfway = $totaldistance * ($q2_percent/100);
	    
	    $dirs = array(OUT,IN);
		# $dirs = array(IN);
	    
	    list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);
	#    print "Midpoint is: $totaldistance  $halfway  $halfwayindex   $halfway_x,$halfway_y\n";
	    	
	    $spine[OUT] = array();
	    $spine[IN] = array();
	    $npoints = count($curvepoints)-1;
	
	    for($i=0; $i<=$halfwayindex; $i++)
	    {
			$spine[OUT] []= $curvepoints[$i];
	    }
	    // finally, add the actual midpoint
	    $spine[OUT] []= array($halfway_x,$halfway_y, $halfway);
	    
	    // and then from the end to the middle for the other arrow
	    for($i=$npoints; $i>$halfwayindex; $i--)
	    {
			// copy the original spine, but reversing the distance calculation
			$spine[IN] []= array($curvepoints[$i][X], $curvepoints[$i][Y], $totaldistance - $curvepoints[$i][DISTANCE]);
	    }
	    // finally, add the actual midpoint
	    $spine[IN] []= array($halfway_x,$halfway_y, $totaldistance - $halfway);
	}
	
	# wm_draw_marker_box($image,$map->selected, $halfway_x, $halfway_y );
	
	// now we have two seperate spines, with distances, so that the arrowhead is the end of each.
	// (or one, if it's unidir)
	
	// so we can loop along the spine for each one as a seperate entity
	
	// we calculate the arrow size up here, so that we can decide on the
	// minimum length for a link. The arrowheads are the limiting factor.
	list( $arrowsize[IN], $arrowwidth[IN] ) = calc_arrowsize( $widths[IN], $map, $linkname );
	list( $arrowsize[OUT], $arrowwidth[OUT] ) = calc_arrowsize( $widths[OUT], $map, $linkname );
	
	// the 1.2 here is empirical. It ought to be 1 in theory.
	// in practice, a link this short is useless anyway, especially with bwlabels.
	$minimumlength = 1.2*($arrowsize[IN]+$arrowsize[OUT]);

	foreach ($dirs as $dir)
	{
		# draw_spine($image, $spine[$dir],$map->selected);
		#draw_spine_chain($image, $spine[$dir],$map->selected,3);
		#print "=================\n$linkname/$dir\n";
		#dump_spine($spine[$dir]);
	    $n = count($spine[$dir]) - 1;
	    $l = $spine[$dir][$n][DISTANCE];
		
		#print "L=$l N=$n\n";
	
	    // loop increment, start point, width, labelpos, fillcolour, outlinecolour, commentpos    
	    $arrowsettings = array(+1, 0, $widths[$dir], 0, $fillcolours[$dir], $outlinecolour, 5);
	    
	    # print "Line is $n points to a distance of $l\n";
	    if($l < $minimumlength)
	    {
			wm_warn("Skipping too-short line.\n");
	    }
	    else
		{			
			$arrow_d = $l - $arrowsize[$dir];
			# print "LENGTHS $l $arrow_d ".$arrowsize[$dir]."\n";
			list($pre_mid_x,$pre_mid_y,$pre_midindex) = find_distance_coords($spine[$dir], $arrow_d);
			# print "POS $pre_mid_x,$pre_mid_y  $pre_midindex\n";
			$out = array_slice($spine[$dir], 0, $pre_midindex);
			$out []= array($pre_mid_x, $pre_mid_y, $arrow_d);
			
			# wm_draw_marker_diamond($image, $map->selected, $pre_mid_x, $pre_mid_y, 5);
			# imagearc($image,$pre_mid_x, $pre_mid_y ,15,15,0,360,$map->selected);
			
			# imagearc($image,$spine[$dir][$pre_midindex+1][X],$spine[$dir][$pre_midindex+1][Y],20,20,0,360,$map->selected);
			# imagearc($image,$spine[$dir][$pre_midindex][X],$spine[$dir][$pre_midindex][Y],20,20,0,360,$map->selected);
			#imagearc($image,$pre_mid_x,$pre_mid_y,20,20,0,360,$map->selected);
			#imagearc($image,$spine[$dir][$pre_midindex][X],$spine[$dir][$pre_midindex][Y],12,12,0,360,$map->selected);
						
			$spine[$dir] = $out;
			
			$adx=($halfway_x - $pre_mid_x);
			$ady=($halfway_y - $pre_mid_y);
			$ll=sqrt(($adx * $adx) + ($ady * $ady));
		
			$anx = $ady / $ll;
			$any = -$adx / $ll;
			
			$ax1 = $pre_mid_x + $widths[$dir] * $anx;
			$ay1 = $pre_mid_y + $widths[$dir] * $any;
		
			$ax2 = $pre_mid_x + $arrowwidth[$dir] * $anx;
			$ay2 = $pre_mid_y + $arrowwidth[$dir] * $any;
			
			$ax3 = $halfway_x;
			$ay3 = $halfway_y;
			
			$ax5 = $pre_mid_x - $widths[$dir] * $anx;
			$ay5 = $pre_mid_y - $widths[$dir] * $any;
		
			$ax4 = $pre_mid_x - $arrowwidth[$dir] * $anx;
			$ay4 = $pre_mid_y - $arrowwidth[$dir] * $any;             
			
			# draw_spine($image,$spine[$dir],$map->selected);
						
			$simple = simplify_spine($spine[$dir]);
			$newn = count($simple);	
			
			# draw_spine($image,$simple,$map->selected);
			
			# print "Simplified to $newn points\n";
			# if($draw_skeleton) draw_spine_chain($im,$simple,$blue, 12);
			# draw_spine_chain($image,$simple,$map->selected, 12);
			 # draw_spine_chain($image,$spine[$dir],$map->selected, 10);
		
			# draw_spine_chain($image,$simple,$map->selected, 12);
			# draw_spine($image,$simple,$map->selected);
			
			// now do the actual drawing....
				
			$numpoints=0;
			$numrpoints=0;
			
			$finalpoints = array();
			$reversepoints = array();
			
			$finalpoints[] = $simple[0][X];
			$finalpoints[] = $simple[0][Y];
			$numpoints++;
			
			$reversepoints[] = $simple[0][X];
			$reversepoints[] = $simple[0][Y];
			$numrpoints++;
			
			// before the main loop, add in the jump out to the corners
			// if this is the first step, then we need to go from the middle to the outside edge first
			// ( the loop may not run, but these corners are required)
			$i = 0;
			$v1 = new Vector($simple[$i+1][X] - $simple[$i][X], $simple[$i+1][Y] - $simple[$i][Y]);
			$n1 = $v1->get_normal();
						
			$finalpoints[] = $simple[$i][X] + $n1->dx*$widths[$dir];
			$finalpoints[] = $simple[$i][Y] + $n1->dy*$widths[$dir];
			$numpoints++;
			
			$reversepoints[] = $simple[$i][X] - $n1->dx*$widths[$dir];
			$reversepoints[] = $simple[$i][Y] - $n1->dy*$widths[$dir];
			$numrpoints++;
			
			$max_start = count($simple)-2;
			# print "max_start is $max_start\n";
			for ($i=0; $i <$max_start; $i++)
			{       
				$v1 = new Vector($simple[$i+1][X] - $simple[$i][X], $simple[$i+1][Y] - $simple[$i][Y]);
				$v2 = new Vector($simple[$i+2][X] - $simple[$i+1][X], $simple[$i+2][Y] - $simple[$i+1][Y]);
				$n1 = $v1->get_normal();
				$n2 = $v2->get_normal();
				
				$capping = FALSE;
				// figure out the angle between the lines - for very sharp turns, we should do something special
				// (actually, their normals, but the angle is the same and we need the normals later)
				$angle = rad2deg(atan2($n2->dy,$n2->dx) - atan2($n1->dy,$n1->dx));
				if($angle > 180) $angle -= 360;
				if($angle < -180) $angle += 360;
			
				if(abs($angle)>169)
				{
					$capping = TRUE;
					# print "Would cap. ($angle)\n";
				}
				
				// $capping = FALSE; // override that for now           
				// now figure out the geometry for where the next corners are
				
				list($xi1,$yi1) = line_crossing( $simple[$i][X] + $n1->dx * $widths[$dir], $simple[$i][Y] + $n1->dy * $widths[$dir],
								$simple[$i+1][X] + $n1->dx * $widths[$dir], $simple[$i+1][Y] + $n1->dy * $widths[$dir],
								$simple[$i+1][X] + $n2->dx * $widths[$dir], $simple[$i+1][Y] + $n2->dy * $widths[$dir],
								$simple[$i+2][X] + $n2->dx * $widths[$dir], $simple[$i+2][Y] + $n2->dy * $widths[$dir]
							);
			
				list($xi2,$yi2) = line_crossing( $simple[$i][X] - $n1->dx * $widths[$dir], $simple[$i][Y] - $n1->dy * $widths[$dir],
							$simple[$i+1][X] - $n1->dx * $widths[$dir], $simple[$i+1][Y] - $n1->dy * $widths[$dir],
							$simple[$i+1][X] - $n2->dx * $widths[$dir], $simple[$i+1][Y] - $n2->dy * $widths[$dir],
							$simple[$i+2][X] - $n2->dx * $widths[$dir], $simple[$i+2][Y] - $n2->dy * $widths[$dir]                                
							);           
				
				if(!$capping)
				{
					$finalpoints[] = $xi1;
					$finalpoints[] = $yi1;
					$numpoints++;
						
					$reversepoints[] = $xi2;
					$reversepoints[] = $yi2;
					$numrpoints++;                
				}
				else
				{
				// in here, we need to decide which is the 'outside' of the corner,
				// because that's what we flatten. The inside of the corner is left alone.
				// - depending on the relative angle between the two segments, it could
				//   be either one of these points.
				
				list($xi3,$yi3) = line_crossing( $simple[$i][X] + $n1->dx*$widths[$dir], $simple[$i][Y] + $n1->dy*$widths[$dir],
							$simple[$i+1][X] + $n1->dx*$widths[$dir], $simple[$i+1][Y] + $n1->dy*$widths[$dir],
							$simple[$i+1][X] - $n2->dx*$widths[$dir], $simple[$i+1][Y] - $n2->dy*$widths[$dir],
							$simple[$i+2][X] - $n2->dx*$widths[$dir], $simple[$i+2][Y] - $n2->dy*$widths[$dir]                                
							);
			
				list($xi4,$yi4) = line_crossing( $simple[$i][X] - $n1->dx*$widths[$dir], $simple[$i][Y] - $n1->dy*$widths[$dir],
							$simple[$i+1][X] - $n1->dx*$widths[$dir], $simple[$i+1][Y] - $n1->dy*$widths[$dir],
							$simple[$i+1][X] + $n2->dx*$widths[$dir], $simple[$i+1][Y] + $n2->dy*$widths[$dir],
							$simple[$i+2][X] + $n2->dx*$widths[$dir], $simple[$i+2][Y] + $n2->dy*$widths[$dir]                                
							);                
				if($angle < 0)
				{
					$finalpoints[] = $xi3;
					$finalpoints[] = $yi3;
					$numpoints++;
					
					$finalpoints[] = $xi4;
					$finalpoints[] = $yi4;
					$numpoints++;
					
					$reversepoints[] = $xi2;
					$reversepoints[] = $yi2;
					$numrpoints++;
				}
				else
				{
					$reversepoints[] = $xi4;
					$reversepoints[] = $yi4;
					$numrpoints++;
					
					$reversepoints[] = $xi3;
					$reversepoints[] = $yi3;
					$numrpoints++;
					
					$finalpoints[] = $xi1;
					$finalpoints[] = $yi1;
					$numpoints++;
				}
				
				}
			}
		  
			// at this end, we add the arrowhead
			
			$finalpoints[] = $ax1;
			$finalpoints[] = $ay1;
			$finalpoints[] = $ax2;
			$finalpoints[] = $ay2;
			$finalpoints[] = $ax3;
			$finalpoints[] = $ay3;
			$finalpoints[] = $ax4;
			$finalpoints[] = $ay4;
			$finalpoints[] = $ax5;
			$finalpoints[] = $ay5;
			
			$numpoints += 5;
		
			// combine the forwards and backwards paths, to make a complete loop
			for($i=($numrpoints-1)*2; $i>=0; $i-=2)
			{
				$x = $reversepoints[$i];
				$y = $reversepoints[$i+1];
				
				$finalpoints[] = $x;
				$finalpoints[] = $y;
				$numpoints++;
			}
			// $finalpoints[] contains a complete outline of the line at this stage

			// round to the nearest integer (up OR down). We do this now
			// so that GD doesn't just round everything down and make straight lines slightly off
			for ($i=0; $i<sizeof($finalpoints); $i++) {
				$finalpoints[$i] = round($finalpoints[$i]);
			}

			if (!is_null($fillcolours[$dir]))
			{
				wimagefilledpolygon($image, $finalpoints, count($finalpoints) / 2, $arrowsettings[4]); 
			}
			else
			{
				wm_debug("Not drawing $linkname ($dir) fill because there is no fill colour\n");
			}
			
			$areaname = "LINK:L" . $map->links[$linkname]->id . ":$dir";
			$map->imap->addArea("Polygon", $areaname, '', $finalpoints);
			wm_debug ("Adding Poly imagemap for $areaname\n");
		
			if (!is_null($outlinecolour))
			{
				wimagepolygon($image, $finalpoints, count($finalpoints) / 2, $arrowsettings[5]);
			}
			else
			{
				wm_debug("Not drawing $linkname ($dir) outline because there is no outline colour\n");
			}
	    }
	}
}

// top-level function that takes a two lists to define some points, and draws a weathermap link
// - this takes care of all the extras, like arrowheads, and where to put the bandwidth labels
//    curvepoints is an array of the points the curve passes through
//    width is the link width (the actual width is twice this)
//    outlinecolour is a GD colour reference
//    fillcolours is an array of two more colour references, one for the out, and one for the in spans
function draw_curve($image, &$curvepoints, $widths, $outlinecolour, $fillcolours, $linkname, &$map,
	$q2_percent=50, $unidirectional=FALSE)
{
	// now we have a 'spine' - all the central points for this curve.
	// time to flesh it out to the right width, and figure out where to draw arrows and bandwidth boxes...
		
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
	$arrowsettings[OUT] = array(+1, 0, $widths[OUT], 0, $fillcolours[OUT], $outlinecolour, 5);
	$arrowsettings[IN] = array(-1, count($curvepoints) - 1, $widths[IN], 0, $fillcolours[IN], $outlinecolour, 95);

	// we calculate the arrow size up here, so that we can decide on the
	// minimum length for a link. The arrowheads are the limiting factor.
	list($arrowsize[IN],$arrowwidth[IN]) = calc_arrowsize($widths[IN], $map, $linkname);
	list($arrowsize[OUT],$arrowwidth[OUT]) = calc_arrowsize($widths[OUT], $map, $linkname);
			
	// the 1.2 here is empirical. It ought to be 1 in theory.
	// in practice, a link this short is useless anyway, especially with bwlabels.
	$minimumlength = 1.2*($arrowsize[IN]+$arrowsize[OUT]);

	# warn("$linkname: Total: $totaldistance $arrowsize $arrowwidth $minimumlength\n");
	if($totaldistance <= $minimumlength)
	{
		wm_warn("Skipping drawing very short link ($linkname). Impossible to draw! Try changing WIDTH or ARROWSTYLE? [WMWARN01]\n");
		return;
	}

	
	list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);

	// loop over direction here
	// direction is 1.0 for the first half (forwards through the pointlist), and -1.0 for the second half (backwards from the end)
	//  - used as a multiplier on anything that looks forwards or backwards through the list

	foreach ($dirs as $dir)
	{
		$direction = $arrowsettings[$dir][0];
		// $width = $widths[$dir];
		// this is the last index before the arrowhead starts
		list($pre_mid_x,$pre_mid_y,$pre_midindex) = find_distance_coords($curvepoints,$halfway - $direction * $arrowsize[$dir]);
		
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

			$there_points[]=$curvepoints[$i][0] + $direction * $widths[$dir] * $nx;
			$there_points[]=$curvepoints[$i][1] + $direction * $widths[$dir] * $ny;

			$back_points[]=$curvepoints[$i][0] - $direction * $widths[$dir] * $nx;
			$back_points[]=$curvepoints[$i][1] - $direction * $widths[$dir] * $ny;
		}

		// all the normal line is done, now lets add an arrowhead on

		$adx=($halfway_x - $pre_mid_x);
		$ady=($halfway_y - $pre_mid_y);
		$l=sqrt(($adx * $adx) + ($ady * $ady));

		$anx=$ady / $l;
		$any=-$adx / $l;

		$there_points[]=$pre_mid_x + $direction * $widths[$dir] * $anx;
		$there_points[]=$pre_mid_y + $direction * $widths[$dir] * $any;

		$there_points[]=$pre_mid_x + $direction * $arrowwidth[$dir] * $anx;
		$there_points[]=$pre_mid_y + $direction * $arrowwidth[$dir] * $any;

		$there_points[]=$halfway_x;
		$there_points[]=$halfway_y;

		$there_points[]=$pre_mid_x - $direction * $arrowwidth[$dir] * $anx;
		$there_points[]=$pre_mid_y - $direction * $arrowwidth[$dir] * $any;

		$there_points[]=$pre_mid_x - $direction * $widths[$dir] * $anx;
		$there_points[]=$pre_mid_y - $direction * $widths[$dir] * $any;

		// all points done, now combine the lists, and produce the final result.
		$metapts = "";
		$y=array_pop($back_points);
		$x=array_pop($back_points);
		do
		{
			$metapts .= " $x $y";
			$there_points[]=$x;
			$there_points[]=$y;
			$y=array_pop($back_points);
			$x=array_pop($back_points);
		} while (!is_null($y));

		$arrayindex=1;

		if ($direction < 0) $arrayindex=0;

		if (!is_null($fillcolours[$arrayindex]))
		{
			wimagefilledpolygon($image, $there_points, count($there_points) / 2, $arrowsettings[$dir][4]); 
		}
		else
		{
			wm_debug("Not drawing $linkname ($dir) fill because there is no fill colour\n");
		}
		
		# $areaname = "LINK:" . $linkname. ":$dir";
		$areaname = "LINK:L" . $map->links[$linkname]->id . ":$dir";
		$map->imap->addArea("Polygon", $areaname, '', $there_points);
		wm_debug ("Adding Poly imagemap for $areaname\n");

		if (!is_null($outlinecolour))
		{
			wimagepolygon($image, $there_points, count($there_points) / 2, $arrowsettings[$dir][5]);
		}
		else
		{
			wm_debug("Not drawing $linkname ($dir) outline because there is no outline colour\n");
		}
	}
}

// Take a spine, and strip out all the points that are co-linear with the points either side of them
function simplify_spine(&$input, $epsilon=1e-10)
{   
    $output = array();
    
    $output []= $input[0];
    $n=1;
    $c = count($input)-2;
    $skip=0;
    
    for($n=1; $n<=$c; $n++)
    {
	$x = $input[$n][X];
	$y = $input[$n][Y];
	
	// figure out the area of the triangle formed by this point, and the one before and after
	$a = 	abs($input[$n-1][X] * ( $input[$n][Y] - $input[$n+1][Y] )
		+ $input[$n][X] * ( $input[$n+1][Y] - $input[$n-1][Y] )
		+ $input[$n+1][X] * ( $input[$n-1][Y] - $input[$n][Y] ) );
	
	# print "$n  $x,$y    $a";
	
        if ( $a > $epsilon)
	// if(1==1)
        {
            $output []= $input[$n];
	#    print "  KEEP";
        }
        else
        {
            // ignore n
            $skip++;
	#    print "  SKIP";
            
        }
	# print "\n";
    }
        
    wm_debug("Skipped $skip points of $c\n");
    
#    print "------------------------\n";
    
    $output []= $input[$c+1];
    return $output;
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
		wm_debug("Numeric Offset found\n");
		return(array($matches[1],$matches[2]));
	}
	elseif(preg_match("/(NE|SE|NW|SW|N|S|E|W|C)(\d+)?$/i",$offsetstring,$matches))
	{
		$multiply = 1;
		if( isset($matches[2] ) )
		{
			$multiply = intval($matches[2])/100;
			wm_debug("Percentage compass offset: multiply by $multiply");
		}
	
		$height = $height * $multiply;
		$width = $width * $multiply;
	
		switch (strtoupper($matches[1]))
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

		case 'C':
		default:
			return (array(0, 0));

			break;
		}
	}
	elseif( preg_match("/(-?\d+)r(\d+)$/i",$offsetstring,$matches) )
	{
		$angle = intval($matches[1]);
		$distance = intval($matches[2]);
		
		$x = $distance * sin(deg2rad($angle));
		$y = - $distance * cos(deg2rad($angle));
				
		return (array($x,$y));
		
	}
	else
	{
		wm_warn("Got a position offset that didn't make sense ($offsetstring).");
		return (array(0, 0));
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

	$mega=$kilo * $kilo;
	$giga=$mega * $kilo;
	$tera=$giga * $kilo;

        $milli = 1/$kilo;
	$micro = 1/$mega;
	$nano = 1/$giga;

	if ($number >= $tera)
	{
		$number/=$tera;
		$suffix="T";
	}
	elseif ($number >= $giga)
	{
		$number/=$giga;
		$suffix="G";
	}
	elseif ($number >= $mega)
	{
		$number/=$mega;
		$suffix="M";
	}
	elseif ($number >= $kilo)
	{
		$number/=$kilo;
		$suffix="K";
	}
        elseif ($number >= 1)
        {
                $number = $number;
                $suffix="";
        }
	elseif (($below_one==TRUE) && ($number >= $milli))
	{
		$number/=$milli;
		$suffix="m";
	}
	elseif (($below_one==TRUE) && ($number >= $micro))
	{
		$number/=$micro;
		$suffix="u";
	}
	elseif (($below_one==TRUE) && ($number >= $nano))
	{
		$number/=$nano;
		$suffix="n";
	}

	$result=format_number($number, $decimals) . $suffix;
	return ($result);
}

function nice_scalar($number, $kilo = 1000, $decimals=1)
{
	$suffix = '';
	$prefix = '';
	
	if ($number == 0)
		return '0';
		
	if($number < 0)
	{
		$number = -$number;
		$prefix = '-';
	}

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

	$result = $prefix . format_number($number, $decimals) . $suffix;
	return ($result);
}


// ***********************************************

// Skeleton class just to keep strict mode quiet. 
class WMFont
{
	var $type;
	var $file;
	var $gdnumber;
	var $size;
}

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
	
	function get_normal()
	{
		$len = $this->length();
		
		$nx1 = $this->dy / $len;
		$ny1 = -$this->dx / $len;
		
		return( new Vector($nx1, $ny1));
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
	var $r,$g,$b, $alpha;
	
	
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
	function is_real()
	{
		if($this->r >= 0 && $this->g >=0 && $this->b >= 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// Is this a transparent/none colour?
	function is_none()
	{
		if($this->r == -1 && $this->g == -1 && $this->b == -1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// Is this a contrast colour?
	function is_contrast()
	{
		if($this->r == -3 && $this->g == -3 && $this->b == -3)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// Is this a copy colour?
	function is_copy()
	{
		if($this->r == -2 && $this->g == -2 && $this->b == -2)
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
		if($this->is_none())
		{
			return NULL;
		}
		else
		{
			return(myimagecolorallocate($image_ref, $this->r, $this->g, $this->b));
		}
	}
	
	// based on an idea from: http://www.bennadel.com/index.cfm?dax=blog:902.view
	function contrast_ary()
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
	
	function contrast()
	{
		return( new Colour($this->contrast_ary() ) );
	}
	
	// make a printable version, for debugging
	// - optionally take a format string, so we can use it for other things (like WriteConfig, or hex in stylesheets)
	function as_string($format = "RGB(%d,%d,%d)")
	{
		return (sprintf($format, $this->r, $this->g, $this->b));
	}

	function __toString()
	{
		return $this->as_string();
	}
	
	function as_config()
	{
		return $this->as_string("%d %d %d");
	}
	
	function as_html()
	{
		if($this->is_real())
		{
			return $this->as_string("#%02x%02x%02x");
		}
		else
		{
			return "";
		}
	}
}

// A series of wrapper functions around all the GD function calls
// - I added these in so I could make a 'metafile' easily of all the
//   drawing commands for a map. I have a basic Perl-Cairo script that makes
//   anti-aliased maps from these, using Cairo instead of GD.

function metadump($string, $truncate=FALSE)
{
	// comment this line to get a metafile for this map
	return;

	if($truncate)
	{
		$fd = fopen("metadump.txt","w+");
	}
	else
	{
		$fd = fopen("metadump.txt","a");
	}
	fputs($fd,$string."\n");
	fclose($fd);
}

function metacolour(&$col)
{
	return ($col['red1']." ".$col['green1']." ".$col['blue1']);
}

function wimagecreate($width,$height)
{
	metadump("NEWIMAGE $width $height");
	return(imagecreate($width,$height));
}

function wimagefilledrectangle( $image ,$x1, $y1, $x2, $y2, $color )
{
	if ($color===NULL) return;
	
	$col = imagecolorsforindex($image, $color);
	$r = $col['red']; $g = $col['green']; $b = $col['blue']; $a = $col['alpha'];
	$r = $r/255; $g=$g/255; $b=$b/255; $a=(127-$a)/127;

	metadump("FRECT $x1 $y1 $x2 $y2 $r $g $b $a");
	return(imagefilledrectangle( $image ,$x1, $y1, $x2, $y2, $color ));
}

function wimagerectangle( $image ,$x1, $y1, $x2, $y2, $color )
{
	if ($color===NULL) return;
	
	$col = imagecolorsforindex($image, $color);
	$r = $col['red']; $g = $col['green']; $b = $col['blue']; $a = $col['alpha'];
	$r = $r/255; $g=$g/255; $b=$b/255; $a=(127-$a)/127;

	metadump("RECT $x1 $y1 $x2 $y2 $r $g $b $a");
	return(imagerectangle( $image ,$x1, $y1, $x2, $y2, $color ));
}

function wimagepolygon($image, $points, $num_points, $color)
{
	if ($color===NULL) return;
	
	$col = imagecolorsforindex($image, $color);
	$r = $col['red']; $g = $col['green']; $b = $col['blue']; $a = $col['alpha'];
	$r = $r/255; $g=$g/255; $b=$b/255; $a=(127-$a)/127;
	
	$pts = "";
	for ($i=0; $i < $num_points; $i++)
        {
		$pts .= $points[$i * 2]." ";
		$pts .= $points[$i * 2+1]." ";
        }
	
	metadump("POLY $num_points ".$pts." $r $g $b $a");

	return(imagepolygon($image, $points, $num_points, $color));
}

function wimagefilledpolygon($image, $points, $num_points, $color)
{
	if ($color===NULL) return;
	
	$col = imagecolorsforindex($image, $color);
	$r = $col['red']; $g = $col['green']; $b = $col['blue']; $a = $col['alpha'];
	$r = $r/255; $g=$g/255; $b=$b/255; $a=(127-$a)/127;
	
	$pts = "";
	for ($i=0; $i < $num_points; $i++)
        {
		$pts .= $points[$i * 2]." ";
		$pts .= $points[$i * 2+1]." ";
        }
	
	metadump("FPOLY $num_points ".$pts." $r $g $b $a");

	return(imagefilledpolygon($image, $points, $num_points, $color));
}

function wimagecreatetruecolor($width, $height)
{
	

	metadump("BLANKIMAGE $width $height");

	return imagecreatetruecolor($width,$height);

}

function wimagettftext($image, $size, $angle, $x, $y, $color, $file, $string)
{
	if ($color===NULL) return;

	$col = imagecolorsforindex($image, $color);
	$r = $col['red']; $g = $col['green']; $b = $col['blue']; $a = $col['alpha'];
	$r = $r/255; $g=$g/255; $b=$b/255; $a=(127-$a)/127;

	metadump("TEXT $x $y $angle $size $file $r $g $b $a $string");

	return(imagettftext($image, $size, $angle, $x, $y, $color, $file, $string));
}

function wm_draw_marker_diamond($im, $col, $x, $y, $size=10)
{
	$points = array();
	
	$points []= $x-$size;
	$points []= $y;
	
	$points []= $x;
	$points []= $y-$size;
	
	$points []= $x+$size;
	$points []= $y;
	
	$points []= $x;
	$points []= $y+$size;
		
	$num_points = 4;

	imagepolygon($im, $points, $num_points, $col);
}

function wm_draw_marker_box($im, $col, $x, $y, $size=10)
{
	$points = array();
	
	$points []= $x-$size;
	$points []= $y-$size;
	
	$points []= $x+$size;
	$points []= $y-$size;
	
	$points []= $x+$size;
	$points []= $y+$size;
	
	$points []= $x-$size;
	$points []= $y+$size;
		
	$num_points = 4;

	imagepolygon($im, $points, $num_points, $col);
}

function wm_draw_marker_circle($im, $col, $x, $y, $size=10)
{
	imagearc($im,$x, $y ,$size,$size,0,360,$col);
}

function draw_spine_chain($im,$spine,$col, $size=10)
{
    $newn = count($spine);
        
    for ($i=0; $i < $newn; $i++)
    {   
		imagearc($im,$spine[$i][X],$spine[$i][Y],$size,$size,0,360,$col);
    }
}

function dump_spine($spine)
{
	print "===============\n";
	for($i=0; $i<count($spine); $i++)
	{
		printf ("  %3d: %d,%d (%d)\n", $i, $spine[$i][X], $spine[$i][Y], $spine[$i][DISTANCE] );		
	}
	print "===============\n";
}

function draw_spine($im, $spine,$col)
{
    $max_i = count($spine)-1;
    
    for ($i=0; $i <$max_i; $i++)
    {
        imageline($im,
                    $spine[$i][X],$spine[$i][Y],
                    $spine[$i+1][X],$spine[$i+1][Y],
                    $col
                    );
    }
}

/**
 * A duplicate of the HTML output code in the weathermap CLI utility,
 * for use by the test-output stuff.
 *
 * @global string $WEATHERMAP_VERSION
 * @param string $htmlfile
 * @param WeatherMap $map
 */
    function TestOutput_HTML($htmlfile, &$map)
    {
        global $WEATHERMAP_VERSION;

        $fd=fopen($htmlfile, 'w');
        fwrite($fd,
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>');
        if($map->htmlstylesheet != '') fwrite($fd,'<link rel="stylesheet" type="text/css" href="'.$map->htmlstylesheet.'" />');
        fwrite($fd,'<meta http-equiv="refresh" content="300" /><title>' . $map->ProcessString($map->title, $map) . '</title></head><body>');

        if ($map->htmlstyle == "overlib")
        {
                fwrite($fd,
                        "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n");
                fwrite($fd,
                        "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n");
        }

        fwrite($fd, $map->MakeHTML());
        fwrite($fd,
                '<hr /><span id="byline">Network Map created with <a href="http://www.network-weathermap.com/?vs='
                . $WEATHERMAP_VERSION . '">PHP Network Weathermap v' . $WEATHERMAP_VERSION
                . '</a></span></body></html>');
        fclose ($fd);
    }

	    /**
     * Run a config-based test.
     * Read in config from $conffile, and produce an image and HTML output
     * Optionally Produce a new config file in $newconffile (for testing WriteConfig)
     * Optionally collect config-keyword-coverage stats about this config file
     *
     *
     *
     * @param string $conffile
     * @param string $imagefile
     * @param string $htmlfile
     * @param string $newconffile
     * @param string $coveragefile
     */

    function TestOutput_RunTest($conffile, $imagefile, $htmlfile, $newconffile, $coveragefile)
    {
        global $weathermap_map;
	global $WEATHERMAP_VERSION;

        $map = new WeatherMap();
        if($coveragefile != '') {
            $map->SeedCoverage();
            if(file_exists($coveragefile) ) {
                $map->LoadCoverage($coveragefile);
            }
        }
        $weathermap_map = $conffile;
        $map->ReadConfig($conffile);
	$skip = 0;
	$nwarns = 0;

	if( ! strstr($WEATHERMAP_VERSION, "dev" )) {
		# Allow tests to be from the future. Global SET in test file can excempt test from running
		# SET REQUIRES_VERSION 0.98
		# but don't check if the current version is a dev version
		$required_version = $map->get_hint("REQUIRES_VERSION");

		if($required_version != "") {	
			// doesan't need to be complete, just in the right order
			$known_versions = array("0.97","0.97a","0.97b","0.98");
			$my_version = array_search($WEATHERMAP_VERSION,$known_versions);	
			$req_version = array_search($required_version,$known_versions);	
			if($req_version > $my_version) {
				$skip = 1;
				$nwarns = -1;
			}
		}
	}

	if( $skip == 0) {
       		$map->ReadData();
       		$map->DrawMap($imagefile);
        	$map->imagefile=$imagefile;
        	if($htmlfile != '') {
        	    TestOutput_HTML($htmlfile, $map);
        	}
        	if($newconffile != '') {
        	    $map->WriteConfig($newconffile);
        	}
        	if($coveragefile != '') {
        	    $map->SaveCoverage($coveragefile);
        	}
        	$nwarns = $map->warncount;
	}
	
        $map->CleanUp();
        unset ($map);

        return intval($nwarns);
    }

	

// vim:ts=4:sw=4:
