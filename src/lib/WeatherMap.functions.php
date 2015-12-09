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
    global $weathermap_map;

    if (wm_debug_shouldDebug($string)) {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $string = call_user_func_array('sprintf', $args);
        }
        
        $calling_fn = wm_debug_getCallingFunction();

        // use Cacti's debug log, if we are running from the poller
        if (function_exists('debug_log_insert') && (! function_exists('wmeShowStartPage'))) {
            cacti_log("DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim($string), true, "WEATHERMAP");
        } else {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, "DEBUG:$calling_fn " . ($weathermap_map == '' ? '' : $weathermap_map . ": ") . $string);
            fclose($stderr);
        }
    }
}

/**
 * @param $string
 * @param $weathermap_debugging_readdata
 * @return bool
 */
function wm_debug_shouldDebug($string)
{
    global $weathermap_debugging_readdata;
    global $weathermap_debugging;

    if ($weathermap_debugging_readdata) {
        $isReadData = false;

        if (false !== strpos("ReadData", $string)) {
            $isReadData = true;
        }
    }

    if ($weathermap_debugging || ($weathermap_debugging_readdata && $isReadData)) {
        return true;
    }
    return false;
}

/**
 * @return string
 */
function wm_debug_getCallingFunction()
{
    $calling_fn = "";

    if (function_exists("debug_backtrace")) {
        $backtrace = debug_backtrace();
        $index = 2;

        $function = (true === isset($backtrace[$index]['function'])) ? $backtrace[$index]['function'] : '';
        $index = 1;
        $file = (true === isset($backtrace[$index]['file'])) ? basename($backtrace[$index]['file']) : '';
        $line = (true === isset($backtrace[$index]['line'])) ? $backtrace[$index]['line'] : '';

        $calling_fn = " [$function@$file:$line]";
        return $calling_fn;
    }
    return $calling_fn;
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


// vim:ts=4:sw=4:
