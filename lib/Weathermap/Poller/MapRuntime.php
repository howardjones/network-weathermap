<?php

namespace Weathermap\Poller;

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

    public $warncount;

    public function __construct($configDirectory, $outputDirectory, $mapObject, $imageFormat)
    {
        $fileHash = $mapObject->filehash;

        $this->mapConfigFileName = $configDirectory . DIRECTORY_SEPARATOR . $mapObject->configfile;
        $this->htmlOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".html";
        $this->imageOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".".$imageFormat;
    }

    public function __toString()
    {
        return sprintf("Runtime: %s -> %s & %s", $this->mapConfigFileName, $this->htmlOutputFileName, $this->imageOutputFileName);
    }

    public function run()
    {
    }
}
