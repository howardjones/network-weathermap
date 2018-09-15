<?php

namespace Weathermap\Poller;

use Weathermap\Integrations\MapManager;
use Weathermap\Core\MapUtility;

/**
 *
 * All the global environment required to run through the map list in the poller.
 *
 */
class Poller
{

    public $imageFormat;
    public $rrdtoolPath;
    public $configDirectory;
    /** @var MapManager $manager */
    public $manager;

    public $totalWarnings;
    public $warningNotes;

    public $startTime;
    public $pollerStartTime;
    public $duration;
    public $mapCount;
    public $quietLogging;

    public $outputDirectory;

    public $config;

    public $canRun;

    public function __construct($baseDirectory, $applicationInterface, $pollerStartTime = 0)
    {
        $this->config = new PollerConfig();
        // TODO: fill in the pollerConfig

        $this->configDirectory = realpath($baseDirectory . DIRECTORY_SEPARATOR . 'configs');
        $this->outputDirectory = realpath($baseDirectory . DIRECTORY_SEPARATOR . 'output');

        $this->manager = new MapManager(weathermap_get_pdo(), $this->configDirectory, $applicationInterface);

        $this->imageFormat = strtolower($this->manager->application->getAppSetting("weathermap_output_format", "png"));
        $this->rrdtoolPath = $this->manager->application->getAppSetting("path_rrdtool", "rrdtool");

        $this->totalWarnings = 0;
        $this->warningNotes = "";
        $this->mapCount = 0;
        $this->canRun = false;

        $this->startTime = microtime(true);

        $this->pollerStartTime = $pollerStartTime;
        if ($pollerStartTime == 0) {
            $this->pollerStartTime = intval($this->startTime);
        }
    }

    public function preFlight()
    {
        MapUtility::debug("Poller preflight checks running.");

        if (!MapUtility::moduleChecks()) {
            MapUtility::warn("Required modules for PHP Weathermap " . WEATHERMAP_VERSION . " were not present. Not running. [WMPOLL08]\n");

            return;
        }

        $username = $this->getPollerUser();
        $userNote = "";
        if ($username != "") {
            $userNote = "($username)";
        }

        if (!is_dir($this->outputDirectory)) {
            MapUtility::warn("Output directory ($this->outputDirectory) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process user$userNote (like you did with the RRA directory) [WMPOLL07]\n");
            $this->totalWarnings++;
            $this->warningNotes .= " (Output directory problem prevents any maps running WMPOLL07)";

            return;
        }

        // next, make sure that we stand a chance of writing files
        if (!$this->testWritable($this->outputDirectory)) {
            MapUtility::warn("Output directory ($this->outputDirectory) isn't writable (tried to create a file). No maps created. You probably need to make it writable by the poller process user$userNote (like you did with the RRA directory) [WMPOLL06]\n");
            $this->totalWarnings++;
            $this->warningNotes .= " (Permissions problem prevents any maps running - check cacti.log for WMPOLL06)";

            return;
        }

        MapUtility::debug("Poller preflight checks passed.");
        $this->canRun = true;
    }

    public function run()
    {
        if (!$this->canRun) {
            return;
        }

        $this->manager->application->setAppSetting("weathermap_last_start_time", time());

        // ???
        $maplist = $this->manager->getMapRunList();
        if (!is_array($maplist)) {
            return;
        }

        MapUtility::debug("Iterating all maps.");

        foreach ($maplist as $map) {
            $runtime = new MapRuntime($this->config, $map, $this->manager);
            $ran = $runtime->run();
            if ($ran) {
                MapUtility::notice(json_encode($runtime->getStats()));
                $this->mapCount++;
            }

            $this->totalWarnings += $runtime->warncount;
        }

        $this->calculateStats();
        $this->manager->application->setAppSetting("weathermap_last_finish_time", time());
    }

    public function calculateStats()
    {
        $statsString = sprintf(
            '%s: %d maps were run in %.2f seconds with %d warnings. %s',
            date(DATE_RFC822),
            $this->mapCount,
            $this->duration,
            $this->totalWarnings,
            $this->warningNotes
        );
        if ($this->quietLogging == 0) {
            MapUtility::warn("STATS: Weathermap " . WEATHERMAP_VERSION . " run complete - $statsString\n", true);
        }
        $this->manager->application->setAppSetting("weathermap_last_stats", $statsString);
    }

    /**
     * @return string
     */
    protected function testWritable($directory)
    {
        $testfile = $directory . DIRECTORY_SEPARATOR . "weathermap.permissions.test";
        $testfd = @fopen($testfile, 'w');
        if ($testfd) {
            fclose($testfd);
            unlink($testfile);
            return true;
        }
        return false;
    }

    private function getPollerUser()
    {
        $username = "";
        if (function_exists("posix_geteuid") && function_exists("posix_getpwuid")) {
            $processUser = posix_getpwuid(posix_geteuid());
            $username = $processUser['name'];
        }

        return $username;
    }
}
