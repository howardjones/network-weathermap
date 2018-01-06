<?php

namespace Weathermap\Poller;

use Weathermap\Core\MapUtility;

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

    public $warncount;
    public $duration;

    private $mapConfig;

    public function __construct($configDirectory, $outputDirectory, $mapManagerObj, $imageFormat)
    {
        $fileHash = $mapManagerObj->filehash;

        $this->mapConfig = $mapManagerObj;

        $this->mapConfigFileName = $configDirectory . DIRECTORY_SEPARATOR . $mapManagerObj->configfile;
        $this->htmlOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . ".html";
        $this->imageOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . "." . $imageFormat;

        $this->resultsFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . ".results.txt";
        $this->tempImageFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . ".tmp.png";

        $this->duration = 0;
        $this->warncount = 0;
    }

    public function __toString()
    {
        return sprintf("Runtime: %s -> %s & %s", $this->mapConfigFileName, $this->htmlOutputFileName,
            $this->imageOutputFileName);
    }

    private function preChecks()
    {
        if (!file_exists($this->mapConfigFileName)) {
            MapUtility::warn("Mapfile $this->mapConfigFileName is not readable or doesn't exist [WMPOLL04]\n");
            return false;
        }

        return true;
    }

    public function run()
    {
        if (!$this->preChecks()) {
            return false;
        }

        // TODO: Cron

        MapUtility::notice("Map: $this->mapConfigFileName -> $this->htmlOutputFileName& $this->imageOutputFileName\n",
            true);

        return true;
    }
}
