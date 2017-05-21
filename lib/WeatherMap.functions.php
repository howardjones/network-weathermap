<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

// Utility functions
// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
function wm_module_checks()
{
    if (!extension_loaded('gd')) {
        wm_warn("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");

        return false;
    }

    if (!function_exists('imagecreatefrompng')) {
        wm_warn("Your GD php module doesn't support PNG format. [WMWARN21]\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");
        return false;
    }

    if (!function_exists('imagecreatetruecolor')) {
        wm_warn("Your GD php module doesn't support truecolor. [WMWARN22]\n");
        wm_warn("\nrun check.php to check PHP requirements.\n\n");
        return false;
    }

    if (!function_exists('imagecopyresampled')) {
        wm_warn("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
    }
    return true;
}

function wm_debug($string)
{
    global $weathermap_debugging;
    global $weathermap_map;
    global $weathermap_debug_suppress;

    if (func_num_args() > 1) {
        $args = func_get_args();
        $string = call_user_func_array('sprintf', $args);
    }

    if ($weathermap_debugging) {
        $calling_fn = "";
        if (function_exists("debug_backtrace")) {
            $bt = debug_backtrace();
            $index = 1;
            # 	$class = (isset($bt[$index]['class']) ? $bt[$index]['class'] : '');
            $function = (isset($bt[$index]['function']) ? $bt[$index]['function'] : '');
            $index = 0;
            $file = (isset($bt[$index]['file']) ? basename($bt[$index]['file']) : '');
            $line = (isset($bt[$index]['line']) ? $bt[$index]['line'] : '');

            $calling_fn = " [$function@$file:$line]";

            if (is_array($weathermap_debug_suppress) && in_array(strtolower($function), $weathermap_debug_suppress)) {
                return;
            }
        }

        // use Cacti's debug log, if we are running from the poller
        if (function_exists('debug_log_insert') && (!function_exists('show_editor_startpage'))) {
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

function wm_warn($string, $notice_only = FALSE)
{
    global $weathermap_map;
    global $weathermap_warncount;
    global $weathermap_error_suppress;

    $message = "";
    $code = "";

    if (preg_match('/\[(WM\w+)\]/', $string, $matches)) {
        $code = $matches[1];
    }

    if ((true === is_array($weathermap_error_suppress))
        && (true === array_key_exists(strtoupper($code), $weathermap_error_suppress))
    ) {
        wm_debug("$code is suppressed\n");
        // This error code has been deliberately disabled.
        return false;
    }

    if (!$notice_only) {
        $weathermap_warncount++;
        $message .= "WARNING: ";
    }

    $message .= ($weathermap_map == '' ? '' : $weathermap_map . ": ") . rtrim($string);

    // use Cacti's debug log, if we are running from the poller
    if (function_exists('cacti_log') && (!function_exists('show_editor_startpage'))) {
        cacti_log($message, true, "WEATHERMAP");
    } else {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, $message . "\n");
        fclose($stderr);
    }

    return true;
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


// vim:ts=4:sw=4:
