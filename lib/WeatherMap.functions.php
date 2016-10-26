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

    $weathermap_map = $conffile;
    $map->ReadConfig($conffile);
    $skip = 0;
    $nwarns = 0;

    if (!strstr($WEATHERMAP_VERSION, "dev")) {
        # Allow tests to be from the future. Global SET in test file can excempt test from running
        # SET REQUIRES_VERSION 0.98
        # but don't check if the current version is a dev version
        $required_version = $map->get_hint("REQUIRES_VERSION");

        if ($required_version != "") {
            // doesan't need to be complete, just in the right order
            $known_versions = array("0.97", "0.97a", "0.97b", "0.98");
            $my_version = array_search($WEATHERMAP_VERSION, $known_versions);
            $req_version = array_search($required_version, $known_versions);
            if ($req_version > $my_version) {
                $skip = 1;
                $nwarns = -1;
            }
        }
    }

    if ($skip == 0) {
        $map->readData();
        $map->DrawMap($imagefile);
        $map->imagefile = $imagefile;
        if ($htmlfile != '') {
            TestOutput_HTML($htmlfile, $map);
        }
        if ($newconffile != '') {
            $map->WriteConfig($newconffile);
        }

        $nwarns = $map->warncount;
    }

    $map->CleanUp();
    unset ($map);

    return intval($nwarns);
}


// vim:ts=4:sw=4:
