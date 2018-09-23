<?php

namespace Weathermap\Poller;

use Weathermap\Core\MapUtility;
use Weathermap\Integrations\Cacti\CactiApplicationInterface;
use Weathermap\Integrations\MapManager;
use Weathermap\Core\Utility;

// common code used by the poller, the manual-run from the Cacti UI, and from the command-line manual-run.
// this is the easiest way to keep it all consistent!


function runMaps($mydir)
{
    global $weathermap_debugging;
    global $weathermapPollerStartTime;

    $configDirectory = realpath($mydir . DIRECTORY_SEPARATOR . 'configs');
    $outputDirectory = $mydir . DIRECTORY_SEPARATOR . 'output';

    $app = new CactiApplicationInterface(weathermap_get_pdo());
    $manager = new MapManager(weathermap_get_pdo(), $configDirectory, $app);

    $startTime = microtime(true);
    if ($weathermapPollerStartTime == 0) {
        $weathermapPollerStartTime = intval($startTime);
    }

    $pollerConfig = new PollerConfig();
    $pollerConfig->rrdtoolFileName = $manager->application->getAppSetting("path_rrdtool", "rrdtool");
    $pollerConfig->imageFormat = strtolower($manager->application->getAppSetting("weathermap_output_format", "png"));
    $pollerConfig->configDirectory = $configDirectory;
    $pollerConfig->outputDirectory = $outputDirectory;
    $pollerConfig->thumbnailSize = $manager->application->getAppSetting("weathermap_thumbsize", "200");
    $pollerConfig->cronTime = $weathermapPollerStartTime;

    $totalWarnings = 0;
    $warningNotes = "";


    $mapCount = 0;

    // take our debugging cue from the poller - turn on Poller debugging to get weathermap debugging
    if ($manager->application->getAppSetting("log_verbosity", POLLER_VERBOSITY_LOW) >= POLLER_VERBOSITY_DEBUG) {
        $weathermap_debugging = true;
        $modeMessage = "DEBUG mode is on";
    } else {
        $modeMessage = "Normal logging mode. Turn on DEBUG in Cacti for more information";
    }
    $quietLogging = $manager->application->getAppSetting("weathermap_quiet_logging", 0);
    // moved this outside the module_checks, so there should always be something in the logs!
    if ($quietLogging == 0) {
        MapUtility::notice("Weathermap " . WEATHERMAP_VERSION . " starting - $modeMessage\n", true);
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
        $testfile = $outputDirectory . DIRECTORY_SEPARATOR . "weathermap.permissions.test";
        $testfd = @fopen($testfile, 'w');
        if ($testfd) {
            fclose($testfd);
            unlink($testfile);

            $queryrows = $manager->getMapRunList();

            if (is_array($queryrows)) {
                MapUtility::debug("Iterating all maps.");

                foreach ($queryrows as $mapSpec) {
                    $runtime = new MapRuntime($pollerConfig, $mapSpec, $manager);
                    $ran = $runtime->run();
                    if ($ran) {
                        MapUtility::notice(json_encode($runtime->getStats()));
                        $mapCount++;
                    }
                }

                MapUtility::debug("Iterated all $mapCount maps.\n");
            } else {
                MapUtility::notice("No activated maps found. [WMPOLL05]\n");
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
    MapUtility::notice("STATS: Weathermap " . WEATHERMAP_VERSION . " run complete - $statsString\n", true);
    $manager->application->setAppSetting("weathermap_last_stats", $statsString);
    $manager->application->setAppSetting("weathermap_last_finish_time", time());
}

// vim:ts=4:sw=4:
