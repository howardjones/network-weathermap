<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

// Utility functions
// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
function wm_module_checks()
{
    if (! extension_loaded ( 'gd' )) {
        wm_warn ( "\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n" );
        wm_warn ( "\nrun check.php to check PHP requirements.\n\n" );
        
        return (FALSE);
    }
    
    if (! function_exists ( 'imagecreatefrompng' )) {
        wm_warn ( "Your GD php module doesn't support PNG format. [WMWARN21]\n" );
        wm_warn ( "\nrun check.php to check PHP requirements.\n\n" );
        return (FALSE);
    }
    
    if (! function_exists ( 'imagecreatetruecolor' )) {
        wm_warn ( "Your GD php module doesn't support truecolor. [WMWARN22]\n" );
        wm_warn ( "\nrun check.php to check PHP requirements.\n\n" );
        return (FALSE);
    }
    
    if (! function_exists ( 'imagecopyresampled' )) {
        wm_warn ( "Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n" );
    }
    return (TRUE);
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
        $is_readdata = FALSE;
        
        if (FALSE !== strpos ( "ReadData", $string )) {
            $is_readdata = TRUE;
        }
    }
    
    if ($weathermap_debugging || ($weathermap_debugging_readdata && $is_readdata)) {
        if (func_num_args () > 1) {
            $args = func_get_args ();
            $string = call_user_func_array ( 'sprintf', $args );
        }
        
        $calling_fn = "";
        if (function_exists ( "debug_backtrace" )) {
            $bt = debug_backtrace ();
            $index = 1;
            
            $function = (true === isset ( $bt [$index] ['function'] )) ? $bt [$index] ['function'] : '';
            $index = 0;
            $file = (true === isset ( $bt [$index] ['file'] )) ? basename ( $bt [$index] ['file'] ) : '';
            $line = (true === isset ( $bt [$index] ['line'] )) ? $bt [$index] ['line'] : '';
            
            $calling_fn = " [$function@$file:$line]";
            
            if (is_array ( $weathermap_debug_suppress ) && in_array ( strtolower ( $function ), $weathermap_debug_suppress ))
                return;
        }
        
        // use Cacti's debug log, if we are running from the poller
        if (function_exists ( 'debug_log_insert' ) && (! function_exists ( 'show_editor_startpage' ))) {
            cacti_log ( "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim ( $string ), true, "WEATHERMAP" );
        } else {
            $stderr = fopen ( 'php://stderr', 'w' );
            fwrite ( $stderr, "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . $string );
            fclose ( $stderr );
            
            // mostly this is overkill, but it's sometimes useful (mainly in the editor)
            if (1 == 0) {
                $log = fopen ( 'debug.log', 'a' );
                fwrite ( $log, "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . $string );
                fclose ( $log );
            }
        }
    }
}

function wm_warn($string, $notice_only = FALSE, $code = "")
{
    global $weathermap_map;
    global $weathermap_warncount;
    global $weathermap_error_suppress;
    
    $message = "";
    $code = "";
    
    if (preg_match ( '/\[(WM\w+)\]/', $string, $matches )) {
        $code = $matches [1];
    }
    
    if ((true === is_array ( $weathermap_error_suppress )) && (true === in_array ( strtoupper ( $code ), $weathermap_error_suppress ))) {
        
        // This error code has been deliberately disabled.
        return;
    }
    
    if (! $notice_only) {
        $weathermap_warncount ++;
        $message .= "WARNING: ";
    }
    
    $message .= ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim ( $string );
    
    // use Cacti's debug log, if we are running from the poller
    if (function_exists ( 'cacti_log' ) && (! function_exists ( 'show_editor_startpage' ))) {
        cacti_log ( $message, true, "WEATHERMAP" );
    } else {
        $stderr = fopen ( 'php://stderr', 'w' );
        fwrite ( $stderr, $message . "\n" );
        fclose ( $stderr );
    }
}

function wm_value_or_null($value) 
{
    return ( $value === NULL ? '{NULL}' : $value);    
}



/** Die, leaving a stack-trace. Mostly used along with the unit tests to figure out if
 *  a piece of old-looking code is really redundant.
 *  
 * @param string $message
 */
function wm_die_with_trace($message="Dying here") 
{
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    die($message);
}

function js_escape($str, $wrap = TRUE)
{
    $str = str_replace ( '\\', '\\\\', $str );
    $str = str_replace ( '"', '\\"', $str );

    if ($wrap)
        $str = '"' . $str . '"';

    return ($str);
}

function mysprintf($format, $value, $kilo = 1000)
{
    $output = "";
    
    // if we get a NULL, it probably means no-data from the datasource plugin
    // don't coerce that into a zero
    if($value === NULL) {
        return "?";
    }
    
    if (preg_match ( "/%(\d*\.?\d*)k/", $format, $matches )) {
        $spec = $matches [1];
        $places = 2;
        if ($spec != '') {
            preg_match ( "/(\d*)\.?(\d*)/", $spec, $matches );
            if ($matches [2] != '')
                $places = $matches [2];
            // we don't really need the justification (pre-.) part...
        }
        $result = nice_scalar ( $value, $kilo, $places );
        $output = preg_replace ( "/%" . $spec . "k/", $format, $result );
    } elseif (preg_match ( "/%(-*)(\d*)([Tt])/", $format, $matches )) {
        $spec = $matches [3];
        $precision = ($matches [2] == '' ? 10 : intval ( $matches [2] ));
        $joinchar = " ";
        if ($matches [1] == "-") {
            $joinchar = " ";
        }

        // special formatting for time_t (t) and SNMP TimeTicks (T)
        if ($spec == "T")
            $value = $value / 100;

        $results = array ();
        $periods = array (
                "y" => 24 * 60 * 60 * 365,
                "d" => 24 * 60 * 60,
                "h" => 60 * 60,
                "m" => 60,
                "s" => 1
        );
        foreach ( $periods as $periodsuffix => $timeperiod ) {
            $slot = floor ( $value / $timeperiod );
            $value = $value - $slot * $timeperiod;

            if ($slot > 0) {
                $results [] = sprintf ( "%d%s", $slot, $periodsuffix );
            }
        }
        if (sizeof ( $results ) == 0) {
            $results [] = "0s";
        }
        $output = implode ( $joinchar, array_slice ( $results, 0, $precision ) );
    } else {
        $output = sprintf ( $format, $value );
    }
    return $output;
}

// ParseString is based on code from:
// http://www.webscriptexpert.com/Php/Space-Separated%20Tag%20Parser/
function wm_parse_string($input)
{
    $output = array (); // Array of Output
    $cPhraseQuote = null; // Record of the quote that opened the current phrase
    $sPhrase = null; // Temp storage for the current phrase we are building
     
    // Define some constants
    $sTokens = " \t"; // Space, Tab
    $sQuotes = "'\""; // Single and Double Quotes

    // Start the State Machine
    do {
        // Get the next token, which may be the first
        $sToken = isset ( $sToken ) ? strtok ( $sTokens ) : strtok ( $input, $sTokens );

        // Are there more tokens?
        if ($sToken === false) {
            // Ensure that the last phrase is marked as ended
            $cPhraseQuote = null;
        } else {
            // Are we within a phrase or not?
            if ($cPhraseQuote !== null) {
                // Will the current token end the phrase?
                if (substr ( $sToken, - 1, 1 ) === $cPhraseQuote) {
                    // Trim the last character and add to the current phrase, with a single leading space if necessary
                    if (strlen ( $sToken ) > 1)
                        $sPhrase .= ((strlen ( $sPhrase ) > 0) ? ' ' : null) . substr ( $sToken, 0, - 1 );
                    $cPhraseQuote = null;
                } else {
                    // If not, add the token to the phrase, with a single leading space if necessary
                    $sPhrase .= ((strlen ( $sPhrase ) > 0) ? ' ' : null) . $sToken;
                }
            } else {
                // Will the current token start a phrase?
                if (strpos ( $sQuotes, $sToken [0] ) !== false) {
                    // Will the current token end the phrase?
                    if ((strlen ( $sToken ) > 1) && ($sToken [0] === substr ( $sToken, - 1, 1 ))) {
                        // The current token begins AND ends the phrase, trim the quotes
                        $sPhrase = substr ( $sToken, 1, - 1 );
                    } else {
                        // Remove the leading quote
                        $sPhrase = substr ( $sToken, 1 );
                        $cPhraseQuote = $sToken [0];
                    }
                } else
                    $sPhrase = $sToken;
            }
        }

        // If, at this point, we are not within a phrase, the prepared phrase is complete and can be added to the array
        if (($cPhraseQuote === null) && ($sPhrase != null)) {
            $output [] = $sPhrase;
            $sPhrase = null;
        }
    } while ( $sToken !== false ); // Stop when we receive FALSE from strtok()

    return $output;
}

// PHP < 5.3 doesn't support anonymous functions, so here's a little function for screenshotify
function screenshotify_xxx($matches)
{
    return str_repeat('x',strlen($matches[1]));
}

function screenshotify($input)
{
    $output = $input;

    $output = preg_replace ( "/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/", "127.0.0.1", $output );
    $output = preg_replace_callback ( "/([A-Za-z]{3,})/", "screenshotify_xxx", $output );

    return ($output);
}


function wm_unformat_number($instring, $kilo = 1000)
{
    $matches = 0;
    $number = 0;
    
    if (preg_match ( "/([0-9\.]+)(M|G|K|T|m|u)/", $instring, $matches )) {
        $number = floatval ( $matches [1] );
        
        switch ($matches [2]) {
            case 'K' :
                return $number * $kilo;
            case 'M' :
                return $number * $kilo * $kilo;
            case 'G' :
                return $number * $kilo * $kilo * $kilo;
            case 'T' :
                return $number * $kilo * $kilo * $kilo * $kilo;
            case 'm' :
                return $number / $kilo;
            case 'u' :
                return $number / ($kilo * $kilo);
        }
    } else {
        $number = floatval ( $instring );
    }
    
    return ($number);
}

// given a compass-point, and a width & height, return a tuple of the x,y offsets
function calc_offset($offsetstring, $width, $height)
{
    if (preg_match ( "/^([-+]?\d+):([-+]?\d+)$/", $offsetstring, $matches )) {
        wm_debug ( "Numeric Offset found\n" );
        return (array (
                $matches [1],
                $matches [2] 
        ));
    } elseif (preg_match ( "/(NE|SE|NW|SW|N|S|E|W|C)(\d+)?$/i", $offsetstring, $matches )) {
        $multiply = 1;
        if (isset ( $matches [2] )) {
            $multiply = intval ( $matches [2] ) / 100;
            wm_debug ( "Percentage compass offset: multiply by $multiply" );
        }
        
        $height = $height * $multiply;
        $width = $width * $multiply;
        
        switch (strtoupper ( $matches [1] )) {
            case 'N' :
                return (array (
                        0,
                        - $height / 2 
                ));
            
            case 'S' :
                return (array (
                        0,
                        $height / 2 
                ));
            
            case 'E' :
                return (array (
                        + $width / 2,
                        0 
                ));
            
            case 'W' :
                return (array (
                        - $width / 2,
                        0 
                ));
            
            case 'NW' :
                return (array (
                        - $width / 2,
                        - $height / 2 
                ));
            
            case 'NE' :
                return (array (
                        $width / 2,
                        - $height / 2 
                ));
            
            case 'SW' :
                return (array (
                        - $width / 2,
                        $height / 2 
                ));
            
            case 'SE' :
                return (array (
                        $width / 2,
                        $height / 2 
                ));
            
            case 'C' : // FALL THROUGH
            default :
                return (array (
                        0,
                        0 
                ));
        }
    } elseif (preg_match ( "/(-?\d+)r(\d+)$/i", $offsetstring, $matches )) {
        $angle = intval ( $matches [1] );
        $distance = intval ( $matches [2] );
        
        $x = $distance * sin ( deg2rad ( $angle ) );
        $y = - $distance * cos ( deg2rad ( $angle ) );
        
        return (array (
                $x,
                $y 
        ));
    } else {
        wm_warn ( "Got a position offset that didn't make sense ($offsetstring)." );
        return (array (
                0,
                0 
        ));
    }
}

// These next two are based on perl's Number::Format module
// by William R. Ward, chopped down to just what I needed
function format_number($number, $precision = 2, $trailing_zeroes = 0)
{
    $sign = 1;
    
    if ($number < 0) {
        $number = abs ( $number );
        $sign = - 1;
    }
    
    $number = round ( $number, $precision );
    $integer = intval ( $number );
    
    if (strlen ( $integer ) < strlen ( $number )) {
        $decimal = substr ( $number, strlen ( $integer ) + 1 );
    }
    
    if (! isset ( $decimal )) {
        $decimal = '';
    }
    
    $integer = $sign * $integer;
    
    if ($decimal == '') {
        return ($integer);
    } else {
        return ($integer . "." . $decimal);
    }
}

function wm_nice_bandwidth($number, $kilo = 1000, $decimals = 1, $below_one = TRUE)
{
    $suffix = '';
    
    if ($number == 0)
        return '0';
    
    $mega = $kilo * $kilo;
    $giga = $mega * $kilo;
    $tera = $giga * $kilo;
    
    $milli = 1 / $kilo;
    $micro = 1 / $mega;
    $nano = 1 / $giga;
    
    if ($number >= $tera) {
        $number /= $tera;
        $suffix = "T";
    } elseif ($number >= $giga) {
        $number /= $giga;
        $suffix = "G";
    } elseif ($number >= $mega) {
        $number /= $mega;
        $suffix = "M";
    } elseif ($number >= $kilo) {
        $number /= $kilo;
        $suffix = "K";
    } elseif ($number >= 1) {
        $number = $number;
        $suffix = "";
    } elseif (($below_one == TRUE) && ($number >= $milli)) {
        $number /= $milli;
        $suffix = "m";
    } elseif (($below_one == TRUE) && ($number >= $micro)) {
        $number /= $micro;
        $suffix = "u";
    } elseif (($below_one == TRUE) && ($number >= $nano)) {
        $number /= $nano;
        $suffix = "n";
    }
    
    $result = format_number ( $number, $decimals ) . $suffix;
    return ($result);
}

function nice_scalar($number, $kilo = 1000, $decimals = 1)
{
    $suffix = '';
    $prefix = '';
    
    if ($number == 0)
        return '0';
    
    if ($number < 0) {
        $number = - $number;
        $prefix = '-';
    }
    
    $mega = $kilo * $kilo;
    $giga = $mega * $kilo;
    $tera = $giga * $kilo;
    
    if ($number > $tera) {
        $number /= $tera;
        $suffix = "T";
    } elseif ($number > $giga) {
        $number /= $giga;
        $suffix = "G";
    } elseif ($number > $mega) {
        $number /= $mega;
        $suffix = "M";
    } elseif ($number > $kilo) {
        $number /= $kilo;
        $suffix = "K";
    } elseif ($number > 1) {
        $number = $number;
        $suffix = "";
    } elseif ($number < (1 / ($kilo))) {
        $number = $number * $mega;
        $suffix = "u";
    } elseif ($number < 1) {
        $number = $number * $kilo;
        $suffix = "m";
    }
    
    $result = $prefix . format_number ( $number, $decimals ) . $suffix;
    return $result;
}


// rotate a list of points around cx,cy by an angle in radians, IN PLACE
function RotateAboutPoint(&$points, $centre_x, $centre_y, $angle = 0)
{
    $npoints = count ( $points ) / 2;

    for($i = 0; $i < $npoints; $i ++) {
        $delta_x = $points [$i * 2] - $centre_x;
        $delta_y = $points [$i * 2 + 1] - $centre_y;
        $rotated_x = $delta_x * cos ( $angle ) - $delta_y * sin ( $angle );
        $rotated_y = $delta_y * cos ( $angle ) + $delta_x * sin ( $angle );

        $points [$i * 2] = $rotated_x + $centre_x;
        $points [$i * 2 + 1] = $rotated_y + $centre_y;
    }
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

// ///////////////////////////////////////////////////////////////////////////////////////



function wm_draw_marker_cross($gdimage, $colour, $x, $y, $size = 5)
{
    imageline ( $gdimage, $x, $y, $x + $size, $y + $size, $colour );
    imageline ( $gdimage, $x, $y, $x - $size, $y + $size, $colour );
    imageline ( $gdimage, $x, $y, $x + $size, $y - $size, $colour );
    imageline ( $gdimage, $x, $y, $x - $size, $y - $size, $colour );
}

function wm_draw_marker_diamond($gdimage, $colour, $x, $y, $size = 10)
{
    $points = array ();
    
    $points [] = $x - $size;
    $points [] = $y;
    
    $points [] = $x;
    $points [] = $y - $size;
    
    $points [] = $x + $size;
    $points [] = $y;
    
    $points [] = $x;
    $points [] = $y + $size;
    
    $num_points = 4;
    
    imagepolygon ( $gdimage, $points, $num_points, $colour );
}

function wm_draw_marker_box($gdimage, $colour, $x, $y, $size = 10)
{
    $points = array ();
    
    $points [] = $x - $size;
    $points [] = $y - $size;
    
    $points [] = $x + $size;
    $points [] = $y - $size;
    
    $points [] = $x + $size;
    $points [] = $y + $size;
    
    $points [] = $x - $size;
    $points [] = $y + $size;
    
    $num_points = 4;
    
    imagepolygon ( $gdimage, $points, $num_points, $colour );
}

function wm_draw_marker_circle($gdimage, $colour, $x, $y, $size = 10)
{
    imagearc ( $gdimage, $x, $y, $size, $size, 0, 360, $colour );
}

function wm_draw_spine_chain($gdimage, $spine, $colour, $size = 10)
{
    $newn = count ( $spine );
    
    for($i = 0; $i < $newn; $i ++) {
        imagearc ( $gdimage, $spine [$i] [X], $spine [$i] [Y], $size, $size, 0, 360, $colour );
    }
}

function wm_dump_spine($spine)
{
    print "===============\n";
    for($i = 0; $i < count ( $spine ); $i ++) {
        printf ( "  %3d: %d,%d (%d)\n", $i, $spine [$i] [X], $spine [$i] [Y], $spine [$i] [DISTANCE] );
    }
    print "===============\n";
}

function wm_draw_spine($gdimage, $spine, $colour)
{
    $max_i = count ( $spine ) - 1;
    
    for($i = 0; $i < $max_i; $i ++) {
        imageline ( $gdimage, $spine [$i] [X], $spine [$i] [Y], $spine [$i + 1] [X], $spine [$i + 1] [Y], $colour );
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
    
    $fd = fopen ( $htmlfile, 'w' );
    fwrite ( $fd, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>' );
    if ($map->htmlstylesheet != '') {
        fwrite ( $fd, '<link rel="stylesheet" type="text/css" href="' . $map->htmlstylesheet . '" />' );
    }
    fwrite ( $fd, '<meta http-equiv="refresh" content="300" /><title>' . $map->ProcessString ( $map->title, $map ) . '</title></head><body>' );
    
    if ($map->htmlstyle == "overlib") {
        fwrite ( $fd, "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n" );
        fwrite ( $fd, "<script type=\"text/javascript\" src=\"vendor/overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n" );
    }
    
    fwrite ( $fd, $map->MakeHTML () );
    fwrite ( $fd, '<hr /><span id="byline">Network Map created with <a href="http://www.network-weathermap.com/?vs=' . $WEATHERMAP_VERSION . '">PHP Network Weathermap v' . $WEATHERMAP_VERSION . '</a></span></body></html>' );
    fclose ( $fd );
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
function TestOutput_RunTest($conffile, $imagefile, $htmlfile, $newconffile, $coveragefile)
{
//    global $weathermap_map;
    global $WEATHERMAP_VERSION;
    
    $map = new WeatherMap ();
    if ($coveragefile != '') {
        $map->SeedCoverage ();
        if (file_exists ( $coveragefile )) {
            $map->LoadCoverage ( $coveragefile );
        }
    }
    # $weathermap_map = $conffile;
    $map->ReadConfig ( $conffile );
    $skip = 0;
    $nwarns = 0;
    
    if (! strstr ( $WEATHERMAP_VERSION, "dev" )) {
        // Allow tests to be from the future. Global SET in test file can excempt test from running
        // SET REQUIRES_VERSION 0.98
        // but don't check if the current version is a dev version
        $required_version = $map->get_hint ( "REQUIRES_VERSION" );
        
        if ($required_version != "") {
            // doesan't need to be complete, just in the right order
            $known_versions = array (
                    "0.97",
                    "0.97a",
                    "0.97b",
                    "0.98" 
            );
            $my_version = array_search ( $WEATHERMAP_VERSION, $known_versions );
            $req_version = array_search ( $required_version, $known_versions );
            if ($req_version > $my_version) {
                $skip = 1;
                $nwarns = - 1;
            }
        }
    }
    
    if ($skip == 0) {
        $map->ReadData ();
        $map->DrawMap ( $imagefile );
        $map->imagefile = $imagefile;
        
        if ($htmlfile != '') {
            TestOutput_HTML ( $htmlfile, $map );
        }
        if ($newconffile != '') {
            $map->WriteConfig ( $newconffile );
        }
        if ($coveragefile != '') {
            $map->SaveCoverage ( $coveragefile );
        }
        $nwarns = $map->warncount;
    }
    
    $map->CleanUp ();
    unset ( $map );
    
    return intval ( $nwarns );
}

// vim:ts=4:sw=4:
