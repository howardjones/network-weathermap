<?php
/**
 * Encapsulate all the random junk required to produce a map. Extracted from poller-common.php
 *
 */
class WeatherMapRunner
{
    
    private $mapObject;

    private $processedTitle;
    private $rrdtool;


    private $mapConfigFileName;
    private $htmlOutputFileName;
    private $imageOutputFileName;
    
    private $thumbnailImageFileName;
    private $thumbimagefile2;
    
    private $jsonOutputFileName;
    private $statisticsOutputFileName;
    private $resultsOutputFileName;
    
    private $workingImageFileName;

    private $warningCount;
    private $debugging;
    private $quietLogging;

    private $startTime;
    private $endTime;
    private $dataTime;

    private $mapID;
    private $groupID;
    
    public function __construct($configDirectory, $outputDirectory, $configFile, $fileHash, $imageFormat)
    {
        wm_debug("Format is passed as $imageFormat\n");

        $this->mapConfigFileName = $configDirectory . DIRECTORY_SEPARATOR . $configFile;
        $this->htmlOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".html";
        $this->imageOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".".$imageFormat;
        $this->thumbnailImageFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".thumb.".$imageFormat;
        $this->thumbimagefile2 = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".thumb48.".$imageFormat;

        $this->jsonOutputFileName  = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash.".json";

        $this->statisticsOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . '.stats.txt';
        $this->resultsOutputFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . '.results.txt';
        // temporary file used to write files before moving them into place
        $this->workingImageFileName = $outputDirectory . DIRECTORY_SEPARATOR . $fileHash . '.tmp.png';

        wm_debug("Output image is ".$this->imageOutputFileName."\n");

        $this->filehash = $fileHash;

        $this->mapObject = null;
        $this->warningCount = 0;

        $this->debugging = false;
        $this->quietLogging = false;

        // TODO - these need to come from somewhere!
        $this->mapID = 1;
        $this->groupID = 1;
    }

    public function __toString()
    {
        return sprintf("Runner: %s -> %s & %s", $this->mapConfigFileName, $this->htmlOutputFileName, $this->imageOutputFileName);
    }

    public function LoadMap()
    {
        if (file_exists($this->mapConfigFileName)) {
            if ($this->quietLogging == 0) {
                wm_warn("Map $this\n", true);
            }

            $this->mapObject = new Weathermap;
            $this->mapObject->context = "cacti";
            $this->mapObject->rrdtool = $this->rrdtool;
            $this->mapObject->ReadConfig($this->mapConfigFileName);

            return;
        }
        wm_warn("Mapfile $this->mapConfigFileName is not readable or doesn't exist [WMPOLL04]\n");
        $this->warningCount++;
    }

    public function canRun()
    {
        if($this->mapObject === null) {
            return false;
        }
        return true;
    }

    public function setDebug($newValue)
    {
        $this->debugging = $newValue;
    }

    public function applyAllHints($mapParameters)
    {
        $this->mapObject->add_hint("mapgroup", $mapParameters['groupname']);
        $this->mapObject->add_hint("mapgroupextra", ($mapParameters['group_id'] == 1 ? "" : $mapParameters['groupname']));

        $this->applyHints($this->mapID, $this->groupID);
        wm_debug("Applied hints\n");
    }

    public function run()
    {
        if (! $this->canRun()) {
            wm_warn("Couldn't run $this->mapConfigFileName\n", true);
            return;
        }

        $this->startTime = microtime(true);

        weathermap_memory_check("MEM postread");
        $this->mapObject->ReadData();
        weathermap_memory_check("MEM postdata");

        $this->mapObject->runProcessorPlugins("post");
        weathermap_memory_check("MEM pre-render");

        $this->dataTime = microtime(true);

        $this->mapObject->drawMapImage($this->workingImageFileName, $this->thumbnailImageFileName, read_config_option("weathermap_thumbsize"));

        $this->endTime = microtime(true);

        $this->processedTitle = $this->mapObject->processString($this->mapObject->title, $this->mapObject);

        $this->processImages();

        if ($this->quietLogging == 0) {
            wm_warn("Wrote: $this\n", true);
        }
    }

    /**
     * @return mixed
     */
    public function getProcessedTitle()
    {
        return $this->processedTitle;
    }

