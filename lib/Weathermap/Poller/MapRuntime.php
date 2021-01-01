<?php

namespace Weathermap\Poller;

use Weathermap\Core\MapUtility;
use Weathermap\Core\StringUtility;
use Weathermap\Integrations\MapManager;
use Weathermap\Core\Utility;
use Weathermap\Core\Map;

/**
 *
 * A single map's worth of the surrounding stuff to make a map from a config file.
 *
 */
class MapRuntime
{
    private $mapConfigFileName;
    private $htmlOutputFileName;
    private $imageOutputFileName;
    private $resultsFileName;
    private $statsFileName;
    private $tempImageFileName;
    private $thumbnailFileName;

    private $pollerConfig;

    public $warncount;
    public $duration;

    private $manager;

    private $mapConfig;
    private $rrdtoolPath;
    private $thumbnailSize;

    private $description;

    private $times = array();
    private $memory = array();
    private $stats = array();

    private $context;

    /**
     * MapRuntime constructor.
     * @param PollerConfig $pollerConfig
     * @param stdClass $mapSpec
     * @param MapManager $manager
     */
    public function __construct($pollerConfig, $mapSpec, $manager, $context)
    {
        $this->manager = $manager;
        $this->mapConfig = $mapSpec;
        $this->pollerConfig = $pollerConfig;
        $this->context = $context;

        $this->rrdtoolPath = $pollerConfig->rrdtoolFileName;
        $this->thumbnailSize = $pollerConfig->thumbnailSize;

        $this->mapConfigFileName = $pollerConfig->configDirectory . DIRECTORY_SEPARATOR . $mapSpec->configfile;

        if ($mapSpec->filehash != "") {
            $this->htmlOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".html";
            $this->imageOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . "." . $pollerConfig->imageFormat;
            $this->thumbnailFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".thumb." . $pollerConfig->imageFormat;

            $this->resultsFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".results.txt";
            $this->statsFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".stats.txt";
            $this->tempImageFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".tmp.png";
        } else {
            // TODO - figure out what the correct CLI paths should be
            $this->htmlOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".html";
            $this->imageOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . "." . $pollerConfig->imageFormat;
            $this->thumbnailFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".thumb." . $pollerConfig->imageFormat;

            $this->resultsFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".results.txt";
            $this->statsFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".stats.txt";
            $this->tempImageFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".tmp.png";
        }
        $this->duration = 0;
        $this->warncount = 0;

        $this->description = "[Map " . $mapSpec->id . "] " . $mapSpec->configfile;
    }

    public function __toString()
    {
        return sprintf(
            "Runtime: %s -> %s & %s",
            $this->mapConfigFileName,
            $this->htmlOutputFileName,
            $this->imageOutputFileName
        );
    }

    private function preChecks()
    {
        if (!file_exists($this->mapConfigFileName)) {
            MapUtility::warn("Mapfile $this->mapConfigFileName is not readable or doesn't exist [WMPOLL04]\n");
            return false;
        }

        return true;
    }

    private function timeStamp($name)
    {
        $this->times[$name] = microtime(true);
    }

    private function memoryStamp($name)
    {
        $this->memory[$name] = StringUtility::formatNumberWithMetricSuffix(memory_get_usage());
    }

    private function checkCron()
    {
        return Utility::checkCronString($this->pollerConfig->cronTime, $this->mapConfig->schedule);
    }

    private function checkPoint($name)
    {
        $this->memoryStamp($name);
        $this->timeStamp($name);
    }

