<?php 
    // PHP Weathermap 0.98
    // Copyright Howard Jones, 2005-2014 howie@thingy.com
    // http://www.network-weathermap.com/
    // Released under the GNU Public License
    
    // Links, Nodes and the Map object inherit from this class ultimately.
    // Just to make some common code common.
    
    class WeatherMapBase
    {
    	var $notes = array();
    	var $hints = array();
    	var $imap_areas = array();
    	var $config = array();
    
    	var $inherit_fieldlist;
    
    	function add_note($name,$value)
    	{
    		wm_debug("Adding note $name='$value' to ".$this->name."\n");
    		$this->notes[$name] = $value;
    	}
    
    	function get_note($name)
    	{
    		if(isset($this->notes[$name]))
    		{
    			return($this->notes[$name]);
    		}
    		else
    		{
    			return(NULL);
    		}
    	}
    
    	function add_hint($name,$value)
    	{
    		wm_debug("Adding hint $name='$value' to ".$this->name."\n");
    		$this->hints[$name] = $value;
    	}
    
    
    	function get_hint($name)
    	{
    		if(isset($this->hints[$name]))
    		{
    			return($this->hints[$name]);
    		}
    		else
    		{
    			return(NULL);
    		}
    	}
    }
    
    // XXX - unused
    class WeatherMapConfigItem
    {
    	var $defined_in;
    	var $name;
    	var $value;
    	var $type;
    }
    
    // The 'things on the map' class. More common code (mainly variables, actually)
    class WeatherMapItem extends WeatherMapBase
    {
    	var $owner;
    
    	var $configline;
    	var $infourl;
    	var $overliburl;
    	var $overlibwidth, $overlibheight;
    	var $overlibcaption;
    	var $my_default;
    	var $defined_in;
    	var $config_override;	# used by the editor to allow text-editing
    
    	function my_type() {  return "ITEM"; }
    }