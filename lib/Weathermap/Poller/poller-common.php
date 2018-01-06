<?php

namespace Weathermap\Poller;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Map;
use Weathermap\Integrations\Cacti\CactiApplicationInterface;
use Weathermap\Integrations\MapManager;
use Weathermap\Core\Utility;

// common code used by the poller, the manual-run from the Cacti UI, and from the command-line manual-run.
// this is the easiest way to keep it all consistent!


function testCronPart($value, $checkstring)
{
    // XXX - this should really handle a few more crontab niceties like */5 or 3,5-9 but this will do for now
    if ($checkstring == '*') {
        return true;
    }
    if ($checkstring == $value) {
        return true;
    }

    $value = intval($value);

    // Cron allows for multiple comma separated clauses, so let's break them
    // up first, and evaluate each one.
    $parts = explode(",", $checkstring);

    foreach ($parts as $part) {
        // just a number
        if ($part === $value) {
            return true;
        }

        // an interval - e.g. */5
        if (1 === preg_match('/\*\/(\d+)/', $part, $matches)) {
            $mod = intval($matches[1]);

            if (($value % $mod) === 0) {
                return true;
            }
        }

        // a range - e.g. 4-7
        if (1 === preg_match('/(\d+)\-(\d+)/', $part, $matches)) {
            if (($value >= intval($matches[1])) && ($value <= intval($matches[2]))) {
                return true;
            }
        }
    }

    return false;
}

function checkCronString($time, $string)
{
    if ($string == '' || $string == '*' || $string == '* * * * *') {
        return true;
    }

    $localTime = localtime($time, true);
    list($minute, $hour, $wday, $day, $month) = preg_split('/\s+/', $string);

    $matched = true;

    $matched = $matched && testCronPart($localTime['tm_min'], $minute);
    $matched = $matched && testCronPart($localTime['tm_hour'], $hour);
    $matched = $matched && testCronPart($localTime['tm_wday'], $wday);
    $matched = $matched && testCronPart($localTime['tm_mday'], $day);
    $matched = $matched && testCronPart($localTime['tm_mon'] + 1, $month);

    return $matched;
}