    /**
     * @return bool did it run a map or not
     */
    public function run($postRunCallback=null)
    {
        if (!$this->preChecks()) {
            return false;
        }

        if (!$this->checkCron()) {
            MapUtility::debug("Skipping " . $this->mapConfig->id . " (" . $this->mapConfig->configfile . ") due to schedule.\n");
            return false;
        }

        MapUtility::notice(
            "Map: $this->mapConfigFileName -> $this->htmlOutputFileName & $this->imageOutputFileName\n",
            true
        );
        $this->manager->application->setAppSetting("weathermap_last_started_file", $this->description);

        $this->memory['_limit_'] = $memAllowed = ini_get("memory_limit");
        $this->checkPoint("start");

        $map = new Map;
        $map->context = $this->context;

        // we can grab the rrdtool path from Cacti's config, in this case
        $map->rrdtool = $this->rrdtoolPath;

        $map->readConfig($this->mapConfigFileName);

        $this->checkPoint("config-read");

        $map->addHint("mapgroup", $this->mapConfig->groupname);
        $map->addHint("mapgroupextra", ($this->mapConfig->group_id == 1 ? "" : $this->mapConfig->groupname));
        $this->importMapSettings($map);

        $this->checkPoint("settings-set");

        $map->readData();

        $this->checkPoint("data-read");

        $configuredImageURI = $map->imageuri;
        $map->imageuri = $this->manager->application->getMapImageURL($this->mapConfig);

        $note = Utility::buildMemoryCheckString("");
        MapUtility::notice(
            "About to write image file. If this is the last message in your log, increase memory_limit in php.ini () [WMPOLL01] $note\n",
            true
        );

        // Write the image to a temporary file first - it turns out that libpng is not that fast
        // and this way we avoid showing half a map
        $map->drawMap(
            $this->tempImageFileName,
            $this->thumbnailFileName,
            $this->thumbnailSize
        );

        $this->checkPoint("post-render");

        // Firstly, don't move or delete anything if the image saving failed
        if (file_exists($this->tempImageFileName)) {
            // Don't try and delete a non-existent file (first run)
            if (file_exists($this->imageOutputFileName)) {
                unlink($this->imageOutputFileName);
            }
            rename($this->tempImageFileName, $this->imageOutputFileName);
        }

        MapUtility::notice("Wrote map to $this->imageOutputFileName and $this->thumbnailFileName\n", true);

        $this->writeHTMLFile($this->htmlOutputFileName, $map, $this->mapConfig->filehash);

        // put back the configured imageuri
        $map->imageuri = $configuredImageURI;

        // if an htmloutputfile was configured, output the HTML there too but using the configured imageuri and imagefilename
        if ($map->htmloutputfile != "") {
            MapUtility::debug("Writing additional HTML file to " . $map->htmloutputfile);
            $this->writeHTMLFile($map->htmloutputfile, $map, $this->mapConfig->filehash);
        }

        if ($map->imageoutputfile != "" && $map->imageoutputfile != "weathermap.png" && file_exists($this->imageOutputFileName)) {
            // copy the existing image file to the configured location too
            MapUtility::debug("Writing additional Image file to " . $map->imageoutputfile);
            @copy($this->imageOutputFileName, $map->imageoutputfile);
        }

        $map->writeDataFile($this->resultsFileName);

        // if the user explicitly defined a data file, write it there too
        if ($map->dataoutputfile) {
            $map->writeDataFile($map->dataoutputfile);
        }

        // From CLI, this will be 0 so no thumb is generated
        if (intval($map->thumbWidth) > 0) {
            $this->manager->updateMap(
                $this->mapConfig->id,
                array(
                    'thumb_width' => intval($map->thumbWidth),
                    'thumb_height' => intval($map->thumbHeight)
                )
            );
        }

        $this->extractStats($map);

        // Call a delegate if it was given (for some CLI debug functions)
        if (!is_callable($postRunCallback)) {
            MapUtility::notice("Calling post-run callback");
            call_user_func($postRunCallback, $map);
        } else {
            if (!is_null($postRunCallback)) {
                MapUtility::warn("Got a callback but it wasn't a callable");
            }
        }

        $map->cleanUp();

        $this->checkPoint("end");

        $mapDuration = $this->times['end'] - $this->times['start'];
        $conf = $this->mapConfig->configfile;
        MapUtility::debug("TIME: $conf took $mapDuration seconds.\n");
        $map->stats->set("duration", $mapDuration);
        $map->stats->set("warnings", $this->warncount);

        $this->stats = $map->stats->get();

        $this->manager->application->setAppSetting("weathermap_last_finished_file", $this->description);

        $stats_json = json_encode($this->getStats(), JSON_PRETTY_PRINT);

        $this->manager->updateMap(
            $this->mapConfig->id,
            array(
                'titlecache' => $map->processString($map->title, $map),
                'warncount' => intval($this->warncount),
                'runtime' => floatval($mapDuration),
                'stats' => $stats_json
            )
        );
        $this->writeTextFile($this->statsFileName, $stats_json);
        unset($map);

        return true;
    }

