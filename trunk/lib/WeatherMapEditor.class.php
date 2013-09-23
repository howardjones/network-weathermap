<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once dirname(__FILE__).'/Weathermap.class.php';

/** Wrapper API around WeatherMap to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, WeatherMapEditorUI, is concerned with the actual presentation of the supplied editor)
 */

class WeatherMapEditor {
    
    var $map;
    
    function WeatherMapEditor()
    {
    	$this->map = NULL;
    }
    
    function isLoaded() {
    	return is_null($this->map);
    }
    
    function newConfig() 
    {
    	$this->map = new WeatherMap();
    	$map->context = "editor";
    }
    
    function loadConfig($filename)
    {
        $this->map = new WeatherMap();
       	$this->map->context = 'editor';
        $this->map->ReadConfig($filename);
    }
    
    function saveConfig($filename)
    {        	     	 
    	$this->map->WriteConfig($filename);
    }
    
    function addNode($x, $y, $nodename="", $template="DEFAULT")
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	
    	$success = false;  
    	
    	// Generate a node name for ourselves  
    	if($nodename == "") {
	    	$newnodename = sprintf("node%05d",time()%10000);
	    	while(array_key_exists($newnodename,$this>map->nodes))
	    	{
	    		$newnodename .= "a";
	    	}
    	} else {
    		$newnodename = $nodename;
    	}

    	// Check again - if they are specifying a name, it's possible for it to exist
    	if (!array_key_exists($newnodename, $this->map->nodes)) {
	    	$node = new WeatherMapNode;
	    	$node->name = $newnodename;
	    	$node->template = $template;
	    	
	    	$node->Reset($this->map);
	    	
	    	$node->x = $x;
	    	$node->y = $y;
	    	$node->defined_in = $map->configfile;
	    	
	    	array_push($this->map->seen_zlayers[$node->zorder], $node);
	    	
	    	// only insert a label if there's no LABEL in the DEFAULT node.
	    	// otherwise, respect the template.
	    	if($this->map->nodes['DEFAULT']->label == $this->map->nodes[':: DEFAULT ::']->label)
	    	{
	    		$node->label = "Node";
	    	}
	    	
	    	$this->map->nodes[$node->name] = $node;
	    	$log = "added a node called $newnodename at $x,$y to $mapfile";
    		$success = true;
    	} else {
    		$log = "Requested node name already exists";
    		$success = false;
    	}
    	
    	return array($newnodename, $success, $log);
    }
    
    function moveNode($nodename, $new_x, $new_y)
    {
    	$n_links = 0;
    	$n_nodes = 0;
    	$affected_nodes = array();
    	$affected_links = array();
    	
    	
    	
    	return array($n_nodes, $n_links, $affected_nodes, $affected_links);
    }
        
    function updateNode($nodename)
    {
    }
    
    function deleteNode($nodename)
    {        
    	$n_nodes = 0;
    	$n_links = 0;
    	
    	
    	if(isset($this->map->nodes[$nodename])) {
    		$log = "delete node ".$nodename;
    	
    		foreach ($this->map->links as $link)
    		{
    			if( isset($link->a) )
    			{
    				if( ($nodename == $link->a->name) || ($nodename == $link->b->name) )
    				{
    					unset($this->map->links[$link->name]);
    					$n_links++;
    				}
    			}
    		}
    	
    		unset($this->map->nodes[$target]);
    		$n_nodes++;
    	}
    	
    	return array($n_nodes, $n_links, $log);
    }

    function cloneNode($sourcename, $targetname="")
    {
    }
    
    function addLink($node1, $node2, $linkname="",$template="DEFAULT")
    {        
    }
    
    function updateLink()
    {        
    }
    
    function addLinkVia($linkname, $newx,$newy)
    {        
    }
    
    function tidyLink($linkname) 
    {
    }
    
    function asJS()
    {        
    }
}