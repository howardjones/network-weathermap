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
    var $mapfile;
    
    function WeatherMapEditor()
    {
    	$this->map = NULL;
    }
    
    function isLoaded() {
    	return !is_null($this->map);
    }
    
    function newConfig() 
    {
    	$this->map = new WeatherMap();
    	$map->context = "editor";
    	$this->mapfile = "untitled";
    }
    
    function loadConfig($filename)
    {
        $this->map = new WeatherMap();
       	$this->map->context = 'editor';
        $this->map->ReadConfig($filename);
		$this->mapfile = $filename;
    }
    
    function saveConfig($filename)
    {        	     	 
    	$this->mapfile = $filename;
    	$this->map->WriteConfig($filename);
    }
    
    function addNode($x, $y, $nodename="", $template="DEFAULT")
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	
    	$success = false;
    	$log = "";
    	$newnodename = null;
    	
    	// Generate a node name for ourselves  
    	if($nodename == "") {
	    	$newnodename = sprintf("node%05d",time()%10000);
	    	while(array_key_exists($newnodename,$this->map->nodes))
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
	    	$node->defined_in = $this->map->configfile;
	    	
	    	// needs to know if zlayer exists.
	    	if( !array_key_exists($node->zorder, $this->map->seen_zlayers)) {
	    		$this->map->seen_zlayers[$node->zorder] = array();
	    	}
	    	array_push($this->map->seen_zlayers[$node->zorder], $node);
	    	
	    	// only insert a label if there's no LABEL in the DEFAULT node.
	    	// otherwise, respect the template.
	    	if($this->map->nodes['DEFAULT']->label == $this->map->nodes[':: DEFAULT ::']->label)
	    	{
	    		$node->label = "Node";
	    	}
	    	
	    	$this->map->nodes[$node->name] = $node;
	    	$log = "added a node called $newnodename at $x,$y to $this->mapfile";
    		$success = true;
    	} else {
    		$log = "Requested node name already exists";
    		$success = false;
    	}
    	
    	return array($newnodename, $success, $log);
    }
    
    function moveNode($nodename, $new_x, $new_y)
    {
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    	$n_links = 0;
    	$n_nodes = 0;
    	$affected_nodes = array();
    	$affected_links = array();
    	
    	
    	
    	return array($n_nodes, $n_links, $affected_nodes, $affected_links);
    }
        
    function updateNode($nodename)
    {
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
    
    function deleteNode($nodename)
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
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
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
    
    function addLink($node1, $node2, $linkname="",$template="DEFAULT")
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	
    	$success = false;
    	$log = "";
    	$newlinkname = null;
    	
    	if($node1 != $node2 && isset($this->map->nodes[$node1]) && isset($this->map->nodes[$node2]) )
    	{
    		$newlink = new WeatherMapLink;
    		$newlink->Reset($this->map);
    			
    		$newlink->a = $this->map->nodes[$node1];
    		$newlink->b = $this->map->nodes[$node2];
    			
    	
 /////   		$newlink->width = $this->map->links['DEFAULT']->width;
    	
    		// make sure the link name is unique. We can have multiple links between
    		// the same nodes, these days
    		$newlinkname = "$node1-$node2";
    		while(array_key_exists($newlinkname,$this->map->links))
    		{
    			$newlinkname .= "a";
    		}
    		$newlink->name = $newlinkname;
    		$newlink->defined_in = $this->map->configfile;
    		$this->map->links[$newlinkname] = $newlink;

    		// needs to know if zlayer exists.
    		if( !array_key_exists($newlink->zorder, $this->map->seen_zlayers)) {
    			$this->map->seen_zlayers[$newlink->zorder] = array();
    		}
    		array_push($this->map->seen_zlayers[$newlink->zorder], $newlink);
       		$success = true;
       		$log = "created link $newlinkname between $node1 and $node2";
    	} 	 
       	
       	return array($newlinkname, $success, $log);
       	
    }
    
    function updateLink()
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
    
    function addLinkVia($linkname, $newx,$newy)
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
    
    function tidyLink($linkname) 
    {
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
    
    
    function asJS()
    {    
    	if (! $this->isLoaded() ) die("Map must be loaded before editing API called.");
    	 
    }
}