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
    public $baseDirectory;

    public $config;

    public $canRun;

    public function __construct($baseDirectory, $applicationInterface, $pollerStartTime = 0)
    {
        $this->baseDirectory = $baseDirectory;
        $this->configDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'configs';
        $this->outputDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'output';

        $this->manager = new MapManager(weathermap_get_pdo(), $this->configDirectory, $applicationInterface);

        $this->totalWarnings = 0;
        $this->warningNotes = "";
        $this->mapCount = 0;
        $this->canRun = false;

        $this->startTime = microtime(true);

        $this->config = new PollerConfig();
        $this->config->configDirectory = $this->configDirectory;
        $this->config->outputDirectory = $this->outputDirectory;
        $this->config->cronTime = ($pollerStartTime == 0 ? intval($this->startTime) : $pollerStartTime);
        $this->config->imageFormat = strtolower($this->manager->application->getAppSetting("weathermap_output_format", "png"));
        $this->config->rrdtoolFileName = $this->manager->application->getAppSetting("path_rrdtool", "rrdtool");
        $this->config->thumbnailSize = $this->manager->application->getAppSetting("weathermap_thumbsize", "200");
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

        if (!is_dir($this->configDirectory)) {
            MapUtility::warn("Config directory ($this->configDirectory) doesn't exist! This is probably a software bug [WMPOLL09]\n");
            $this->totalWarnings++;
            $this->warningNotes .= " (Config directory problem prevents any maps running WMPOLL09)";

            return;
        }

        if (!is_dir($this->outputDirectory)) {
            MapUtility::warn("Output directory ($this->outputDirectory) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process user$userNote (like you did with the RRA directory) [WMPOLL07new]\n");
            $this->totalWarnings++;
            $this->warningNotes .= " (Output directory problem prevents any maps running WMPOLL07new)";

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
            MapUtility::debug("run() quitting - preflight issue");
            return;
        }

        $this->manager->application->setAppSetting("weathermap_last_start_time", time());

        // ???
        $maplist = $this->manager->getMapRunList();
        if (!is_array($maplist)) {
            return;
        }

        MapUtility::debug("Iterating all maps.");

        $originalWorkingDir = getcwd();
        chdir($this->baseDirectory);

        foreach ($maplist as $map) {
            $runtime = new MapRuntime($this->config, $map, $this->manager);
            $ran = $runtime->run();
            if ($ran) {
                MapUtility::notice(json_encode($runtime->getStats()));
                $this->mapCount++;
            }

            $this->totalWarnings += $runtime->warncount;
        }
        chdir($originalWorkingDir);

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
     * Check if it's possible to create files in a directory
     *
     * @param $directory string
     * @return string
     */
    protected function testWritable($directory)
    {
        $testFileName = $directory . DIRECTORY_SEPARATOR . "weathermap.permissions.test";
        $testHandle = @fopen($testFileName, 'w');
        if ($testHandle) {
            fclose($testHandle);
            unlink($testFileName);
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
