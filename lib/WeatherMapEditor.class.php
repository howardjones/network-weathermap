<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "Weathermap.class.php";

/** Wrapper API around WeatherMap to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, WeatherMapEditorUI, is concerned with the actual presentation of the supplied editor)
 */

class WeatherMapEditor {
    
    var $map;
    
    function loadConfig($filename)
    {
        $map = new WeatherMap();
       	$map->context = 'editor';
        $map->ReadConfig($filename);
    }
    
    function saveConfig($filename)
    {        
        $map->WriteConfig($filename);
    }
    
    function addNode()
    {        
    }
    
    function moveNode()
    {        
    }
        
    function updateNode()
    {
    }
    
    function deleteNode()
    {        
    }
    
    function addLink()
    {        
    }
    
    function updateLink()
    {        
    }
    
    function addLinkVia()
    {        
    }
    
    function asJS()
    {        
    }
}