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
    public $manager;
    public $pluginName;

    public $totalWarnings;
    public $warningNotes;

    public $startTime;
    public $pollerStartTime;
    public $duration;
    public $mapCount;
    public $quietLogging;

    public $outputDirectory;

    public $canRun;

    public function __construct($mydir, $pollerStartTime = 0)
    {
        $this->imageFormat = strtolower(\read_config_option("weathermap_output_format"));
        $this->rrdtoolPath = \read_config_option("path_rrdtool");
        $this->configDirectory = realpath($mydir . DIRECTORY_SEPARATOR . 'configs');
        $this->outputDirectory = realpath($mydir . DIRECTORY_SEPARATOR . 'output');
        $this->manager = new MapManager(weathermap_get_pdo(), $this->configDirectory);
        $this->pluginName = "weathermap-cacti-plugin.php";

        $this->totalWarnings = 0;
        $this->warningNotes = "";
        $this->canRun = false;

        $this->startTime = microtime(true);

        $this->pollerStartTime = $pollerStartTime;
        if ($pollerStartTime == 0) {
            $this->pollerStartTime = intval($this->startTime);
        }
    }

    public function preFlight()
    {
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
        $this->canRun = true;
    }

    public function run()
    {
        if (!$this->canRun) {
            return;
        }

        $this->manager->setAppSetting("weathermap_last_start_time", time());

        // ???
        $maplist = $this->manager->getMapRunList();
        if (!is_array($maplist)) {
            return;
        }

        MapUtility::debug("Iterating all maps.");

        foreach ($maplist as $map) {
            $runner = new MapRuntime($this->configDirectory, $this->outputDirectory, $map, $this->imageFormat);
            $runner->run();

            $this->totalWarnings += $runner->warncount;
        }

        $this->calculateStats();
        $this->manager->setAppSetting("weathermap_last_finish_time", time());
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
        $this->manager->setAppSetting("weathermap_last_stats", $statsString);
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