    public function getStats()
    {
        global $config;

        $env = array(
            "php_version" => phpversion(),
            "php_os" => php_uname(),
            "sql_version" => $this->manager->getDatabaseVersion(),
            "weathermap_version" => WEATHERMAP_VERSION,
            "host_app_version" => "",
            "gd_version" => ""
        );

        if (file_exists("/etc/lsb-release")) {
            $release_info = parse_ini_file("/etc/lsb-release");
            $env['php_os'] = $release_info['DISTRIB_DESCRIPTION'];
        }
        if (file_exists("/etc/redhat-release")) {
            $fd = fopen("/etc/redhat-release");
            $version_line = fgets($fd);
            fclose($fd);
            $env['php_os'] = $version_line;
        }

        if (function_exists('gd_info')) {
            $gdinfo = gd_info();
            $env['gd_version'] = $gdinfo['GD Version'];
        }

        $env['host_app_version'] = $this->manager->application->getAppVersion();

        print_r($this->times);

        $previous = reset($this->times);
        $calculated_times = array();
        foreach ($this->times as $label => $time) {
            $calculated_times[$label] = array($time, $time - $previous, $time - $this->times['start']);
            $previous = $time;
        }

        return array(
            "memory" => $this->memory,
            "times" => $calculated_times,
            "stats" => $this->stats,
            "environment" => $env
        );
    }

    /**
     * @param string $filename
     * @param Map $map
     * @param string $filehash
     */
    private function writeHTMLFile($filename, $map, $filehash)
    {
        $fd = @fopen($filename, 'w');
        if ($fd != false) {
            fwrite($fd, $map->makeHTML('weathermap_' . $filehash . '_imap'));
            fclose($fd);
            MapUtility::debug("Wrote HTML to $filename");
        } else {
            if (file_exists($filename)) {
                MapUtility::warn("Failed to overwrite $filename - permissions of existing file are wrong? [WMPOLL02]\n");
            } else {
                MapUtility::warn("Failed to create $filename - permissions of output directory are wrong? [WMPOLL03]\n");
            }
        }
    }

    private function writeTextFile($filename, $content)
    {
        $fd = @fopen($filename, 'w');
        if ($fd != false) {
            fwrite($fd, $content);
            fclose($fd);
            MapUtility::debug("Wrote to $filename");
        } else {
            if (file_exists($filename)) {
                MapUtility::warn("Failed to overwrite $filename - permissions of existing file are wrong? [WMPOLL02]\n");
            } else {
                MapUtility::warn("Failed to create $filename - permissions of output directory are wrong? [WMPOLL03]\n");
            }
        }
    }


    /**
     * @param Map $map
     */
    private function importMapSettings($map)
    {
        global $weathermap_error_suppress;

        # in the order of precedence - global extras, group extras, and finally map extras
        $settingsGlobal = $this->manager->getMapSettings(0);
        $settingsGroup = $this->manager->getMapSettings(-$this->mapConfig->group_id);
        $settingsMap = $this->manager->getMapSettings($this->mapConfig->id);

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
    }

    /**
     * @param Map $map
     */
    private function extractStats(Map $map)
    {
        # remove the 'DEFAULT' and ':: DEFAULT ::' from the counts for links and nodes
        $map->stats->set("n_nodes", count($map->nodes) - 2);
        $map->stats->set("n_links", count($map->links) - 2);

        # remove the 'none' scale from the count for scales
        $map->stats->set("n_scales", count($map->scales) - 1);

        $n_vias = 0;
        $n_curves = 0;
        $n_angles = 0;

        $target_stats = array();

        foreach ($map->links as $link) {
            $via_count = count($link->viaList);
            if ($via_count > 0) {
                $n_vias += $via_count;
                if ($link->viaStyle == "angled") {
                    $n_angles += $via_count;
                }
                if ($link->viaStyle == "curved") {
                    $n_curves += $via_count;
                }
            }
        }

        foreach ($map->buildAllItemsList() as $item) {
            foreach ($item->targets as $target) {
                $tgt_plugin = $target->pluginName;
                if (!array_key_exists($tgt_plugin, $target_stats)) {
                    $target_stats[$tgt_plugin] = array("count" => 0, "valid" => 0);
                }
                $target_stats[$tgt_plugin]['count']++;
                if ($target->hasValidData()) {
                    $target_stats[$tgt_plugin]['valid']++;
                }
            }
        }

        foreach ($target_stats as $name => $plugin) {
            foreach ($plugin as $key => $value) {
                $map->stats->set("tgt_" . $name . "_" . $key, $value);
            }
        }

        $poller_output = "no";
        if ($map->getHint("rrd_use_poller_output", 0) == 1) {
            $poller_output = "yes";
        }
        $map->stats->set("tgt_RRDTool_poller_output", $poller_output);

        $map->stats->set("n_vias", $n_vias);
        $map->stats->set("n_curves", $n_curves);
        $map->stats->set("n_angles", $n_angles);

        $map->stats->set("width", $map->width);
        $map->stats->set("height", $map->height);
    }
}
