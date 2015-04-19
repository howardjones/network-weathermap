<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

// Utility functions
// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
function wm_module_checks()
{
    if (! extension_loaded('gd')) {
        wm_warn("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");
        
        return false;
    }
    
    if (! function_exists('imagecreatefrompng')) {
        wm_warn("Your GD php module doesn't support PNG format. [WMWARN21]\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");
        return false;
    }
    
    if (! function_exists('imagecreatetruecolor')) {
        wm_warn("Your GD php module doesn't support truecolor. [WMWARN22]\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");
        return false;
    }
    
    if (! function_exists('imagecopyresampled')) {
        wm_warn("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
    }
    return true;
}

/**
 * central point for all debug logging, whether in the
 * standalone or Cacti parts of the tool.
 *
 * @global boolean $weathermap_debugging
 * @global string $weathermap_map
 * @global boolean $weathermap_debug_suppress
 * @param string $string
 *            The actual message to be logged
 * @param
 *            string... the first string is treated as a sprintf format string.
 *            Following params are fed to sprintf()
 */
function wm_debug($string)
{
    global $weathermap_debugging;
    global $weathermap_debugging_readdata;
    global $weathermap_map;
    global $weathermap_debug_suppress;
    
    if ($weathermap_debugging_readdata) {
        $is_readdata = false;
        
        if (false !== strpos("ReadData", $string)) {
            $is_readdata = true;
        }
    }
    
    if ($weathermap_debugging || ($weathermap_debugging_readdata && $is_readdata)) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $string = call_user_func_array('sprintf', $args);
        }
        
        $calling_fn = "";
        if (function_exists("debug_backtrace")) {
            $backtrace = debug_backtrace();
            $index = 1;
            
            $function = (true === isset($backtrace[$index]['function'])) ? $backtrace[$index]['function'] : '';
            $index = 0;
            $file = (true === isset($backtrace[$index]['file'])) ? basename($backtrace[$index]['file']) : '';
            $line = (true === isset($backtrace[$index]['line'])) ? $backtrace[$index]['line'] : '';
            
            $calling_fn = " [$function@$file:$line]";
            
            if (is_array($weathermap_debug_suppress) && in_array(strtolower($function), $weathermap_debug_suppress)) {
                return;
            }
        }
        
        // use Cacti's debug log, if we are running from the poller
        if (function_exists('debug_log_insert') && (! function_exists('wmeShowStartPage'))) {
            cacti_log("DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim($string), true, "WEATHERMAP");
        } else {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . $string);
            fclose($stderr);
            
            // mostly this is overkill, but it's sometimes useful (mainly in the editor)
            if (1 == 0) {
                $log = fopen('debug.log', 'a');
                fwrite($log, "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . $string);
                fclose($log);
            }
        }
    }
}

function wm_warn($string, $notice_only = false, $code = "")
{
    global $weathermap_map;
    global $weathermap_warncount;
    global $weathermap_error_suppress;
    
    $message = "";

    if (preg_match('/\[(WM\w+)\]/', $string, $matches)) {
        $code = $matches[1];
    }
    
    if ((true === is_array($weathermap_error_suppress)) && (true === in_array(strtoupper($code), $weathermap_error_suppress))) {
        // This error code has been deliberately disabled.
        return;
    }
    
    if (! $notice_only) {
        $weathermap_warncount ++;
        $message .= "WARNING: ";
    }
    
    $message .= ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim($string);
    
    // use Cacti's debug log, if we are running from the poller
    if (function_exists('cacti_log') && (! function_exists('wmeShowStartPage'))) {
        cacti_log($message, true, "WEATHERMAP");
    } else {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, $message . "\n");
        fclose($stderr);
    }
}

function wm_value_or_null($value)
{
    return ($value === null ? '{null}' : $value);
}

/** Die, leaving a stack-trace. Mostly used along with the unit tests to figure out if
 *  a piece of old-looking code is really redundant.
 *
 * @param string $message message to be show in exception
 * @throws WMException that's ALL it does
 */
function wm_die_with_trace($message = "Dying here")
{
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    throw new WMException($message);
}

function jsEscape($str)
{
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace('"', '\\"', $str);

    $str = '"' . $str . '"';

    return ($str);
}