function runMaps($mydir)
{
    global $config;
    global $weathermap_debugging;
    global $weathermap_map;
    global $weathermap_warncount;
    global $weathermapPollerStartTime;

//    include_once $mydir . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "HTMLImagemap.php";
//    include_once $mydir . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "Map.php";
//    include_once $mydir . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "database.php";
//    include_once $mydir . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "WeathermapManager.php";

    $configDirectory = realpath($mydir . DIRECTORY_SEPARATOR . 'configs');

    $app = new CactiApplicationInterface(weathermap_get_pdo());
    $manager = new MapManager(weathermap_get_pdo(), $configDirectory, $app);

    $pluginName = "weathermap-cacti-plugin.php";
    if (substr($config['cacti_version'], 0, 3) == "0.8") {
        $pluginName = "weathermap-cacti88-plugin.php";
    }

    if (substr($config['cacti_version'], 0, 2) == "1.") {
        $pluginName = "weathermap-cacti10-plugin.php";
    }

    $totalWarnings = 0;
    $warningNotes = "";

    $startTime = microtime(true);
    if ($weathermapPollerStartTime == 0) {
        $weathermapPollerStartTime = intval($startTime);
    }

    $outputDirectory = $mydir . DIRECTORY_SEPARATOR . 'output';
    $confdir = $mydir . DIRECTORY_SEPARATOR . 'configs';

    $mapCount = 0;

    // take our debugging cue from the poller - turn on Poller debugging to get weathermap debugging
    if (\read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
        $weathermap_debugging = true;
        $modeMessage = "DEBUG mode is on";
    } else {
        $modeMessage = "Normal logging mode. Turn on DEBUG in Cacti for more information";
    }
    $quietLogging = \read_config_option("weathermap_quiet_logging");
    // moved this outside the module_checks, so there should always be something in the logs!
    if ($quietLogging == 0) {
        \cacti_log("Weathermap " . WEATHERMAP_VERSION . " starting - $modeMessage\n", true, "WEATHERMAP");
    }

    if (!MapUtility::moduleChecks()) {
        MapUtility::warn("Required modules for PHP Weathermap " . WEATHERMAP_VERSION . " were not present. Not running. [WMPOLL08]\n");
        return;
    }
    Utility::memoryCheck("MEM Initial");
    // move to the weathermap folder so all those relatives paths don't *have* to be absolute
    $originalWorkingDir = getcwd();
    chdir($mydir);

    $manager->application->setAppSetting("weathermap_last_start_time", time());

    // first, see if the output directory even exists
    if (is_dir($outputDirectory)) {
        // next, make sure that we stand a chance of writing files
        //// $testfile = realpath($outdir."weathermap.permissions.test");
        ///
        $testfile = $outputDirectory . DIRECTORY_SEPARATOR . "weathermap.permissions.test";
        $testfd = @fopen($testfile, 'w');
        if ($testfd) {
            fclose($testfd);
            unlink($testfile);

            $queryrows = $manager->getMapRunList();

            if (is_array($queryrows)) {
                MapUtility::debug("Iterating all maps.");

                $imageFormat = strtolower(\read_config_option("weathermap_output_format"));
                $rrdtoolPath = \read_config_option("path_rrdtool");

// TODO: From here, should all be in MapRuntime, as per-map processing
//*********************
                foreach ($queryrows as $mapSpec) {
                    // reset the warning counter
                    $weathermap_warncount = 0;
                    // this is what will prefix log entries for this map
                    $weathermap_map = "[Map " . $mapSpec->id . "] " . $mapSpec->configfile;

                    MapUtility::debug("FIRST TOUCH\n");

                    if (checkCronString($weathermapPollerStartTime, $mapSpec->schedule)) {
                        $mapFileName = $confdir . DIRECTORY_SEPARATOR . $mapSpec->configfile;

                        $htmlFileName = $outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".html";
                        $imageFileName = $outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . "." . $imageFormat;
                        $thumbnailFileName = $outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".thumb." . $imageFormat;
                        $resultsFile = $outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . '.results.txt';
                        $tempImageFile = $outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . '.tmp.png';

                        $mapDuration = 0;

                        if (file_exists($mapFileName)) {
                            if ($quietLogging == 0) {
                                MapUtility::warn("Map: $mapFileName -> $htmlFileName & $imageFileName\n", true);
                            }
                            $manager->application->setAppSetting("weathermap_last_started_file", $weathermap_map);

                            $mapStartTime = microtime(true);
                            Utility::memoryCheck("MEM starting $mapCount");
                            $map = new Map;
                            $map->context = "cacti";

                            // we can grab the rrdtool path from Cacti's config, in this case
                            $map->rrdtool = $rrdtoolPath;

                            $map->readConfig($mapFileName);

                            $map->addHint("mapgroup", $mapSpec->groupname);
                            $map->addHint("mapgroupextra", ($mapSpec->group_id == 1 ? "" : $mapSpec->groupname));

                            # in the order of precedence - global extras, group extras, and finally map extras
                            $settingsGlobal = $manager->getMapSettings(0);
                            $settingsGroup = $manager->getMapSettings(-$mapSpec->group_id);
                            $settingsMap = $manager->getMapSettings($mapSpec->id);

                            $extraSettings = array(
                                "all maps" => $settingsGlobal,
                                "all maps in group" => $settingsGroup,
                                "map-global" => $settingsMap
                            );

                            foreach ($extraSettings as $settingType => $settingrows) {
                                if (is_array($settingrows) && count($settingrows) > 0) {
                                    foreach ($settingrows as $setting) {
                                        MapUtility::debug("Setting additional ($settingType) option: " . $setting->optname . " to '" . $setting->optvalue . "'\n");
                                        $map->addHint($setting->optname, $setting->optvalue);

                                        if (substr($setting->optname, 0, 7) == 'nowarn_') {
                                            $code = strtoupper(substr($setting->optname, 7));
                                            $weathermap_error_suppress[] = $code;
                                        }
                                    }
                                }
                            }

                            Utility::memoryCheck("MEM postread $mapCount");
                            $map->readData();
                            Utility::memoryCheck("MEM postdata $mapCount");

                            // why did I change this before? It's useful...
                            // $wmap->imageuri = $config['url_path'].'/plugins/weathermap/output/weathermap_'.$map->id.".".$imageformat;
                            $configuredImageURI = $map->imageuri;
                            $map->imageuri = $pluginName . '?action=viewimage&id=' . $mapSpec->filehash . "&time=" . time();

                            if ($quietLogging == 0) {
                                $note = Utility::buildMemoryCheckString("");
                                MapUtility::warn(
                                    "About to write image file. If this is the last message in your log, increase memory_limit in php.ini () [WMPOLL01] $note\n",
                                    true
                                );
                            }
                            Utility::memoryCheck("MEM pre-render $mapCount");

                            // Write the image to a temporary file first - it turns out that libpng is not that fast
                            // and this way we avoid showing half a map
                            $map->drawMap(
                                $tempImageFile,
                                $thumbnailFileName,
                                \read_config_option("weathermap_thumbsize")
                            );

                            // Firstly, don't move or delete anything if the image saving failed
                            if (file_exists($tempImageFile)) {
                                // Don't try and delete a non-existent file (first run)
                                if (file_exists($imageFileName)) {
                                    unlink($imageFileName);
                                }
                                rename($tempImageFile, $imageFileName);
                            }

                            if ($quietLogging == 0) {
                                MapUtility::warn("Wrote map to $imageFileName and $thumbnailFileName\n", true);
                            }
                            $fd = @fopen($htmlFileName, 'w');
                            if ($fd != false) {
                                fwrite($fd, $map->makeHTML('weathermap_' . $mapSpec->filehash . '_imap'));
                                fclose($fd);
                                MapUtility::debug("Wrote HTML to $htmlFileName");
                            } else {
                                if (file_exists($htmlFileName)) {
                                    MapUtility::warn("Failed to overwrite $htmlFileName - permissions of existing file are wrong? [WMPOLL02]\n");
                                } else {
                                    MapUtility::warn("Failed to create $htmlFileName - permissions of output directory are wrong? [WMPOLL03]\n");
                                }
                            }

                            $map->writeDataFile($resultsFile);
                            // if the user explicitly defined a data file, write it there too
                            if ($map->dataoutputfile) {
                                $map->writeDataFile($map->dataoutputfile);
                            }

                            // put back the configured imageuri
                            $map->imageuri = $configuredImageURI;

                            // if an htmloutputfile was configured, output the HTML there too
                            // but using the configured imageuri and imagefilename
                            if ($map->htmloutputfile != "") {
                                $htmlFileName = $map->htmloutputfile;
                                $fd = @fopen($htmlFileName, 'w');

                                if ($fd !== false) {
                                    fwrite(
                                        $fd,
                                        $map->makeHTML('weathermap_' . $mapSpec->filehash . '_imap')
                                    );
                                    fclose($fd);
                                    MapUtility::debug("Wrote HTML to %s\n", $htmlFileName);
                                } else {
                                    if (true === file_exists($htmlFileName)) {
                                        MapUtility::warn(
                                            'Failed to overwrite ' . $htmlFileName
                                            . " - permissions of existing file are wrong? [WMPOLL02]\n"
                                        );
                                    } else {
                                        MapUtility::warn(
                                            'Failed to create ' . $htmlFileName
                                            . " - permissions of output directory are wrong? [WMPOLL03]\n"
                                        );
                                    }
                                }
                            }

                            if ($map->imageoutputfile != "" && $map->imageoutputfile != "weathermap.png" && file_exists($imageFileName)) {
                                // copy the existing image file to the configured location too
                                @copy($imageFileName, $map->imageoutputfile);
                            }

                            $manager->updateMap(
                                $mapSpec->id,
                                array(
                                    'titlecache' => $map->processString($map->title, $map)
                                )
                            );

                            $map->stats->dump();

                            if (intval($map->thumbWidth) > 0) {
                                $manager->updateMap(
                                    $mapSpec->id,
                                    array(
                                        'thumb_width' => intval($map->thumbWidth),
                                        'thumb_height' => intval($map->thumbHeight)
                                    )
                                );
                            }

                            $map->cleanUp();

                            $mapDuration = microtime(true) - $mapStartTime;
                            MapUtility::debug("TIME: $mapFileName took $mapDuration seconds.\n");
                            $map->stats->set("duration", $mapDuration);
                            $map->stats->set("warnings", $weathermap_warncount);
                            if ($quietLogging == 0) {
                                MapUtility::warn("MAPINFO: " . $map->stats->dump(), true);
                            }
                            unset($map);

                            Utility::memoryCheck("MEM after $mapCount");
                            $mapCount++;
                            $manager->application->setAppSetting("weathermap_last_finished_file", $weathermap_map);
                        } else {
                            MapUtility::warn("Mapfile $mapFileName is not readable or doesn't exist [WMPOLL04]\n");
                        }
                        $manager->updateMap(
                            $mapSpec->id,
                            array(
                                'warncount' => intval($weathermap_warncount),
                                'runtime' => floatval($mapDuration)
                            )
                        );

                        $totalWarnings += $weathermap_warncount;
                        $weathermap_warncount = 0;
                        $weathermap_map = "";
                    } else {
                        MapUtility::debug("Skipping " . $mapSpec->id . " (" . $mapSpec->configfile . ") due to schedule.\n");
                    }
                }

//*********************
                MapUtility::debug("Iterated all $mapCount maps.\n");
            } else {
                if ($quietLogging == 0) {
                    MapUtility::warn("No activated maps found. [WMPOLL05]\n");
                }
            }
        } else {
            $NOTE = "";
            if (function_exists("posix_geteuid") && function_exists("posix_getpwuid")) {
                $processUser = posix_getpwuid(posix_geteuid());
                $username = $processUser['name'];
                $NOTE = " ($username)";
            }

            MapUtility::warn("Output directory ($outputDirectory) isn't writable (tried to create '$testfile'). No maps created. You probably need to make it writable by the poller process user$NOTE (like you did with the RRA directory) [WMPOLL06]\n");
            $totalWarnings++;
            $warningNotes .= " (Permissions problem prevents any maps running - check cacti.log for WMPOLL06)";
        }
    } else {
        MapUtility::warn("Output directory ($outputDirectory) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]\n");
        $totalWarnings++;
        $warningNotes .= " (Output directory problem prevents any maps running [WMPOLL07])";
    }
    Utility::memoryCheck("MEM Final");
    chdir($originalWorkingDir);
    $duration = microtime(true) - $startTime;

    $statsString = sprintf(
        '%s: %d maps were run in %.2f seconds with %d warnings. %s',
        date(DATE_RFC822),
        $mapCount,
        $duration,
        $totalWarnings,
        $warningNotes
    );
    if ($quietLogging == 0) {
        MapUtility::warn("STATS: Weathermap " . WEATHERMAP_VERSION . " run complete - $statsString\n", true);
    }
    $manager->application->setAppSetting("weathermap_last_stats", $statsString);
    $manager->application->setAppSetting("weathermap_last_finish_time", time());
}

// vim:ts=4:sw=4:
