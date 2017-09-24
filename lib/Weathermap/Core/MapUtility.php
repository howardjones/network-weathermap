<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 17:47
 */

namespace Weathermap\Core;

class MapUtility
{

    public static function wm_debug2($string)
    {
        global $wm_debug_logger;

        $wm_debug_logger->log($string);
    }

    public static function wm_debug($string)
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

    public static function wm_warn($string, $notice_only = FALSE)
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
     * @param Map $map
     */
    public static function TestOutput_HTML($htmlfile, &$map)
    {
        global $WEATHERMAP_VERSION;

        $fd = fopen($htmlfile, 'w');
        fwrite($fd,
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>');
        if ($map->htmlstylesheet != '') fwrite($fd, '<link rel="stylesheet" type="text/css" href="' . $map->htmlstylesheet . '" />');
        fwrite($fd, '<meta http-equiv="refresh" content="300" /><title>' . $map->ProcessString($map->title, $map) . '</title></head><body>');

        if ($map->htmlstyle == "overlib") {
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
        fclose($fd);
    }


// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
    public static function wm_module_checks()
    {
        if (!extension_loaded('gd')) {
            self::wm_warn("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
            self::wm_warn("\nrun check.php to check PHP requirements.\n\n");

            return false;
        }

        if (!function_exists('imagecreatefrompng')) {
            self::wm_warn("Your GD php module doesn't support PNG format. [WMWARN21]\n");
            self::wm_warn("\nrun check.php to check PHP requirements.\n\n");
            return false;
        }

        if (!function_exists('imagecreatetruecolor')) {
            self::wm_warn("Your GD php module doesn't support truecolor. [WMWARN22]\n");
            self::wm_warn("\nrun check.php to check PHP requirements.\n\n");
            return false;
        }

        if (!function_exists('imagecopyresampled')) {
            self::wm_warn("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
        }
        return true;
    }


    public static function calculateCompassOffset($compassPoint, $factor, $width, $height)
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

    public static function calculateOffset($offsetstring, $width, $height)
    {
        if (preg_match('/^([-+]?\d+):([-+]?\d+)$/', $offsetstring, $matches)) {
            wm_debug("Numeric Offset found\n");
            return (array($matches[1], $matches[2]));
        }

        if (preg_match('/(NE|SE|NW|SW|N|S|E|W|C)(\d+)?$/i', $offsetstring, $matches)) {
            return self::calculateCompassOffset($matches[1], (isset($matches[2]) ? $matches[2] : null), $width, $height);
        }

        if (preg_match('/(-?\d+)r(\d+)$/i', $offsetstring, $matches)) {
            $angle = intval($matches[1]);
            $distance = intval($matches[2]);
            $rangle = deg2rad($angle);

            $offsets = array($distance * sin($rangle), -$distance * cos($rangle));

            return $offsets;
        }

        wm_warn("Got a position offset that didn't make sense ($offsetstring).");
        return (array(0, 0));
    }
}