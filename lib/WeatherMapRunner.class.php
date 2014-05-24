<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WeatherMapRunner
 *
 * @author Howard Jones
 */
class WeatherMapRunner {
    
    var $wmap;
    
    var $mapfile;
    var $htmlfile;
    var $imagefile;
    
    var $thumbimagefile;
    var $thumbimagefile2;
    
    var $jsonfile;
    var $statsfile;
    var $resultsfile;
    
    var $tempfile;
    
    function WeatherMapRunner($config_directory, $output_directory, $config_file, $filehash, $imageformat)
    {    
        $this->mapfile = $config_directory . DIRECTORY_SEPARATOR . $config_file;
        $this->htmlfile = $output_directory . DIRECTORY_SEPARATOR . $filehash.".html";
        $this->imagefile = $output_directory . DIRECTORY_SEPARATOR . $filehash.".".$imageformat;
        $this->thumbimagefile = $output_directory . DIRECTORY_SEPARATOR . $filehash.".thumb.".$imageformat;
        $this->thumbimagefile2 = $output_directory . DIRECTORY_SEPARATOR . $filehash.".thumb48.".$imageformat;

        $this->jsonfile  = $output_directory . DIRECTORY_SEPARATOR . $filehash.".json";

        $this->statsfile = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.stats.txt';
        $this->resultsfile = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.results.txt';
        // used to write files before moving them into place
        $this->tempfile = $output_directory . DIRECTORY_SEPARATOR . $filehash . '.tmp.png';                       
    }
    
    function LoadMap()
    {
        if (file_exists($this->mapfile)) {
            $this->wmap = new Weathermap;
            $this->wmap->context = "cacti";
        } else {
            wm_warn("Mapfile $this->mapfile is not readable or doesn't exist [WMPOLL04]\n");  
        }
    }
    
    function Run()
    {
        weathermap_memory_check("MEM postread $this->config_file");
        $this->wmap->ReadData();
        weathermap_memory_check("MEM postdata $this->config_file");
        weathermap_memory_check("MEM pre-render $mapcount");
        $this->wmap->DrawMap($this->tempfile, $this->thumbimagefile, read_config_option("weathermap_thumbsize"));
    }
    
    function CleanUp()
    {
        $this->wmap->CleanUp();
        unset($this->wmap);
    }
}
