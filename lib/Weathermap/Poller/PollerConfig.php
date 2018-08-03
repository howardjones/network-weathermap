<?php

namespace Weathermap\Poller;

/** All the random settings that the poller needs to pass to the MapRuntime from the environment
 */
class PollerConfig
{
    public $rrdtoolFileName;
    public $imageFormat;
    public $configDirectory;
    public $outputDirectory;

    public $thumbnailSize;
    public $pluginName;

    public $cronTime;
}
