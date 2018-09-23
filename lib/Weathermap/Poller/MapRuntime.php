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

    /**
     * MapRuntime constructor.
     * @param PollerConfig $pollerConfig
     * @param stdClass $mapSpec
     * @param MapManager $manager
     */
    public function __construct($pollerConfig, $mapSpec, $manager)
    {
        $this->manager = $manager;
        $this->mapConfig = $mapSpec;
        $this->pollerConfig = $pollerConfig;

        $this->rrdtoolPath = $pollerConfig->rrdtoolFileName;
        $this->thumbnailSize = $pollerConfig->thumbnailSize;

        $this->mapConfigFileName = $pollerConfig->configDirectory . DIRECTORY_SEPARATOR . $mapSpec->configfile;
        $this->htmlOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".html";
        $this->imageOutputFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . "." . $pollerConfig->imageFormat;
        $this->thumbnailFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".thumb." . $pollerConfig->imageFormat;

        $this->resultsFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".results.txt";
        $this->tempImageFileName = $pollerConfig->outputDirectory . DIRECTORY_SEPARATOR . $mapSpec->filehash . ".tmp.png";

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

    public function run()
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
        $map->context = "cacti";

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
        $map->imageuri = $this->manager->application->getMapImageURL($this->mapConfig->filehash);

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

        // TODO: will this ever be 0?
        if (intval($map->thumbWidth) > 0) {
            $this->manager->updateMap(
                $this->mapConfig->id,
                array(
                    'thumb_width' => intval($map->thumbWidth),
                    'thumb_height' => intval($map->thumbHeight)
                )
            );
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

        $this->manager->updateMap(
            $this->mapConfig->id,
            array(
                'titlecache' => $map->processString($map->title, $map),
                'warncount' => intval($this->warncount),
                'runtime' => floatval($mapDuration)
            )
        );

        unset($map);

        return true;
    }

    public function getStats()
    {
        return array(
            "memory" => $this->memory,
            "times" => $this->times,
            "stats" => $this->stats,
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
}
