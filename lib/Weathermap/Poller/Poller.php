<?php
namespace Weathermap\Poller;

use Weathermap\Integrations\MapManager;

/**
 *
 * All the global environment required to run through the map list in the poller.
 *
 */
class Poller
{

    public $imageformat;
    public $rrdtool_path;
    public $confdir;
    public $manager;
    public $plugin_name;

    public $total_warnings;
    public $warning_notes;

    public $startTime;
    public $pollerStartTime;
    public $duration;
    public $mapcount;
    public $quietlogging;

    public $outdir;

    public $canRun;

    public function __construct($mydir, $pollerStartTime = 0)
    {
        $this->imageformat = strtolower(read_config_option("weathermap_output_format"));
        $this->rrdtool_path = read_config_option("path_rrdtool");
        $this->confdir = realpath($mydir . DIRECTORY_SEPARATOR . 'configs');
        $this->outdir = realpath($mydir . DIRECTORY_SEPARATOR . 'output');
        $this->manager = new MapManager(weathermap_get_pdo(), $this->confdir);
        $this->plugin_name = "weathermap-cacti-plugin.php";

        $this->total_warnings = 0;
        $this->warning_notes = "";
        $this->canRun = false;

        $this->startTime = microtime(true);

        $this->pollerStartTime = $pollerStartTime;
        if ($pollerStartTime == 0) {
            $this->pollerStartTime = intval($this->startTime);
        }
    }

    public function preFlight()
    {
        global $WEATHERMAP_VERSION;

        if (!wm_module_checks()) {
            wm_warn("Required modules for PHP Weathermap $WEATHERMAP_VERSION were not present. Not running. [WMPOLL08]\n");

            return;
        }

        $username = $this->getPollerUser();
        $userNote = "";
        if ($username != "") {
            $userNote = "($username)";
        }

        if (!is_dir($this->outdir)) {
            wm_warn("Output directory ($this->outdir) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process user$userNote (like you did with the RRA directory) [WMPOLL07]\n");
            $this->total_warnings++;
            $this->warning_notes .= " (Output directory problem prevents any maps running WMPOLL07)";

            return;
        }

        // next, make sure that we stand a chance of writing files
        if (!$this->testWritable($this->outdir)) {
            wm_warn("Output directory ($this->outdir) isn't writable (tried to create a file). No maps created. You probably need to make it writable by the poller process user$userNote (like you did with the RRA directory) [WMPOLL06]\n");
            $this->total_warnings++;
            $this->warning_notes .= " (Permissions problem prevents any maps running - check cacti.log for WMPOLL06)";

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

        wm_debug("Iterating all maps.");

        foreach ($maplist as $map) {
            $runner = new MapRuntime($this->confdir, $this->outdir, $map, $this->imageformat);
            $runner->run();

            $this->total_warnings += $runner->warncount;
        }

        $this->calculateStats();
        $this->manager->setAppSetting("weathermap_last_finish_time", time());
    }

    public function calculateStats()
    {
        global $WEATHERMAP_VERSION;

        $stats_string = sprintf('%s: %d maps were run in %.2f seconds with %d warnings. %s', date(DATE_RFC822), $this->mapcount, $this->duration, $this->total_warnings, $this->warning_notes);
        if ($this->quietlogging == 0) {
            wm_warn("STATS: Weathermap $WEATHERMAP_VERSION run complete - $stats_string\n", true);
        }
        $this->manager->setAppSetting("weathermap_last_stats", $stats_string);
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
