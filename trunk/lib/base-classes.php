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
    var $descendents = array();

    var $inherit_fieldlist;

    function my_type()
    {
        return "MAP";
    }

    function add_note($name, $value)
    {
        wm_debug("Adding note $name='$value' to ".$this->name."\n");
        $this->notes[$name] = $value;
    }

    function get_note($name)
    {
        if (isset($this->notes[$name])) {
            return($this->notes[$name]);
        }

        return(null);
    }

    function add_hint($name, $value)
    {
        wm_debug("Adding hint $name='$value' to ".$this->name."\n");
        $this->hints[$name] = $value;
    }


    function get_hint($name)
    {
        if (isset($this->hints[$name])) {
            return($this->hints[$name]);
        }

        return(null);
    }

    /**
     * Get a value for a config variable. Follow the template inheritance tree if necessary.
     * Return an array with the value followed by the status (whether it came from the source object or
     * a template, or just didn't exist). This will replace all that CopyFrom stuff.
     *
     * @param $keyname
     * @return array
     */
    function getConfig($keyname)
    {
        if (isset($this->config[$keyname])) {
            return array($this->config[$keyname], CONF_FOUND_DIRECT);
        } else {
            if (!is_null($this->parent)) {
                list($value, $direct) = $this->parent->getConfig($keyname);
                if($direct != CONF_NOT_FOUND) {
                    $direct = CONF_FOUND_INHERITED;
                }
            } else {
                $value = null;
                $direct = CONF_NOT_FOUND;
            }

            // if we got to the top of the tree without finding it, that's probably a typo in the original getConfig()
            if (is_null($value) && is_null($this->parent)) {
                wm_warn("Tried to get config keyword '$keyname' with no result. [WMWARN300]");
            }
            return array($value, $direct);
        }
    }

    function getConfigWithoutInheritance($keyname)
    {
        if (isset($this->config[$keyname])) {
            return $this->config[$keyname];
        }
        return array(null);
    }

    /*
     * Set a new value for a config variable. If $recalculate is true (after the initial readConfig)
     * then also recursively tell all objects that have us as a template that their state has changed
     *
     * return an array of the objects that were notified
     */
    function setConfig($keyname, $value, $recalculate=false)
    {
        wm_debug("Settings config %s = %s\n", $keyname, $value);
        if (is_null($value)) {
            unset($this->config[$keyname]);
        } else {
            $this->config[$keyname] = $value;
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    function addConfig($keyname, $value, $recalculate=false)
    {
        wm_debug("Appending config %s = %s\n", $keyname, $value);
        if (is_null($this->config[$keyname])) {
            // create a new array, with this as the only item
            $this->config[$keyname] = array($value);
        } else {
            if (is_array($this->config[$keyname])) {
                // append the new item to the existing array
                $this->config[$keyname] []= $value;
            } else {
                // This is the second value, so make a new array of the old one, and this one
                $this->config[$keyname] = array( $this->config[$keyname], $value);
            }
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    /**
     * Do any pre-drawing calculations needed, then let any items that use us as a template
     * do theirs, too. Recursively build up a list of the affected objects so we could
     * tell the editor to do selective updates
     */
    function recalculate()
    {
        $notified = array();
        $notified []= $this->name;
        wm_debug("Recalculating %s %s\n", $this->my_type(), $this->name);
        $this->preCalculate();

        foreach ($this->descendents as $child) {
            wm_debug("  %s notifying %s\n", $this->name, $child->name);
            $new_notified = $child->recalculate();
            foreach ($new_notified as $n) {
                $notified []= $n;
            }
        }
        return $notified;
    }

    function setTemplate($template_name, $owner)
    {
        $this->template = $template_name;
        wm_debug("Resetting to template %s %s\n", $this->my_type(), $template_name);
        $this->reset($owner);
    }
}

// The 'things on the map' class. More common code (mainly variables, actually)
class WeatherMapItem extends WeatherMapBase
{
    var $owner;

    var $configline;
    var $infourl;
    var $overliburl;
    var $overlibwidth;
    var $overlibheight;
    var $overlibcaption;
    var $descendents; # if I change, who could inherit that change?
    var $config;  # config set on this node specifically
    var $parent;
    var $my_default;
    var $defined_in;
    var $config_override;	# used by the editor to allow text-editing

    function my_type()
    {
        return "ITEM";
    }

}