    private function processImages()
    {
        // Firstly, don't move or delete anything if the image saving failed
        if (file_exists($this->workingImageFileName)) {
            // Don't try and delete a non-existent file (first run)
            if (file_exists($this->imageOutputFileName)) {
                unlink($this->imageOutputFileName);
            }
            rename($this->workingImageFileName, $this->imageOutputFileName);
        }

        $gdThumbImage = imagecreatefrompng($this->thumbnailImageFileName);
        $gdThumb48Image = imagecreatetruecolor(48, 48);
        imagecopyresampled($gdThumb48Image, $gdThumbImage, 0, 0, 0, 0, 48, 48, imagesx($gdThumbImage), imagesy($gdThumbImage));
        imagepng($gdThumb48Image, $this->thumbimagefile2);
        imagedestroy($gdThumb48Image);
        imagedestroy($gdThumbImage);

        $alternateImageOutputFile = $this->mapObject->imageoutputfile;
        if ($alternateImageOutputFile != "" && $alternateImageOutputFile != "weathermap.png" && file_exists($this->imageOutputFileName)) {
            // copy the existing file to the configured location too
            @copy($this->imageOutputFileName, $alternateImageOutputFile);
        }

        if (intval($this->mapObject->thumb_width) > 0) {
            db_execute(sprintf(
                "update weathermap_maps set thumb_width='%d', thumb_height='%d' where id=%d",
                $this->mapObject->thumb_width,
                $this->mapObject->thumb_height,
                $this->mapID
            ));
        }
    }

    public function createAllHTML()
    {
        // switch out the configured Image URI for Cacti's own
        $configured_imageuri = $this->mapObject->imageuri;
        $this->mapObject->imageuri = 'weathermap-cacti-plugin.php?action=viewimage&id=' . $this->filehash . "&time=" . time();

        $this->createHTML($this->htmlOutputFileName);

        if ($this->mapObject->htmloutputfile != "") {
            // Now create the HTML file and ImageURI if requested, in addition
            $this->mapObject->imageuri = $configured_imageuri;
            $this->createHTML($this->mapObject->htmloutputfile);
        }
    }

    public function createHTML($htmlFilename)
    {
        $fileHandle = @fopen($htmlFilename, 'w');
        if ($fileHandle != false) {
            fwrite($fileHandle, $this->mapObject->makeHTML('weathermap_' . $this->filehash . '_imap'));
            fclose($fileHandle);
            wm_debug("Wrote HTML to $htmlFilename");
        } else {
            if (file_exists($htmlFilename)) {
                wm_warn("Failed to overwrite $htmlFilename - permissions of existing file are wrong? [WMPOLL02]\n");
            } else {
                wm_warn("Failed to create $htmlFilename - permissions of output directory are wrong? [WMPOLL03]\n");
            }
        }
    }

    public function writeDataFile()
    {
        $this->mapObject->writeDataFile($this->resultsOutputFileName);

        // if the user explicitly defined a data file, write it there too
        if ($this->mapObject->dataoutputfile) {
            $this->mapObject->writeDataFile($this->mapObject->dataoutputfile);
        }
    }

    public function cleanUp()
    {
        $this->mapObject->CleanUp();
        unset($this->mapObject);
    }

    public function applyHints($mapID, $groupID)
    {
        global $weathermap_error_suppress;

        wm_debug("Applying hints.\n");

        $weathermapObject = $this->mapObject;

        # in the order of precedence - global extras, group extras, and finally map extras
        $queries = array();
        $queries[] = "select * from weathermap_settings where mapid=0 and groupid=0";
        $queries[] = "select * from weathermap_settings where mapid=0 and groupid=" . $groupID;
        $queries[] = "select * from weathermap_settings where mapid=" . $mapID;

        foreach ($queries as $sql) {
            $settingrows = db_fetch_assoc($sql);
            if (is_array($settingrows) && count($settingrows) > 0) {
                foreach ($settingrows as $setting) {
                    $set_it = false;
                    if ($setting['mapid'] == 0 && $setting['groupid'] == 0) {
                        wm_debug("Setting additional (all maps) option: " . $setting['optname'] . " to '" . $setting['optvalue'] . "'\n");
                        $set_it = true;
                    } elseif ($setting['groupid'] != 0) {
                        wm_debug("Setting additional (all maps in group) option: " . $setting['optname'] . " to '" . $setting['optvalue'] . "'\n");
                        $set_it = true;
                    } else {
                        wm_debug("Setting additional map-global option: " . $setting['optname'] . " to '" . $setting['optvalue'] . "'\n");
                        $set_it = true;
                    }
                    if ($set_it) {
                        $weathermapObject->add_hint($setting['optname'], $setting['optvalue']);

                        if (substr($setting['optname'], 0, 7) == 'nowarn_') {
                            $code = strtoupper(substr($setting['optname'], 7));
                            $weathermap_error_suppress[] = $code;
                        }
                    }
                }
            }
        }
    }


    /**
     * @param mixed $rrdtool
     */
    public function setRrdtool($rrdtool)
    {
        $this->rrdtool = $rrdtool;
    }

}
