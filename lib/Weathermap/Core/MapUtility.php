<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 17:47
 */

namespace Weathermap\Core;

/**
 * Utility Functions that were in Map but don't need to be methods
 * Quite a random collection that should probably be elsewhere.
 *
 * @package Weathermap\Core
 */
class MapUtility
{

    public static function debug2($string)
    {
        global $wmDebugLogger;

        $wmDebugLogger->log($string);
    }

    public static function debug($string)
    {
        global $weathermap_debugging;
        global $weathermap_map;
        global $weathermap_debug_suppress;

        if (func_num_args() > 1) {
            $args = func_get_args();
            $string = call_user_func_array('sprintf', $args);
        }

        if ($weathermap_debugging) {
            $callingFunction = '';
            if (function_exists('debug_backtrace')) {
                $bt = debug_backtrace();
                $index = 1;
                #   $class = (isset($bt[$index]['class']) ? $bt[$index]['class'] : '');
                $function = (isset($bt[$index]['function']) ? $bt[$index]['function'] : '');
                $index = 0;
                $file = (isset($bt[$index]['file']) ? basename($bt[$index]['file']) : '');
                $line = (isset($bt[$index]['line']) ? $bt[$index]['line'] : '');

                $callingFunction = " [$function@$file:$line]";

                if (is_array($weathermap_debug_suppress) && in_array(
                    strtolower($function),
                    $weathermap_debug_suppress
                )) {
                    return;
                }
            }

            // use Cacti's debug log, if we are running from the poller
            if (function_exists('debug_log_insert') && (!function_exists('show_editor_startpage'))) {
                \cacti_log(
                    "DEBUG:$callingFunction " . ($weathermap_map == '' ? '' : $weathermap_map . ': ') . rtrim($string),
                    true,
                    'WEATHERMAP'
                );
            } else {
                $stderr = fopen('php://stderr', 'w');
                fwrite(
                    $stderr,
                    "DEBUG:$callingFunction " . ($weathermap_map == '' ? '' : $weathermap_map . ': ') . $string
                );
                fclose($stderr);

                // mostly this is overkill, but it's sometimes useful (mainly in the editor)
                if (1 == 0) {
                    $log = fopen('debug.log', 'a');
                    fwrite(
                        $log,
                        "DEBUG:$callingFunction " . ($weathermap_map == '' ? '' : $weathermap_map . ': ') . $string
                    );
                    fclose($log);
                }
            }
        }
    }

    public static function notice($string, $noticeOnly = false)
    {
        // TODO: This is ugly for now, but will be OK again once there's a logger object
        // (the quietLogging check will just translate to loglevel=NOTICE or loglevel=WARN)
        $quietLogging = \read_config_option("weathermap_quiet_logging");
        if (!$quietLogging) {
            MapUtility::warn($string, $noticeOnly);
        }
    }

    public static function warn($string, $noticeOnly = false)
    {
        global $weathermap_map;
        global $weathermap_warncount;
        global $weathermap_error_suppress;

        $message = '';
        $code = '';

        if (preg_match('/\[(WM\w+)\]/', $string, $matches)) {
            $code = $matches[1];
        }

        if ((true === is_array($weathermap_error_suppress))
            && (true === array_key_exists(strtoupper($code), $weathermap_error_suppress))
        ) {
            self::debug("$code is suppressed\n");
            // This error code has been deliberately disabled.
            return false;
        }

        if (!$noticeOnly) {
            $weathermap_warncount++;
            $message .= 'WARNING: ';
        }

        $message .= ($weathermap_map == '' ? '' : $weathermap_map . ': ') . rtrim($string);

        // use Cacti's debug log, if we are running from the poller
        if (function_exists('cacti_log') && (!function_exists('show_editor_startpage'))) {
            cacti_log($message, true, 'WEATHERMAP');
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
     * @param string $htmlfile
     * @param Map $map
     */
    public static function outputTestHTML($htmlfile, &$map)
    {
        $fd = fopen($htmlfile, 'w');
        fwrite(
            $fd,
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>'
        );
        if ($map->htmlstylesheet != '') {
            fwrite($fd, '<link rel="stylesheet" type="text/css" href="' . $map->htmlstylesheet . '" />');
        }
        fwrite(
            $fd,
            '<meta http-equiv="refresh" content="300" /><title>' . $map->processString(
                $map->title,
                $map
            ) . '</title></head><body>'
        );

        if ($map->htmlstyle == 'overlib') {
            fwrite(
                $fd,
                "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n"
            );
            fwrite(
                $fd,
                "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n"
            );
        }

        fwrite($fd, $map->makeHTML());
        fwrite(
            $fd,
            '<hr /><span id="byline">Network Map created with <a href="http://www.network-weathermap.com/?vs='
            . WEATHERMAP_VERSION . '">PHP Network Weathermap v' . WEATHERMAP_VERSION
            . '</a></span></body></html>'
        );
        fclose($fd);
    }


// Check for GD & PNG support This is just in here so that both the editor and CLI can use it without the need for another file
    public static function moduleChecks()
    {
        if (!extension_loaded('gd')) {
            self::warn("\n\nNo image (gd) extension is loaded. This is required by weathermap. [WMWARN20]\n\n");
            self::warn("\nrun check.php to check PHP requirements.\n\n");

            return false;
        }

        if (!function_exists('imagecreatefrompng')) {
            self::warn("Your GD php module doesn't support PNG format. [WMWARN21]\n");
            self::warn("\nrun check.php to check PHP requirements.\n\n");
            return false;
        }

        if (!function_exists('imagecreatetruecolor')) {
            self::warn("Your GD php module doesn't support truecolor. [WMWARN22]\n");
            self::warn("\nrun check.php to check PHP requirements.\n\n");
            return false;
        }

        if (!function_exists('imagecopyresampled')) {
            self::warn("Your GD php module doesn't support thumbnail creation (imagecopyresampled). [WMWARN23]\n");
        }
        return true;
    }


    public static function calculateCompassOffset($compassPoint, $factor, $width, $height)
    {
        $compassPoints = array(
            'N' => array(0, -1),
            'S' => array(0, 1),
            'E' => array(1, 0),
            'W' => array(-1, 0),
            'NE' => array(1, -1),
            'NW' => array(-1, -1),
            'SE' => array(1, 1),
            'SW' => array(-1, 1),
            'C' => array(0, 0)
        );
        $multiply = 1;
        if (null !== $factor) {
            $multiply = intval($factor) / 100;
            self::debug("Percentage compass offset: multiply by $multiply");
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
        if ($offsetstring == "") {
            return array(0, 0);
        }

        if (preg_match('/^([-+]?\d+):([-+]?\d+)$/', $offsetstring, $matches)) {
            self::debug("Numeric Offset found\n");
            return array($matches[1], $matches[2]);
        }

        if (preg_match('/(NE|SE|NW|SW|N|S|E|W|C)(\d+)?$/i', $offsetstring, $matches)) {
            return self::calculateCompassOffset(
                $matches[1],
                (isset($matches[2]) ? $matches[2] : null),
                $width,
                $height
            );
        }

        if (preg_match('/(-?\d+)r(\d+)$/i', $offsetstring, $matches)) {
            $angle = intval($matches[1]);
            $distance = intval($matches[2]);
            $radianAngle = deg2rad($angle);

            $offsets = array($distance * sin($radianAngle), -$distance * cos($radianAngle));

            return $offsets;
        }

        self::warn("Got a position offset that didn't make sense ($offsetstring).");

        return array(0, 0);
    }
}
