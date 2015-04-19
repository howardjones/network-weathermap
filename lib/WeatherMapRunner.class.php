<?php
/**
 * Encapsulate all the random junk required to produce a map. Extracted from poller-common.php
 *
 */
class WeatherMapRunner {
    
    private $map;
    
    private $mapConfigFileName;
    private $htmlOutputFileName;
    private $imageOutputFileName;
    
    private $thumbnailImageFileName;
    private $thumbimagefile2;
    
    private $jsonOutputFileName;
    private $statisticsOutputFileName;
    private $resultsOutputFileName;
    
    private $workingImageFileName;
    
    function __construct($config_directory, $output_directory, $config_file, $filehash, $imageformat)
    {    
        $this->mapConfigFileName = $config_directory . DIRECTORY_SEPARATOR . $config_file;
        $this->htmlOutputFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash.".html";
        $this->imageOutputFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash.".".$imageformat;
        $this->thumbnailImageFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash.".thumb.".$imageformat;
        $this->thumbimagefile2 = $output_directory . DIRECTORY_SEPARATOR . $filehash.".thumb48.".$imageformat;

        $this->jsonOutputFileName  = $output_directory . DIRECTORY_SEPARATOR . $filehash.".json";

        $this->statisticsOutputFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.stats.txt';
        $this->resultsOutputFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.results.txt';
        // temporary file used to write files before moving them into place
        $this->workingImageFileName = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.tmp.png';
    }
    
    function LoadMap()
    {
        if (file_exists($this->mapConfigFileName)) {
            $this->map = new Weathermap;
            $this->map->context = "cacti";
            $this->map->ReadConfig($this->mapConfigFileName);

            return;
        }

        wm_warn("Mapfile $this->mapConfigFileName is not readable or doesn't exist [WMPOLL04]\n");
    }
    
    function Run()
    {
        weathermap_memory_check("MEM postread $this->config_file");
        $this->map->ReadData();
        weathermap_memory_check("MEM postdata $this->config_file");

        $this->map->runProcessorPlugins("post");
        weathermap_memory_check("MEM pre-render $mapcount");

        $this->map->DrawMap($this->workingImageFileName, $this->thumbnailImageFileName, read_config_option("weathermap_thumbsize"));
    }
    
    function CleanUp()
    {
        $this->map->CleanUp();
        unset($this->map);
    }
}