function wmFormatTimeTicks($value, $prefix, $tokenCharacter, $precision)
{
    $joinCharacter = " ";
    if ($prefix == "-") {
        $joinCharacter = "";
    }

    // special formatting for time_t (t) and SNMP TimeTicks (T)
    if ($tokenCharacter == "T") {
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
    foreach ($periods as $periodSuffix => $timePeriod) {
        $slot = floor($value / $timePeriod);
        $value = $value - $slot * $timePeriod;

        if ($slot > 0) {
            $results [] = sprintf("%d%s", $slot, $periodSuffix);
        }
    }
    if (sizeof($results) == 0) {
        $results [] = "0s";
    }
    return implode($joinCharacter, array_slice($results, 0, $precision));
}

/**
 * Extend the real sprintf() with some weathermap-specific additional tokens.
 * %k for kilo-based suffixes (KGMT)
 * %T and %t for SNMP timeticks
 *
 * Assumptions - this is called from ProcessString, so there will only ever be one token in
 * the format string, and nothing else.
 *
 * @param $format a format string
 * @param $value a value to be formatted
 * @param int $kilo the base value for kilo,mega,giga calculations (1000 or 1024 usually)
 * @return string the resulting string
 */
function wmSprintf($format, $value, $kilo = 1000)
{
    // if we get a null, it probably means no-data from the datasource plugin
    // don't coerce that into a zero
    if ($value === null) {
        return "?";
    }

    if (preg_match("/%(\d*)\.?(\d*)k/", $format, $matches)) {
        $places = 2;
        // we don't really need the justification (pre-.) part...
        if ($matches[2] != "") {
            $places = intval($matches[2]);
        }
        return wmFormatNumberWithMetricPrefix($value, $kilo, $places);

    } elseif (preg_match('/%(-*)(\d*)([Tt])/', $format, $matches)) {
        $precision = ($matches[2] == '' ? 10 : intval($matches[2]));

        return wmFormatTimeTicks($value, $matches[1], $matches[3], $precision);
    }

    return sprintf($format, $value);
}


// PHP < 5.3 doesn't support anonymous functions, so here's a little function for wmStringAnonymise (screenshotify)
function wmStringAnonymiseReplacer($matches)
{
    return str_repeat('x', strlen($matches[1]));
}

/**
 * Aka 'screenshotify' - takes a string and masks out any word longer than 2 characters
 * Also turns any IP address to 127.0.0.1
 *
 * Intended to allow a quick global setting to remove all private (text) information from
 * a map for sharing.
 *
 * @param string $input The string to clean
 * @return string the cleaned result
 */
function wmStringAnonymise($input)
{
    $output = $input;

    $output = preg_replace("/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/", "127.0.0.1", $output);
    $output = preg_replace_callback("/([A-Za-z]{3,})/", "wmStringAnonymiseReplacer", $output);

    return ($output);
}


function wmInterpretNumberWithMetricPrefix($inputString, $kilo = 1000)
{
    $matches = 0;

    $lookup = array(
        "K" => $kilo,
        "M" => $kilo * $kilo,
        "G" => $kilo * $kilo * $kilo,
        "T" => $kilo * $kilo * $kilo * $kilo,
        "m" => 1/$kilo,
        "u" => 1/($kilo*$kilo)
    );

    if (preg_match('/([0-9\.]+)(M|G|K|T|m|u)/', $inputString, $matches)) {
        $number = floatval($matches[1]);

        if (isset($lookup[$matches[2]])) {
            return $number * $lookup[$matches[2]];
        }
    }
    return floatval($inputString);
}

function wmCalculateCompassOffset($compassPoint, $factor, $width, $height)
{
    $compassPoints = array(
        "N" => array(0, -1),
        "S" => array(0, 1),
        "E" => array(1, 0),
        "W" => array(-1, 0),
        "NE" => array(1, -1),
        "NW" => array(-1, -1),
        "SE" => array(1, 1),
        "SW" => array(-1, 1),
        "C" => array(0, 0)
    );
    $multiply = 1;
    if (null !== $factor) {
        $multiply = intval($factor) / 100;
        wm_debug("Percentage compass offset: multiply by $multiply");
    }

    // divide by 2, since the actual offset will only ever be half of the
    // width and height
    $height = ($height * $multiply) / 2;
    $width = ($width * $multiply) / 2;

    $offsets = $compassPoints[strtoupper($compassPoint)];
    $offsets[0] *= $width;
    $offsets[1] *= $height;

    return $offsets;
}

// given a compass-point, and a width & height, return a tuple of the x,y offsets
function wmCalculateOffset($offsetstring, $width, $height)
{

    if (preg_match('/^([-+]?\d+):([-+]?\d+)$/', $offsetstring, $matches)) {
        wm_debug("Numeric Offset found\n");
        return (array($matches[1], $matches[2]));
    }

    if (preg_match('/(NE|SE|NW|SW|N|S|E|W|C)(\d+)?$/i', $offsetstring, $matches)) {
        return wmCalculateCompassOffset($matches[1], (isset($matches[2]) ? $matches[2] : null), $width, $height);
    }

    if (preg_match('/(-?\d+)r(\d+)$/i', $offsetstring, $matches)) {
        $angle = intval($matches[1]);
        $distance = intval($matches[2]);
        $rangle = deg2rad($angle);

        $offsets = array($distance * sin($rangle), -$distance * cos($rangle));
        
        return $offsets;
    }

    // TODO - where is the named offset handling!!
    wm_warn("Got a position offset that didn't make sense ($offsetstring).");
    return (array (0, 0));
}

// These next two are based on perl's Number::Format module
// by William R. Ward, chopped down to just what I needed
function wmFormatNumber($number, $precision = 2, $trailing_zeroes = 0)
{
    $sign = 1;
    
    if ($number < 0) {
        $number = abs($number);
        $sign = - 1;
    }
    
    $number = round($number, $precision);
    $integer = intval($number);
    
    if (strlen($integer) < strlen($number)) {
        $decimal = substr($number, strlen($integer) + 1);
    }
    
    if (! isset($decimal)) {
        $decimal = '';
    }
    
    $integer = $sign * $integer;
    
    if ($decimal == '') {
        return ($integer);
    }

    return ($integer . "." . $decimal);
}

/**
 * Format a number using the most-appropriate SI suffix
 *
 * @param float $number The number to format
 * @param int $kilo What value to use for a K (1000 or 1024 usually)
 * @param int $decimals how many decimal places to display
 * @return string the resulting formatted number
 */
function wmFormatNumberWithMetricPrefix($number, $kilo = 1000, $decimals = 1)
{
    $lookup = array(
        "T" => $kilo * $kilo * $kilo * $kilo,
        "G" => $kilo * $kilo * $kilo,
        "M" => $kilo * $kilo,
        "K" => $kilo,
        "" => 1,
        "m" => 1/$kilo,
        "u" => 1/($kilo*$kilo),
        "n" => 1/($kilo*$kilo*$kilo)
    );

    $prefix = '';

    if ($number == 0) {
        return '0';
    }

    if ($number < 0) {
        $number = -$number;
        $prefix = '-';
    }

    foreach ($lookup as $suffix => $unit) {
        if ($number >= $unit) {
            return $prefix . wmFormatNumber($number/$unit, $decimals) . $suffix;
        }
    }

    return $prefix . wmFormatNumber($number, $decimals);
}

/**
 * rotate a list of points around cx,cy by an angle in radians, IN PLACE
 *
 * TODO: This should be using WMPoints! (And should be a method of WMPoint)
 *
 * @param $points array of ordinates (x,y,x,y,x,y...)
 * @param $centre_x centre of rotation, X coordinate
 * @param $centre_y centre of rotation, Y coordinate
 * @param int $angle angle in radians
 */
function rotateAboutPoint(&$points, $centre_x, $centre_y, $angle = 0)
{
    $nPoints = count($points) / 2;

    for ($i = 0; $i < $nPoints; $i ++) {
        $delta_x = $points[$i * 2] - $centre_x;
        $delta_y = $points[$i * 2 + 1] - $centre_y;
        $rotated_x = $delta_x * cos($angle) - $delta_y * sin($angle);
        $rotated_y = $delta_y * cos($angle) + $delta_x * sin($angle);

        $points[$i * 2] = $rotated_x + $centre_x;
        $points[$i * 2 + 1] = $rotated_y + $centre_y;
    }
}

// ***********************************************

// ///////////////////////////////////////////////////////////////////////////////////////



function wmDrawMarkerCross($gdImage, $colour, $point, $size = 5)
{
    $x = $point->x;
    $y = $point->y;

    imageline($gdImage, $x, $y, $x + $size, $y + $size, $colour);
    imageline($gdImage, $x, $y, $x - $size, $y + $size, $colour);
    imageline($gdImage, $x, $y, $x + $size, $y - $size, $colour);
    imageline($gdImage, $x, $y, $x - $size, $y - $size, $colour);
}

function wmDrawMarkerDiamond($gdImage, $colour, $point, $size = 10)
{
    $x = $point->x;
    $y = $point->y;

    $points = array ();
    
    $points[] = $x - $size;
    $points[] = $y;
    
    $points[] = $x;
    $points[] = $y - $size;
    
    $points[] = $x + $size;
    $points[] = $y;
    
    $points[] = $x;
    $points[] = $y + $size;
    
    $num_points = 4;
    
    imagepolygon($gdImage, $points, $num_points, $colour);
}

function wmDrawMarkerBox($gdImage, $colour, $point, $size = 10)
{
    $x = $point->x;
    $y = $point->y;

    $points = array ();
    
    $points[] = $x - $size;
    $points[] = $y - $size;
    
    $points[] = $x + $size;
    $points[] = $y - $size;
    
    $points[] = $x + $size;
    $points[] = $y + $size;
    
    $points[] = $x - $size;
    $points[] = $y + $size;
    
    $num_points = 4;
    
    imagepolygon($gdImage, $points, $num_points, $colour);
}

function wmDrawMarkerCircle($gdImage, $colour, $point, $size = 10)
{
    $x = $point->x;
    $y = $point->y;

    imagearc($gdImage, $x, $y, $size, $size, 0, 360, $colour);
}

/**
 * Produce the HTML output for both the unit-tests and the CLI utility
 *
 * @global string $WEATHERMAP_VERSION
 * @param string $htmlfile
 * @param WeatherMap $map
 */
function OutputHTML($htmlfile, &$map, $refresh = 300)
{
    global $WEATHERMAP_VERSION;
    
    $filehandle = fopen($htmlfile, 'w');
    fwrite($filehandle, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>');
    if ($map->htmlstylesheet != '') {
        fwrite($filehandle, '<link rel="stylesheet" type="text/css" href="' . $map->htmlstylesheet . '" />');
    }
    fwrite($filehandle, '<meta http-equiv="refresh" content="' . intval($refresh) . '" /><title>' . $map->processString($map->title, $map) . '</title></head><body>');
    
    if ($map->htmlstyle == "overlib") {
        fwrite($filehandle, "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n");
        fwrite($filehandle, "<script type=\"text/javascript\" src=\"vendor/overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n");
    }
    
    fwrite($filehandle, $map->makeHTML());
    fwrite($filehandle, '<hr /><span id="byline">Network Map created with <a href="http://www.network-weathermap.com/?vs=' . $WEATHERMAP_VERSION . '">PHP Network Weathermap v' . $WEATHERMAP_VERSION . '</a></span></body></html>');
    fclose($filehandle);
}

/**
 * Run a config-based test.
 * Read in config from $conffile, and produce an image and HTML output
 * Optionally Produce a new config file in $newconffile (for testing WriteConfig)
 * Optionally collect config-keyword-coverage stats about this config file
 *
 * @param string $conffile
 * @param string $imagefile
 * @param string $htmlfile
 * @param string $newconffile
 * @param string $coveragefile
 */
function TestOutput_RunTest($conffile, $imagefile, $htmlfile, $newconffile)
{
    global $WEATHERMAP_VERSION;
    
    $map = new WeatherMap();

    $map->ReadConfig($conffile);
    $skip = 0;
    $nwarns = 0;
    
    if (!strstr($WEATHERMAP_VERSION, "dev")) {
        // Allow tests to be from the future. Global SET in test file can exempt test from running
        // SET REQUIRES_VERSION 0.98
        // but don't check if the current version is a dev version
        $required_version = $map->get_hint("REQUIRES_VERSION");
        
        if ($required_version != "") {
            // doesn't need to be complete, just in the right order
            $known_versions = array (
                    "0.97",
                    "0.97a",
                    "0.97b",
                    "0.98"
            );
            $my_version = array_search($WEATHERMAP_VERSION, $known_versions);
            $req_version = array_search($required_version, $known_versions);
            if ($req_version > $my_version) {
                $skip = 1;
                $nwarns = - 1;
            }
        }
    }
    
    if ($skip == 0) {
        $map->readData();
        $map->drawMapImage($imagefile);
        $map->imagefile = $imagefile;
        
        if ($htmlfile != '') {
            OutputHTML($htmlfile, $map);
        }
        if ($newconffile != '') {
            $map->writeConfig($newconffile);
        }
        $nwarns = $map->warncount;
    }
    
    $map->cleanUp();
    unset($map);
    
    return intval($nwarns);
}


// vim:ts=4:sw=4:
